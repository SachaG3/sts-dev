<?php

namespace App\Http\Controllers;

use App\Models\Cours;
use App\Models\Matiere;
use App\Models\Semaine;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class EdtController extends Controller
{
    protected $courseWeeks;
    protected $holidays;

    public function __construct()
    {
        $this->courseWeeks = [
            '2024-08-26', '2024-09-09', '2024-09-30', '2024-10-21', '2024-11-04',
            '2024-11-25', '2024-12-09', '2025-01-06', '2025-01-27', '2025-02-17',
            '2025-03-10', '2025-03-31', '2025-04-21', '2025-05-05', '2025-06-02',
            '2025-06-16', '2025-06-30'
        ];
        $this->holidays = [
            '2024-11-01', '2024-11-11', '2024-12-25', '2025-01-01', '2025-04-21',
            '2025-05-01', '2025-05-08', '2025-05-29', '2025-06-09', '2025-07-14',
            '2025-08-15',
        ];
    }

    public function show()
    {
        return view('edt.home');
    }

    public function edit($id)
    {
        $cours = Cours::findOrFail($id);
        $matieres = Matiere::all();

        return view('cours.edit', compact('cours', 'matieres'));
    }

    public function update(Request $request, $id)
    {
        $cours = Cours::findOrFail($id);

        $request->validate([
            'matiere_id' => 'required',
            'enseignant_id' => 'required',
            'salle' => 'required|string|max:255',
        ]);

        $cours->matiere_id = $request->matiere_id;
        $cours->enseignant_id = $request->enseignant_id;
        $cours->salle = $request->salle;
        $cours->save();

        return redirect()->route('cours.index')->with('success', 'Cours mis à jour avec succès');
    }

    public function getData()
    {
        $currentDate = Carbon::now('Europe/Paris');

        if ($currentDate->isWeekend()) {
            $currentDate = $currentDate->next(Carbon::MONDAY);
        } else {
            $currentDate = $currentDate->startOfWeek(Carbon::MONDAY);
        }

        $allSemaines = Semaine::with('jours.cours.matiere')->get();

        $weekData = $this->generateWeekData($currentDate, $allSemaines);

        return response()->json(['weeks' => [$weekData]]);
    }


    private function generateWeekData($weekStart, $allSemaines)
    {
        $courseWeeksSet = array_flip($this->courseWeeks);
        $holidaysSet = array_flip($this->holidays); // Conversion en ensemble pour une recherche rapide

        $weekData = [
            'start_date' => $weekStart->format('Y-m-d'),
            'type' => 'alternance',
            'emploi_du_temps' => []
        ];

        if (isset($courseWeeksSet[$weekStart->format('Y-m-d')])) {
            $weekData['type'] = 'cours';

            $weekSemaine = $allSemaines->first(function ($semaine) use ($weekStart) {
                return $this->isWeekDataAvailable($semaine, $weekStart);
            });

            if ($weekSemaine) {
                foreach ($weekSemaine->jours as $jour) {
                    $formattedDate = Carbon::parse($jour->date)->format('d/m/Y');
                    $dayCarbon = Carbon::parse($jour->date);
                    $dayData = [
                        'date' => $formattedDate,
                        'cours' => []
                    ];

                    // Vérifier si le jour est un jour férié
                    if (isset($holidaysSet[$dayCarbon->format('Y-m-d')])) {
                        $dayData['cours'][] = [
                            'matiere' => 'Jour férié',
                            'matiere_name' => 'Jour férié',
                            'color' => '#FF0000', // Couleur rouge pour les jours fériés
                            'heure_debut' => null,
                            'heure_fin' => null,
                            'professeur' => null,
                            'salle' => null,
                            'allDay' => false,
                        ];
                    } else {
                        foreach ($jour->cours as $cours) {
                            $matiere = $cours->matiere;

                            $dayData['cours'][] = [
                                'matiere' => $matiere->name,
                                'matiere_name' => $matiere->long_name,
                                'color' => $matiere->color,
                                'heure_debut' => $cours->heure_debut,
                                'heure_fin' => $cours->heure_fin,
                                'professeur' => $cours->professeur,
                                'salle' => $cours->salle,
                                'allDay' => $cours->allDay ?? false,
                            ];
                        }
                    }

                    $weekData['emploi_du_temps'][] = $dayData;
                }
            } else {
                $weekData['emploi_du_temps'] = $this->getDefaultCourseWeek($weekStart, $holidaysSet);

            }
        } else {
            foreach (range(0, 4) as $i) {
                $currentDate = $weekStart->copy()->addDays($i);
                $formattedDate = $currentDate->format('d/m/Y');
                $dayData = [
                    'date' => $formattedDate,
                    'cours' => []
                ];

                // Vérifier si le jour est un jour férié
                if (isset($holidaysSet[$currentDate->format('Y-m-d')])) {
                    $dayData['cours'][] = [
                        'matiere' => 'Jour férié',
                        'matiere_name' => 'Jour férié',
                        'color' => '#FF0000',
                        'heure_debut' => '08h00',
                        'heure_fin' => '17h30',
                        'professeur' => null,
                        'salle' => null,
                        'allDay' => false,
                    ];
                } else {
                    $dayData['cours'][] = [
                        'matiere' => 'En alternance',
                        'matiere_name' => 'En alternance',
                        'color' => '#c8cbcd',
                        'heure_debut' => '08h00',
                        'heure_fin' => '17h30',
                        'allDay' => false,
                    ];
                }

                $weekData['emploi_du_temps'][] = $dayData;
            }
        }

        return $weekData;
    }


    private function isWeekDataAvailable($semaine, $weekStart)
    {
        $jours = $semaine->jours;

        if ($jours->isEmpty()) {
            return false;
        }

        $firstDayDate = $jours->first()->date;

        if (!$firstDayDate) {
            return false;
        }

        $dataWeekStart = Carbon::parse($firstDayDate)->startOfWeek();

        $dataWeekStart->setTimezone('Europe/Paris')->startOfDay();
        $weekStart->setTimezone('Europe/Paris')->startOfDay();

        return $dataWeekStart->eq($weekStart);
    }


    private function getDefaultCourseWeek($weekStart, $holidaysSet)
    {
        $week = [];
        for ($i = 0; $i < 5; $i++) {
            $currentDate = $weekStart->copy()->addDays($i);
            $formattedDate = $currentDate->format('d/m/Y');
            $dayData = [
                'date' => $formattedDate,
                'cours' => []
            ];

            // Vérifier si le jour est un jour férié
            if (isset($holidaysSet[$currentDate->format('Y-m-d')])) {
                $dayData['cours'][] = [
                    'matiere' => 'Jour férié',
                    'matiere_name' => 'Jour férié',
                    'color' => '#FF0000',
                    'heure_debut' => '08h00',
                    'heure_fin' => '17h30',
                    'professeur' => null,
                    'salle' => null,
                    'allDay' => false,
                ];
            } else {
                $dayData['cours'][] = [
                    'matiere' => 'En cours',
                    'matiere_name' => 'En cours',
                    'color' => '#dd8fe8',
                    'heure_debut' => '08h00',
                    'heure_fin' => '17h30',
                    'allDay' => false,
                ];
            }

            $week[] = $dayData;
        }
        return $week;
    }


    public function showInputForm()
    {
        return view('edt.input');
    }

    public function storeEdt(Request $request)
    {
        $request->validate([
            'json_data' => 'required|json',
        ]);

        $data = json_decode($request->json_data, true);

        try {
            $dateEdition = Carbon::createFromFormat('l d F \à H:i', $data['date_edition']);
        } catch (\Exception $e) {
            $dateEdition = Carbon::now();
        }

        $semaine = Semaine::create([
            'json_data' => $request->json_data,
            'numero' => $data['semaine']['numero'],
            'dates' => $data['semaine']['dates'],
            'annee_scolaire' => $data['annee_scolaire'],
            'formation' => $data['formation'],
            'total_heures' => $data['total_heures'],
            'par_option' => $data['par_option'],
            'date_edition' => $dateEdition,
        ]);

        foreach ($data['emploi_du_temps'] as $jourData) {
            $jour = $semaine->jours()->create([
                'jour' => $jourData['jour'],
                'date' => isset($jourData['date']) ? Carbon::createFromFormat('d/m/Y', $jourData['date'])->format('Y-m-d') : null,
            ]);

            foreach ($jourData['cours'] as $coursData) {
                $matiere = Matiere::firstOrCreate(
                    ['name' => $coursData['matiere']],
                    ['long_name' => $coursData['matiere']]
                );

                $jour->cours()->create([
                    'heure_debut' => $this->formatTime($coursData['heure_debut']),
                    'heure_fin' => $this->formatTime($coursData['heure_fin']),
                    'matiere_nom' => $coursData['matiere'],
                    'matiere_id' => $matiere->id,
                    'professeur' => $coursData['professeur'] ?? null,
                    'salle' => $coursData['salle'] ?? null,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Emploi du temps enregistré avec succès.');
    }

    private function formatTime($time)
    {
        $parts = explode('h', $time);
        $hours = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
        $minutes = isset($parts[1]) ? str_pad($parts[1], 2, '0', STR_PAD_RIGHT) : '00';
        return "$hours:$minutes:00";
    }

    public function getRemainingWeeks()
    {
        try {
            $allSemaines = Semaine::with('jours.cours.matiere')->get();  // Charge la relation matiere

            $startDate = Carbon::parse('2024-08-26');
            $endDate = Carbon::parse('2025-08-31');

            $weeks = CarbonPeriod::create($startDate, '1 week', $endDate);
            $allWeeks = [];

            foreach ($weeks as $weekStart) {

                $weekData = $this->generateWeekData($weekStart, $allSemaines);
                $allWeeks[] = $weekData;
            }

            return response()->json(['weeks' => $allWeeks]);
        } catch (\Exception $e) {

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getAlternanceWeek($weekStart)
    {
        $week = [];
        for ($i = 0; $i < 5; $i++) {
            $currentDate = $weekStart->copy()->addDays($i);
            $week[] = [
                'date' => $currentDate->format('d/m/Y'),
                'cours' => [
                    [
                        'matiere' => 'En alternance',
                        'matiere_name' => 'En alternance',
                        'color' => '#c8cbcd',
                        'heure_debut' => '08h00',
                        'heure_fin' => '17h30',
                        'allDay' => false,
                    ]
                ]
            ];
        }
        return $week;
    }


}
