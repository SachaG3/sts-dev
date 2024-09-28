<?php

namespace App\Console\Commands;

use App\Models\Log;
use ICal\ICal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ChekCourExist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:chek-cour-exist';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie si un cours fait partie d\'une liste brute depuis un fichier ICS d\'École Direct et envoie une alerte si non présent.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $url = 'https://api.ecoledirecte.com/v3/ical/E/11254/51314661597a597a5a32704f4c326f315358427463485a6b52484a364e577069555841325758517a4b3051.ics';
            $response = Http::get($url);

            if ($response->status() !== 200) {
                throw new \Exception("Erreur de téléchargement du fichier ICS");
            }

        } catch (\Exception $e) {
            Log::create(['date' => now(), 'type' => 'ecoledirectcheckcourt', 'state' => 'error', 'message' => "Impossible de télécharger le fichier ICS : {$e->getMessage()}",]);
            return;
        }

        // Liste brute des cours à vérifier
        $coursExistants = [
            'MATHEMATIQUES',
            'GDN100',
            'ANG320',
            '',
        ];

        $icsFilePath = storage_path('app/tmp/calendar.ics');
        file_put_contents($icsFilePath, $response->body());

        $ical = new ICal($icsFilePath);
        $events = $ical->events();

        if (empty($events)) {
            Log::create(['date' => now(), 'type' => 'ecoledirectcheckcourt', 'state' => 'info', 'message' => 'Aucun événement trouvé dans le fichier ICS.',]);
            return;
        }

        foreach ($events as $event) {
            $matiereName = $event->summary; // Nom du cours

            if (in_array($matiereName, $coursExistants)) {
                Log::create(['date' => now(), 'type' => 'ecoledirectcheckcourt', 'state' => 'success', 'message' => "Le cours '{$matiereName}' existe dans la liste.",]);
            } else {
                Log::create(['date' => now(), 'type' => 'ecoledirectcheckcourt', 'state' => 'warning', 'message' => "Le cours '{$matiereName}' n'existe pas dans la liste.",]);

                try {
                    $test = env('MESSAGE_API');
                    $message = "Le cours '{$matiereName}' n'existe pas dans la liste des cours. Consultez les logs : https://sts-dev/admin/log";

                    Http::get("https://smsapi.free-mobile.fr/sendmsg", [
                        'user' => '54876185',
                        'pass' => $test,
                        'msg' => $message,
                    ]);
                } catch (\Exception $e) {
                    Log::create(['date' => now(), 'type' => 'ecoledirectcheckcourt', 'state' => 'error', 'message' => "Impossible d'envoyer le message SMS pour le cours '{$matiereName}'.",]);
                }
            }
        }

        Log::create(['date' => now(), 'type' => 'ecoledirectcheckcourt', 'state' => 'success', 'message' => 'Vérification des cours terminée.',]);
    }
}
