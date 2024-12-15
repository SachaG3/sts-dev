<?php

namespace App\Console\Commands;

use App\Models\Cours;
use App\Models\Jour;
use App\Models\Log;
use Carbon\Carbon;
use ICal\ICal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CompareCours extends Command
{
    protected $signature = 'compare:cours';
    protected $description = 'Compare les cours du fichier ICS avec ceux de la base de données et met à jour la salle si nécessaire, en se basant uniquement sur les horaires, y compris si l\'événement ICS couvre plusieurs créneaux.';

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
            Log::create([
                'date' => now(),
                'type' => 'ecoledirect',
                'state' => 'error',
                'message' => 'Impossible de télécharger le fichier ICS : ' . $e->getMessage()
            ]);
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
            Log::create([
                'date' => now(),
                'type' => 'ecoledirect',
                'state' => 'info',
                'message' => 'Aucun événement trouvé dans le fichier ICS.'
            ]);
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
                    $heureDebutIcs = $eventDate->format('H:i:s');
                    $heureFinIcs = Carbon::parse($event->dtend)->setTimezone('Europe/Paris')->format('H:i:s');
                    $salleIcs = $event->location;

                    // Rechercher tous les cours qui se chevauchent avec l'intervalle ICS
                    // Condition de chevauchement :
                    // cours.heure_debut < heureFinIcs ET cours.heure_fin > heureDebutIcs
                    $coursChevauchants = Cours::where('jour_id', $jourId)
                        ->where('heure_debut', '<', $heureFinIcs)
                        ->where('heure_fin', '>', $heureDebutIcs)
                        ->get();

                    if ($coursChevauchants->isEmpty()) {
                        Log::create([
                            'date' => now(),
                            'type' => 'ecoledirect',
                            'state' => 'info',
                            'message' => "Aucun cours trouvé se chevauchant avec le créneau {$heureDebutIcs}-{$heureFinIcs}."
                        ]);
                    } else {
                        foreach ($coursChevauchants as $cours) {
                            if ($cours->salle !== $salleIcs) {
                                Log::create([
                                    'date' => now(),
                                    'type' => 'ecoledirect',
                                    'state' => 'succès',
                                    'message' => "Salle mise à jour pour le cours de {$cours->heure_debut} à {$cours->heure_fin}. Ancienne salle: {$cours->salle}, Nouvelle salle: {$salleIcs}."
                                ]);
                                $cours->salle = $salleIcs;
                                $cours->save();
                            } else {
                                Log::create([
                                    'date' => now(),
                                    'type' => 'ecoledirect',
                                    'state' => 'info',
                                    'message' => "Aucune mise à jour nécessaire pour le cours de {$cours->heure_debut} à {$cours->heure_fin} (déjà dans la salle {$salleIcs})."
                                ]);
                            }
                        }
                    }
                } else {
                    Log::create([
                        'date' => now(),
                        'type' => 'ecoledirect',
                        'state' => 'info',
                        'message' => "Aucun jour correspondant trouvé pour la semaine et le jour {$eventDate->format('l')}."
                    ]);
                }
            } else {
                Log::create([
                    'date' => now(),
                    'type' => 'ecoledirect',
                    'state' => 'info',
                    'message' => "Aucune semaine correspondante trouvée pour la date {$lundiEvent->format('Y-m-d')}."
                ]);
            }
        }

        Log::create([
            'date' => now(),
            'type' => 'ecoledirect',
            'state' => 'succès',
            'message' => 'Comparaison terminée.'
        ]);
    }
}
