<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

class ICalendarController extends Controller
{
    protected $edtController;
    protected $courseWeeks;

    public function __construct(EdtController $edtController)
    {
        $this->edtController = $edtController;
        $this->courseWeeks = [
            '2024-08-26', '2024-09-09', '2024-09-30', '2024-10-21', '2024-11-04',
            '2024-11-25', '2024-12-09', '2025-01-06', '2025-01-27', '2025-02-17',
            '2025-03-10', '2025-03-31', '2025-04-21', '2025-05-05', '2025-06-02',
            '2025-06-16', '2025-06-30'
        ];
    }

    public function feed(Request $request)
    {
        $calendar = Calendar::create('Emploi du temps STS Dev - Alternance')
            ->refreshInterval(60)
            ->productIdentifier('//Votre École//STS Dev Calendrier Alternance//FR');

        $edtData = $this->edtController->getData()->getContent();
        $data = json_decode($edtData, true);

        $this->addAllWeeksToCalendar($calendar, $data);

        return response($calendar->get())
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="sts-dev-calendar-alternance.ics"');
    }

    private function addAllWeeksToCalendar($calendar, $data)
    {
        $startDate = Carbon::parse('2024-08-26');
        $endDate = Carbon::parse('2025-08-31');

        $weeks = CarbonPeriod::create($startDate, '1 week', $endDate);

        foreach ($weeks as $weekStart) {
            if (in_array($weekStart->format('Y-m-d'), $this->courseWeeks)) {
                $this->addCourseWeek($calendar, $weekStart, $data);
            } else {
                $this->addAlternanceWeek($calendar, $weekStart);
            }
        }
    }

    private function addCourseWeek($calendar, $weekStart, $data)
    {
        if ($data && isset($data['emploi_du_temps']) && $this->isWeekDataAvailable($data, $weekStart)) {
            $this->addActualCourseWeek($calendar, $weekStart, $data);
        } else {
            $this->addDefaultCourseWeek($calendar, $weekStart);
        }
    }

    private function isWeekDataAvailable($data, $weekStart)
    {
        if (!isset($data['emploi_du_temps'][0]['date'])) {
            return false;
        }
        $dataWeekStart = Carbon::createFromFormat('d/m/Y', $data['emploi_du_temps'][0]['date'])->startOfWeek();
        return $dataWeekStart->eq($weekStart);
    }

    private function addActualCourseWeek($calendar, $weekStart, $data)
    {
        foreach ($data['emploi_du_temps'] as $index => $jourData) {
            if (isset($jourData['jour'])) {
                $dayOfWeek = $this->getDayOfWeek($jourData['jour']);
            } else {
                $dayOfWeek = $index + 1; // Assuming the array starts with Monday
            }

            $currentDate = $weekStart->copy()->addDays($dayOfWeek - 1);

            if (isset($jourData['cours']) && is_array($jourData['cours'])) {
                foreach ($jourData['cours'] as $coursData) {
                    if (isset($coursData['heure_debut']) && isset($coursData['heure_fin']) && isset($coursData['matiere'])) {
                        $start = $currentDate->copy()->setTimeFromTimeString($this->formatTime($coursData['heure_debut']));
                        $end = $currentDate->copy()->setTimeFromTimeString($this->formatTime($coursData['heure_fin']));

                        $description = "";
                        if (isset($coursData['professeur'])) {
                            $description .= "Professeur: {$coursData['professeur']}\n";
                        }
                        if (isset($coursData['salle'])) {
                            $description .= "Salle: {$coursData['salle']}";
                        }

                        $event = Event::create()
                            ->name($coursData['matiere'])
                            ->description(trim($description))
                            ->startsAt($start)
                            ->endsAt($end);

                        $calendar->event($event);
                    }
                }
            }
        }
    }

    private function getDayOfWeek($jour)
    {
        $jours = [
            'lundi' => 1,
            'mardi' => 2,
            'mercredi' => 3,
            'jeudi' => 4,
            'vendredi' => 5,
            'samedi' => 6,
            'dimanche' => 7
        ];

        return $jours[strtolower($jour)] ?? 1; // Default to Monday if unknown
    }

    private function formatTime($time)
    {
        $parts = explode('h', $time);
        $hours = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
        $minutes = isset($parts[1]) ? str_pad($parts[1], 2, '0', STR_PAD_RIGHT) : '00';
        return "$hours:$minutes:00";
    }

    private function addDefaultCourseWeek($calendar, $weekStart)
    {
        for ($dayOffset = 0; $dayOffset < 5; $dayOffset++) {
            $currentDate = $weekStart->copy()->addDays($dayOffset);

            $event = Event::create()
                ->name('En cours')
                ->description('Semaine de formation')
                ->startsAt($currentDate->copy()->setTime(8, 0))
                ->endsAt($currentDate->copy()->setTime(17, 0));

            $calendar->event($event);
        }
    }

    private function addAlternanceWeek($calendar, $weekStart)
    {
        for ($dayOffset = 0; $dayOffset < 5; $dayOffset++) {
            $currentDate = $weekStart->copy()->addDays($dayOffset);

            $event = Event::create()
                ->name('En alternance')
                ->description('Période en entreprise')
                ->startsAt($currentDate->copy()->setTime(8, 0))
                ->endsAt($currentDate->copy()->setTime(19, 0));

            $calendar->event($event);
        }
    }
}
