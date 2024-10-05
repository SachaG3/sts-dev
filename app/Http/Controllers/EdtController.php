<?php

namespace App\Http\Controllers;

use App\Models\Jour;
use App\Models\Matiere;
use App\Models\Semaine;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class EdtController extends Controller
{
    protected $courseWeeks;

    public function __construct()
    {
        $this->courseWeeks = [
            '2024-08-26', '2024-09-09', '2024-09-30', '2024-10-21', '2024-11-04',
            '2024-11-25', '2024-12-09', '2025-01-06', '2025-01-27', '2025-02-17',
            '2025-03-10', '2025-03-31', '2025-04-21', '2025-05-05', '2025-06-02',
            '2025-06-16', '2025-06-30'
        ];
    }

    public function show()
    {
        return view('edt.home');
    }

    public function getData()
    {
        $currentDate = Carbon::now('Europe/Paris')->startOfWeek(Carbon::MONDAY);

        $lundi = Jour::with('semaine.jours.cours.matiere')
            ->where('date', $currentDate->format('Y-m-d'))
            ->first();

        if ($lundi && $lundi->semaine) {
            $weekData = $this->generateWeekData($currentDate, collect([$lundi->semaine]));
            return response()->json(['weeks' => [$weekData]]);
        }

        return response()->json(['weeks' => []]);
    }

    private function generateWeekData($weekStart, $allSemaines)
    {
        $courseWeeksSet = array_flip($this->courseWeeks);

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
                    $dayData = [
                        'date' => $formattedDate,
                        'cours' => []
                    ];

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

                    $weekData['emploi_du_temps'][] = $dayData;
                }
            } else {
                $weekData['emploi_du_temps'] = $this->getDefaultCourseWeek($weekStart);
            }
        } else {
            $weekData['emploi_du_temps'] = $this->getAlternanceWeek($weekStart);
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


    private function getDefaultCourseWeek($weekStart)
    {
        $week = [];
        for ($i = 0; $i < 5; $i++) {
            $currentDate = $weekStart->copy()->addDays($i);
            $week[] = [
                'date' => $currentDate->format('d/m/Y'),
                'cours' => [
                    [
                        'matiere' => 'En cours',
                        'matiere_name' => 'En cours',
                        'color' => '#dd8fe8',
                        'heure_debut' => '08h00',
                        'heure_fin' => '17h30',
                        'allDay' => false,
                    ]
                ]
            ];
        }
        return $week;
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


}
