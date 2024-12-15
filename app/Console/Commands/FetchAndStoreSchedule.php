<?php

namespace App\Console\Commands;

use App\Models\Log;
use App\Models\Matiere;
use App\Models\Semaine;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchAndStoreSchedule extends Command
{
    protected $signature = 'schedule:fetch-and-store {section} {semaine=0}';
    protected $description = 'Récupère les emplois du temps et met à jour si des différences sont détectées, avec un paramètre semaine optionnel.';

    protected $dayLines = [
        'lundi' => [9, 10],
        'mardi' => [12, 13],
        'mercredi' => [15, 16],
        'jeudi' => [18, 19],
        'vendredi' => [21, 22]
    ];

    protected $baseHour = 8;

    public function handle()
    {
        $section = $this->argument('section');
        $semaineParam = (int)$this->argument('semaine');

        if ($semaineParam === 0) {
            $lastSemaine = Semaine::orderBy('numero', 'desc')->first();
            $nextWeekNumber = $lastSemaine ? $lastSemaine->numero + 1 : 1;
        } else {
            $nextWeekNumber = $semaineParam;
        }

        while (true) {
            $url = 'https://script.google.com/macros/s/AKfycbyx7HGWGEXZroeyDQM2jg7CgLST5rYEbO0VOJ6H3qP1OxVrgE8_EV4XveN3H1NRz1DA/exec';

            try {
                $response = Http::timeout(60)->get($url, [
                    'action' => 'api',
                    'week' => $nextWeekNumber,
                    'section' => $section
                ]);
            } catch (\Exception $e) {
                Log::create([
                    'date' => now(),
                    'type' => 'fetch-schedule',
                    'state' => 'error',
                    'message' => "Impossible d'appeler l'API (erreur co) semaine {$nextWeekNumber} : " . $e->getMessage()
                ]);
                break;
            }

            if ($response->failed()) {
                Log::create([
                    'date' => now(),
                    'type' => 'fetch-schedule',
                    'state' => 'error',
                    'message' => "L'API a échoué (HTTP {$response->status()}) sem. {$nextWeekNumber}"
                ]);
                break;
            }

            $data = $response->json();
            if (!$data) {
                Log::create([
                    'date' => now(),
                    'type' => 'fetch-schedule',
                    'state' => 'error',
                    'message' => "Réponse API non JSON valide sem. {$nextWeekNumber}"
                ]);
                break;
            }

            // Semaine non publiée ?
            if (isset($data['status']) && $data['status'] == 400) {
                $message = $data['message'] ?? "La semaine {$nextWeekNumber} n'est pas publiée.";
                Log::create([
                    'date' => now(),
                    'type' => 'fetch-schedule',
                    'state' => 'info',
                    'message' => $message
                ]);
                break;
            }

            $emplois = $this->convertToReadableJson($data);

            $this->storeEmploiDuTemps($emplois);

            Log::create([
                'date' => now(),
                'type' => 'fetch-schedule',
                'state' => 'succès',
                'message' => "Emploi du temps sem. {$emplois['semaine']['numero']} enregistré."
            ]);

            $this->info("Emploi du temps sem. {$emplois['semaine']['numero']} enregistré.");

            if ($semaineParam !== 0) {
                break;
            }

            $nextWeekNumber++;
        }

        return 0;
    }

    protected function convertToReadableJson($data)
    {
        $formation = $data['sectionName'] ?? 'Formation inconnue';
        $anneeScolaire = '2024-2025';

        if (empty($data['seances'])) {
            return [
                "semaine" => [
                    "numero" => intval($data['week']),
                    "dates" => $data['dates'] ?? ''
                ],
                "annee_scolaire" => $anneeScolaire,
                "formation" => $formation,
                "emploi_du_temps" => [],
                "total_heures" => 0,
                "par_option" => 0,
                "date_edition" => Carbon::now()->format('l d F \à H:i')
            ];
        }

        $allHStarts = array_map(fn($s) => $s['hStart'], $data['seances']);
        $datesHStart = array_map(fn($h) => Carbon::parse($h), $allHStarts);
        $minHStart = min($datesHStart);
        $mondayDate = $minHStart->copy()->startOfWeek(Carbon::MONDAY);

        $jourIndex = [
            'lundi' => 0,
            'mardi' => 1,
            'mercredi' => 2,
            'jeudi' => 3,
            'vendredi' => 4
        ];

        $emploiParJour = [];
        $totalHeures = 0;

        foreach ($data['seances'] as $seance) {
            $jour = $this->getDayFromCoord($seance['coord']);
            if (!$jour) continue;

            $dateCours = $mondayDate->copy()->addDays($jourIndex[$jour]);
            $realDate = $dateCours->format('Y-m-d');

            $start = $seance['start'];
            $width = $seance['width'];

            list($hDeb, $mDeb) = $this->convertStartToTime($start);
            list($hFin, $mFin) = $this->convertWidthToEndTime($start, $width);

            $heureDebut = sprintf("%dh%02d", $hDeb, $mDeb);
            $heureFin = sprintf("%dh%02d", $hFin, $mFin);

            $matiere = $seance['matiere'];
            $prof = $seance['enseignant'];
            $note = null;
            $salle = $seance['salle'] ?: null;

            if (stripos($seance['caption'], 'QCM') !== false) {
                $note = "QCM";
            } elseif (stripos($seance['caption'], 'Examen') !== false) {
                $note = "Examen";
            }

            $cours = [
                "heure_debut" => $heureDebut,
                "heure_fin" => $heureFin,
                "matiere" => $matiere,
                "professeur" => $prof
            ];
            if ($note) $cours["note"] = $note;
            if ($salle) $cours["salle"] = $salle;

            $startMin = $hDeb * 60 + $mDeb;
            $endMin = $hFin * 60 + $mFin;
            $duration = ($endMin - $startMin) / 60;
            $totalHeures += $duration;

            if (!isset($emploiParJour[$jour])) {
                $emploiParJour[$jour] = [
                    "jour" => $jour,
                    "date" => $realDate,
                    "cours" => []
                ];
            } else {
                $emploiParJour[$jour]["date"] = $realDate;
            }

            $emploiParJour[$jour]["cours"][] = $cours;
        }

        $joursOrdre = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'];
        $emploiFinal = [];
        foreach ($joursOrdre as $j) {
            if (isset($emploiParJour[$j])) {
                $emploiFinal[] = $emploiParJour[$j];
            }
        }

        return [
            "semaine" => [
                "numero" => intval($data['week']),
                "dates" => $data['dates'] ?? ''
            ],
            "annee_scolaire" => $anneeScolaire,
            "formation" => $formation,
            "emploi_du_temps" => $emploiFinal,
            "total_heures" => $totalHeures,
            "par_option" => $totalHeures,
            "date_edition" => Carbon::now()->format('l d F \à H:i')
        ];
    }

    protected function getDayFromCoord($coord)
    {
        preg_match_all('/(\d+)/', $coord, $matches);
        $lines = array_map('intval', $matches[0]);
        sort($lines);

        foreach ($this->dayLines as $day => $range) {
            sort($range);
            if ($lines == $range) {
                return $day;
            }
        }
        return null;
    }

    protected function convertStartToTime($start)
    {
        $minutes = $start * 30;
        $h = $this->baseHour + floor($minutes / 60);
        $m = $minutes % 60;
        return [$h, $m];
    }

    protected function convertWidthToEndTime($start, $width)
    {
        $startMinutes = $start * 30;
        $endMinutes = $startMinutes + ($width * 30);

        $h = $this->baseHour + floor($endMinutes / 60);
        $m = $endMinutes % 60;
        return [$h, $m];
    }

    protected function storeEmploiDuTemps(array $emplois)
    {
        $emploisForComparison = $emplois;

        unset($emploisForComparison['date_edition']);

        if (isset($emploisForComparison['emploi_du_temps'])) {
            foreach ($emploisForComparison['emploi_du_temps'] as $jourIndex => $jourData) {
                if (isset($jourData['cours'])) {
                    foreach ($jourData['cours'] as $coursIndex => $coursData) {
                        if (isset($coursData['salle'])) {
                            unset($emploisForComparison['emploi_du_temps'][$jourIndex]['cours'][$coursIndex]['salle']);
                        }
                    }
                }
            }
        }

        $newJson = json_encode($emploisForComparison, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $existing = Semaine::where('numero', $emplois['semaine']['numero'])
            ->where('formation', $emplois['formation'])
            ->first();

        if ($existing) {
            $oldEmplois = json_decode($existing->json_data, true);

            unset($oldEmplois['date_edition']);
            if (isset($oldEmplois['emploi_du_temps'])) {
                foreach ($oldEmplois['emploi_du_temps'] as $jIndex => $jData) {
                    if (isset($jData['cours'])) {
                        foreach ($jData['cours'] as $cIndex => $cData) {
                            if (isset($cData['salle'])) {
                                unset($oldEmplois['emploi_du_temps'][$jIndex]['cours'][$cIndex]['salle']);
                            }
                        }
                    }
                }
            }

            $oldJson = json_encode($oldEmplois, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            if ($oldJson === $newJson) {
                return;
            } else {
                foreach ($existing->jours as $jour) {
                    $jour->cours()->delete();
                    $jour->delete();
                }

                try {
                    $dateEdition = Carbon::createFromFormat('l d F \à H:i', $emplois['date_edition']);
                } catch (\Exception $e) {
                    $dateEdition = Carbon::now();
                }

                $existing->update([
                    'json_data' => json_encode($emplois, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                    'dates' => $emplois['semaine']['dates'],
                    'annee_scolaire' => $emplois['annee_scolaire'],
                    'formation' => $emplois['formation'],
                    'total_heures' => $emplois['total_heures'],
                    'par_option' => $emplois['par_option'],
                    'date_edition' => $dateEdition,
                ]);

                $this->insertJoursEtCours($existing, $emplois);
                return;
            }
        } else {
            try {
                $dateEdition = Carbon::createFromFormat('l d F \à H:i', $emplois['date_edition']);
            } catch (\Exception $e) {
                $dateEdition = Carbon::now();
            }

            $semaine = Semaine::create([
                'json_data' => json_encode($emplois, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'numero' => $emplois['semaine']['numero'],
                'dates' => $emplois['semaine']['dates'],
                'annee_scolaire' => $emplois['annee_scolaire'],
                'formation' => $emplois['formation'],
                'total_heures' => $emplois['total_heures'],
                'par_option' => $emplois['par_option'],
                'date_edition' => $dateEdition,
            ]);

            $this->insertJoursEtCours($semaine, $emplois);
        }
    }


    protected function insertJoursEtCours($semaine, $emplois)
    {
        $formatTime = function ($heure) {
            preg_match('/(\d+)h(\d+)/', $heure, $matches);
            $h = $matches[1];
            $m = $matches[2];
            return sprintf('%02d:%02d:00', $h, $m);
        };

        foreach ($emplois['emploi_du_temps'] as $jourData) {
            $jourDate = null;
            if (isset($jourData['date']) && $jourData['date']) {
                $jourDate = Carbon::parse($jourData['date'])->format('Y-m-d');
            }

            $jour = $semaine->jours()->create([
                'jour' => $jourData['jour'],
                'date' => $jourDate
            ]);

            foreach ($jourData['cours'] as $coursData) {
                $randomColor = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                $matiere = Matiere::firstOrCreate(
                    ['name' => $coursData['matiere']],
                    ['long_name' => $coursData['matiere'], 'color' => $randomColor]
                );

                $jour->cours()->create([
                    'heure_debut' => $formatTime($coursData['heure_debut']),
                    'heure_fin' => $formatTime($coursData['heure_fin']),
                    'matiere_nom' => $coursData['matiere'],
                    'matiere_id' => $matiere->id,
                    'professeur' => $coursData['professeur'] ?? null,
                    'salle' => $coursData['salle'] ?? null,
                ]);
            }
        }
    }
}
