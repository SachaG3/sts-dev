<?php

namespace App\Http\Controllers;

use App\Models\Log as LogModel;
use Illuminate\Http\Request;
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
        try {
            if ($formation === "Licence STS Cyber") {
                $phpPath = '/usr/local/bin/php';
                $artisanPath = '/home/gusa3095/cyber.sts-dev.fr/artisan';
                $workingDirectory = '/home/gusa3095/cyber.sts-dev.fr';
            } else {
                $phpPath = '/usr/local/bin/php';
                $artisanPath = '/home/gusa3095/sts-dev.fr/artisan';
                $workingDirectory = '/home/gusa3095/sts-dev.fr';
            }

            $process = new Process([
                $phpPath,
                $artisanPath,
                'schedule:fetch-and-store',
                $formation,
                $semaine
            ]);

            $process->setWorkingDirectory($workingDirectory);

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
