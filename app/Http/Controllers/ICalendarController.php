<?php

namespace App\Http\Controllers;

use App\Models\Semaine;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

class ICalendarController extends Controller
{
    protected $courseWeeks;
    protected $detailedWeekAdded = false;

    public function __construct()
    {
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

        $startDate = Carbon::parse('2024-08-26');
        $endDate = Carbon::parse('2025-08-31');

        $weeks = CarbonPeriod::create($startDate, '1 week', $endDate);

        foreach ($weeks as $weekStart) {
            if (in_array($weekStart->format('Y-m-d'), $this->courseWeeks)) {
                $this->addCourseWeek($calendar, $weekStart);
            } else {
                $this->addAlternanceWeek($calendar, $weekStart);
            }
        }

        return response($calendar->get())
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="sts-dev-calendar-alternance.ics"');
    }

    private function addCourseWeek($calendar, $weekStart)
    {
        $semaine = Semaine::latest()->first();

        if ($semaine && $semaine->json_data && !$this->detailedWeekAdded) {
            $data = json_decode($semaine->json_data, true);
            $this->addDetailedCourseWeek($calendar, $weekStart, $data);
            $this->detailedWeekAdded = true;
        } else {
            $this->addDefaultCourseWeek($calendar, $weekStart);
        }
    }

    private function addDetailedCourseWeek($calendar, $weekStart, $data)
    {
        foreach ($data['emploi_du_temps'] as $index => $jourData) {
            $date = $weekStart->copy()->addDays($index)->setTimezone('Europe/Paris');

            if (isset($jourData['cours']) && is_array($jourData['cours'])) {
                foreach ($jourData['cours'] as $coursData) {
                    if (isset($coursData['heure_debut']) && isset($coursData['heure_fin']) && isset($coursData['matiere'])) {
                        $start = $date->copy()->setTimeFromTimeString($this->formatTime($coursData['heure_debut']));
                        $end = $date->copy()->setTimeFromTimeString($this->formatTime($coursData['heure_fin']));

                        $description = "";
                        if (isset($coursData['professeur'])) {
                            $description .= "Professeur: {$coursData['professeur']}\n";
                        }
                        if (isset($coursData['salle'])) {
                            $description .= "Salle: {$coursData['salle']}";
                        }
                        $description = trim($description);

                        $event = Event::create()
                            ->name($coursData['matiere'])
                            ->description($description)
                            ->startsAt($start)
                            ->endsAt($end);

                        $calendar->event($event);
                    }
                }
            }
        }
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
        for ($i = 0; $i < 5; $i++) {
            $date = $weekStart->copy()->addDays($i)->setTimezone('Europe/Paris');

            $event = Event::create()
                ->name('En cours')
                ->description('Semaine de formation')
                ->startsAt($date->copy()->setTime(8, 0))
                ->endsAt($date->copy()->setTime(17, 0));

            $calendar->event($event);
        }
    }

    private function addAlternanceWeek($calendar, $weekStart)
    {
        for ($i = 0; $i < 5; $i++) {
            $date = $weekStart->copy()->addDays($i)->setTimezone('Europe/Paris');

            $event = Event::create()
                ->name('En alternance')
                ->description('Période en entreprise')
                ->startsAt($date->copy()->setTime(8, 0))
                ->endsAt($date->copy()->setTime(19, 0));

            $calendar->event($event);
        }
    }
}
