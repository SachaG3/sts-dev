<?php

namespace App\Http\Controllers;

use App\Models\Log as LogModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ScheduleController extends Controller
{
    public function fetchAndStore(Request $request)
    {
        $validated = $request->validate([
            'semaine' => 'required|integer|min:1|max:52',
            'formation' => 'required|string|max:255',
        ]);

        $semaine = $validated['semaine'];
        $formation = $validated['formation'];

        LogModel::create([
            'date' => now(),
            'type' => 'fetch-schedule',
            'state' => 'info',
            'message' => "Début de récupération pour la formation {$formation}, semaine {$semaine}."
        ]);

        if ($formation === "Licence STS Cyber") {
            try {
                $response = Http::timeout(60)->get("https://www.cyber.sts-dev.fr/api/publiee", [
                    'semaine' => $semaine,
                    'formation' => $formation,
                ]);

                if ($response->successful()) {
                    LogModel::create([
                        'date' => now(),
                        'type' => 'fetch-schedule',
                        'state' => 'succès',
                        'message' => "Appel à l'API Cyber réussi pour la formation {$formation}, semaine {$semaine}."
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'message' => "Les données pour la formation {$formation}, semaine {$semaine} ont été récupérées avec succès via l'API Cyber."
                    ], 200);
                } else {
                    LogModel::create([
                        'date' => now(),
                        'type' => 'fetch-schedule',
                        'state' => 'error',
                        'message' => "L'API Cyber a échoué (HTTP {$response->status()}) pour la formation {$formation}, semaine {$semaine}."
                    ]);

                    return response()->json([
                        'status' => 'error',
                        'message' => "L'API Cyber a échoué lors de la récupération des données pour la formation {$formation}, semaine {$semaine}.",
                        'details' => $response->body(),
                    ], 500);
                }
            } catch (\Exception $e) {
                LogModel::create([
                    'date' => now(),
                    'type' => 'fetch-schedule',
                    'state' => 'error',
                    'message' => "Erreur lors de l'appel à l'API Cyber pour la formation {$formation}, semaine {$semaine} : " . $e->getMessage()
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => "Une erreur s'est produite lors de l'appel à l'API Cyber.",
                    'details' => $e->getMessage(),
                ], 500);
            }
        }

        try {
            $process = new Process([
                '/usr/local/bin/php /home/gusa3095/sts-dev.fr/artisan',
                'schedule:fetch-and-store',
                $formation,
                $semaine
            ]);

            $process->setWorkingDirectory(base_path());

            $process->setTimeout(300);

            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            LogModel::create([
                'date' => now(),
                'type' => 'fetch-schedule',
                'state' => 'succès',
                'message' => "Emplois du temps pour la formation {$formation}, semaine {$semaine} récupérés avec succès."
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "Emplois du temps pour la formation {$formation}, semaine {$semaine} ont été récupérés et enregistrés avec succès."
            ], 200);

        } catch (ProcessFailedException $e) {
            LogModel::create([
                'date' => now(),
                'type' => 'fetch-schedule',
                'state' => 'error',
                'message' => "Erreur lors de l'exécution de la commande Artisan pour la formation {$formation}, semaine {$semaine} : " . $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => "Une erreur s'est produite lors de la récupération des emplois du temps.",
                'details' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            LogModel::create([
                'date' => now(),
                'type' => 'fetch-schedule',
                'state' => 'error',
                'message' => "Erreur inattendue pour la formation {$formation}, semaine {$semaine} : " . $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => "Une erreur inattendue s'est produite.",
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
