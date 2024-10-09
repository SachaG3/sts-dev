@php
    $futureAssignments = collect($assignments)->filter(function ($assignmentsForWeek, $weekNumber) {
        $year = substr($weekNumber, 0, 4);
        $week = substr($weekNumber, 4);
        $monday = \Carbon\Carbon::now()->setISODate($year, $week)->startOfWeek();
        $friday = $monday->copy()->addDays(4);

        $today = \Carbon\Carbon::now()->startOfDay();
        return $friday->greaterThanOrEqualTo($today);
    });

    $pastAssignments = collect($assignments)->filter(function ($assignmentsForWeek, $weekNumber) {
        $year = substr($weekNumber, 0, 4);
        $week = substr($weekNumber, 4);
        $monday = \Carbon\Carbon::now()->setISODate($year, $week)->startOfWeek();
        $friday = $monday->copy()->addDays(4);

        $today = \Carbon\Carbon::now()->startOfDay();
        return $friday->lessThan($today);
    });
@endphp

@foreach ($futureAssignments as $weekNumber => $assignmentsForWeek)
    @php
        $year = substr($weekNumber, 0, 4);
        $week = substr($weekNumber, 4);
        $monday = \Carbon\Carbon::now()->setISODate($year, $week)->startOfWeek();
        $friday = $monday->copy()->addDays(4);
        $formattedWeek = $monday->format('d/m') . ' au ' . $friday->format('d/m');
    @endphp

    <div class="mb-6">
        <h3 class="text-lg font-bold text-blue-600">Semaine du {{ $formattedWeek }}</h3>
        <ul class="list-disc list-inside mt-2">
            @foreach ($assignmentsForWeek as $assignment)
                <li class="p-2 border rounded-lg bg-gray-50 mb-2 shadow">
                    <strong>{{ $assignment->matiere->name }}</strong>
                    <p>{{ $assignment->description }}</p>
                </li>
            @endforeach
        </ul>
    </div>
@endforeach

<!-- Affichage des semaines passées -->
@foreach ($pastAssignments as $weekNumber => $assignmentsForWeek)
    @php
        $year = substr($weekNumber, 0, 4);
        $week = substr($weekNumber, 4);
        $monday = \Carbon\Carbon::now()->setISODate($year, $week)->startOfWeek();
        $friday = $monday->copy()->addDays(4);
        $formattedWeek = $monday->format('d/m') . ' au ' . $friday->format('d/m');
    @endphp

    <div class="mb-6">
        <h3 class="text-lg font-bold text-gray-600">Semaine du {{ $formattedWeek }} (Passée)</h3>
        <ul class="list-disc list-inside mt-2">
            @foreach ($assignmentsForWeek as $assignment)
                <li class="p-2 border rounded-lg bg-gray-50 mb-2 shadow">
                    <strong>{{ $assignment->matiere->name }}</strong>
                    <p>{{ $assignment->description }}</p>
                </li>
            @endforeach
        </ul>
    </div>
@endforeach
