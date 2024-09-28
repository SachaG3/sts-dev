<?php

namespace App\Console\Commands;

use App\Models\Cours;
use App\Models\Jour;
use App\Models\Log;
use App\Models\Matiere;
use Carbon\Carbon;
use ICal\ICal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

// Importer le modèle Log

class CompareCours extends Command
{
    protected $signature = 'compare:cours';
    protected $description = 'Compare les cours du fichier ICS avec ceux de la base de données et met à jour la salle si nécessaire';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            $url = 'https://api.ecoledirecte.com/v3/ical/E/11254/51314661597a597a5a32704f4c326f315358427463485a6b52484a364e577069555841325758517a4b3051.ics';
            $response = Http::get($url);
        } catch (\Exception $e) {
            Log::create(['date' => now(), 'type' => 'ecoledirect', 'state' => 'error', 'message' => 'Impossible de télécharger le fichier ICS.']);
            Http::get("https://smsapi.free-mobile.fr/sendmsg", [
                'user' => '54876185',
                'pass' => getenv('MESSAGE_API'),
                'msg' => "Le fichier École Direct ne fonctionne pas. Consultez les logs pour plus de détails : https://sts-dev/admin/log",
            ]);
            return;
        }

        $icsFilePath = storage_path('app/tmp/calendar.ics');
        file_put_contents($icsFilePath, $response->body());

        $ical = new ICal($icsFilePath);
        $events = $ical->events();

        if (empty($events)) {
            Log::create(['date' => now(), 'type' => 'ecoledirect', 'state' => 'info', 'message' => 'Aucun événement trouvé dans le fichier ICS.']);
            return;
        }

        foreach ($events as $event) {
            $eventDate = Carbon::parse($event->dtstart)->setTimezone('Europe/Paris');
            $lundiEvent = $eventDate->copy()->startOfWeek(Carbon::MONDAY);

            $jourLundi = Jour::where('date', $lundiEvent->format('Y-m-d'))->first();

            if ($jourLundi) {
                $jourDeLaSemaine = $eventDate->dayOfWeekIso - 1;

                $jourId = $jourLundi->id + $jourDeLaSemaine;

                $jourCorrespondant = Jour::find($jourId);

                if ($jourCorrespondant) {
                    $matiereName = $event->summary;

                    if ($matiereName === 'MATHEMATIQUES') {
                        $matiereName = 'UTC501';
                    } elseif ($matiereName === 'GDN100') {
                        $matiereName = 'CCE105_SP';
                    }

                    $heureDebut = $eventDate->format('H:i:s');
                    $heureFin = Carbon::parse($event->dtend)->setTimezone('Europe/Paris')->format('H:i:s');

                    $salleIcs = $event->location;

                    $matiere = Matiere::where('name', $matiereName)->first();

                    if ($matiere) {
                        $cours = Cours::where('heure_debut', $heureDebut)
                            ->where('heure_fin', $heureFin)
                            ->where('matiere_id', $matiere->id)
                            ->where('jour_id', $jourId)
                            ->first();

                        if ($cours) {
                            if ($cours->salle !== $salleIcs) {
                                Log::create(['date' => now(), 'type' => 'ecoledirect', 'state' => 'succès', 'message' => "Salle différente pour le cours {$matiereName}. Mise à jour de {$cours->salle} à {$salleIcs}."]);
                                $cours->salle = $salleIcs;
                                $cours->save();
                            } else {
                                Log::create(['date' => now(), 'type' => 'ecoledirect', 'state' => 'info', 'message' => "Aucune mise à jour nécessaire pour le cours {$matiereName}."]);
                            }
                        } else {
                            Log::create(['date' => now(), 'type' => 'ecoledirect', 'state' => 'info', 'message' => "Aucun cours correspondant trouvé pour {$matiereName}."]);
                        }
                    } else {
                        Log::create(['date' => now(), 'type' => 'ecoledirect', 'state' => 'info', 'message' => "Aucune matière trouvée pour {$matiereName}."]);
                    }
                } else {
                    Log::create(['date' => now(), 'type' => 'ecoledirect', 'state' => 'info', 'message' => "Aucun jour correspondant trouvé pour la semaine et le jour {$eventDate->format('l')}."]);
                }
            } else {
                Log::create(['date' => now(), 'type' => 'ecoledirect', 'state' => 'info', 'message' => "Aucune semaine correspondante trouvée pour la date {$lundiEvent->format('Y-m-d')}."]);
            }
        }

        Log::create(['date' => now(), 'type' => 'ecoledirect', 'state' => 'succès', 'message' => 'Comparaison terminée.']);
    }
}
