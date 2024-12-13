<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

class ICalendarController extends Controller
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
        // Trouver le jour correspondant au lundi de la semaine
        $jourLundi = \App\Models\Jour::where('date', $weekStart->format('Y-m-d'))->first();

        if ($jourLundi) {
            // Récupérer la semaine associée à ce jour
            $semaine = $jourLundi->semaine;


            // Récupérer les jours de la semaine
            $jours = $semaine->jours()->with('cours')->get();

            if ($jours->isEmpty()) {
            }

            foreach ($jours as $jour) {

                $this->addCoursesForDay($calendar, $weekStart, $jour);
            }
        } else {
            $this->addDefaultCourseWeek($calendar, $weekStart);
        }
    }

    private function addCoursesForDay($calendar, $weekStart, $jour)
    {
        // Définir le fuseau horaire utilisé dans la base de données (par exemple, UTC)
        $timezone = 'Europe/Paris'; // Assurez-vous que c'est le fuseau horaire correct pour vos données

        // Calculer la date du jour en fonction du lundi de la semaine
        $jourDate = Carbon::parse($weekStart)->addDays($this->getDayIndex($jour->jour))->setTimezone($timezone);


        foreach ($jour->cours as $cours) {
            // Convertir les heures de début et de fin au fuseau horaire correct
            $start = Carbon::parse($jourDate->format('Y-m-d') . ' ' . $cours->heure_debut, $timezone);
            $end = Carbon::parse($jourDate->format('Y-m-d') . ' ' . $cours->heure_fin, $timezone);

            $description = "";
            if ($cours->professeur) {
                $description .= "Professeur: {$cours->professeur}\n";
            }
            if ($cours->salle) {
                $description .= "Salle: {$cours->salle}";
            }
            $description = trim($description);

            $event = Event::create()
                ->name($cours->matiere->name)
                ->description($description)
                ->startsAt($start)
                ->endsAt($end);

            $calendar->event($event);
        }
    }

    private function getDayIndex($jourName)
    {
        $days = [
            'lundi' => 0,
            'mardi' => 1,
            'mercredi' => 2,
            'jeudi' => 3,
            'vendredi' => 4,
            'samedi' => 5,
            'dimanche' => 6,
        ];

        return $days[strtolower($jourName)] ?? 0;
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

    public function feedWithoutAlternance(Request $request)
    {
        $calendar = Calendar::create('Emploi du temps STS Dev - Formation')
            ->refreshInterval(60)
            ->productIdentifier('//Votre École//STS Dev Calendrier Formation//FR');

        $startDate = Carbon::parse('2024-08-26');
        $endDate = Carbon::parse('2025-08-31');

        $weeks = CarbonPeriod::create($startDate, '1 week', $endDate);

        foreach ($weeks as $weekStart) {
            if (in_array($weekStart->format('Y-m-d'), $this->courseWeeks)) {
                $this->addCourseWeek($calendar, $weekStart);
            }
        }

        return response($calendar->get())
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="sts-dev-calendar-formation.ics"');
    }
}
