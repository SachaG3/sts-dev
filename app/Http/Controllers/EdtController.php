<?php

namespace App\Http\Controllers;

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
        $allSemaines = Semaine::all();
        $currentDate = Carbon::now()->startOfWeek();
        $startDate = Carbon::parse('2024-08-26');
        $endDate = Carbon::parse('2025-08-31');

        $weeks = CarbonPeriod::create($startDate, '1 week', $endDate);
        $allWeeks = [];

        foreach ($weeks as $weekStart) {
            if ($weekStart->eq($currentDate)) {
                $weekData = $this->generateWeekData($weekStart, $allSemaines);
                return response()->json(['weeks' => [$weekData]]);
            }
        }

        return response()->json(['weeks' => []]); // Retourne vide si aucune semaine n'est trouvée
    }

    private function generateWeekData($weekStart, $allSemaines)
    {
        $weekData = [
            'start_date' => $weekStart->format('Y-m-d'),
            'type' => 'alternance',
            'emploi_du_temps' => []
        ];

        if (in_array($weekStart->format('Y-m-d'), $this->courseWeeks)) {
            $weekData['type'] = 'cours';

            $weekSemaine = $allSemaines->first(function ($semaine) use ($weekStart) {
                return $this->isWeekDataAvailable($semaine, $weekStart);
            });

            if ($weekSemaine) {
                $weekData['emploi_du_temps'] = json_decode($weekSemaine->json_data, true)['emploi_du_temps'];
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
        $data = json_decode($semaine->json_data, true);

        if (!isset($data['emploi_du_temps'][0]['date'])) {
            return false;
        }
        $dataWeekStart = Carbon::createFromFormat('d/m/Y', $data['emploi_du_temps'][0]['date'])->startOfWeek();
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
                        'heure_debut' => '08h00',
                        'heure_fin' => '17h00',
                        'professeur' => 'Non spécifié',
                        'salle' => 'Non spécifiée',
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
                        'heure_debut' => '08h00',
                        'heure_fin' => '19h00',
                        'professeur' => 'En entreprise',
                        'salle' => 'Entreprise',
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
            // Si le format de date n'est pas reconnu, utilisez la date actuelle
            $dateEdition = Carbon::now();
        }

        $semaine = Semaine::create([
            'numero' => $data['semaine']['numero'],
            'dates' => $data['semaine']['dates'],
            'annee_scolaire' => $data['annee_scolaire'],
            'formation' => $data['formation'],
            'json_data' => $request->json_data,
            'total_heures' => $data['total_heures'],
            'par_option' => $data['par_option'],
            'date_edition' => $dateEdition,
            'allDay' => false,
        ]);

        foreach ($data['emploi_du_temps'] as $jourData) {
            $jour = $semaine->jours()->create([
                'jour' => $jourData['jour'],
                'date' => isset($jourData['date']) ? Carbon::createFromFormat('d/m/Y', $jourData['date'])->format('Y-m-d') : null,
            ]);

            foreach ($jourData['cours'] as $coursData) {
                $jour->cours()->create([
                    'heure_debut' => $this->formatTime($coursData['heure_debut']),
                    'heure_fin' => $this->formatTime($coursData['heure_fin']),
                    'matiere' => $coursData['matiere'],
                    'professeur' => $coursData['professeur'] ?? null,
                    'salle' => $coursData['salle'] ?? null,
                    'allDay' => false,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Emploi du temps enregistré avec succès.');
    }

    private function formatTime($time)
    {
        // Convertit le format "8h30" en "08:30:00"
        $parts = explode('h', $time);
        $hours = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
        $minutes = isset($parts[1]) ? str_pad($parts[1], 2, '0', STR_PAD_RIGHT) : '00';
        return "$hours:$minutes:00";
    }

    public function getRemainingWeeks()
    {
        $allSemaines = Semaine::all();
        $currentDate = Carbon::now()->startOfWeek();
        $startDate = Carbon::parse('2024-08-26');
        $endDate = Carbon::parse('2025-08-31');

        $weeks = CarbonPeriod::create($startDate, '1 week', $endDate);
        $allWeeks = [];

        foreach ($weeks as $weekStart) {
            if ($weekStart->ne($currentDate)) {
                $weekData = $this->generateWeekData($weekStart, $allSemaines);
                $allWeeks[] = $weekData;
            }
        }

        return response()->json(['weeks' => $allWeeks]);
    }

}
