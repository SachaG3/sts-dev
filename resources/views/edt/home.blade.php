@extends("include/app")
@section('content')
    <body class="bg-base-100 min-h-screen">
    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold mb-4">Emploi du temps - Licence STS Dev</h1>
        <div class="mb-4">
            <button id="exportButton" class="btn btn-primary">Exporter pour Google Calendar</button>
            <a href="{{ route('calendar.feed') }}" class="btn btn-secondary ml-2">S'abonner au calendrier</a>
        </div>
        <div id="calendar" class="bg-base-200 p-4 rounded-lg shadow-lg"></div>
    </div>
    </body>
@endsection

@section('script')
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.css' rel='stylesheet'/>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.js'></script>
@endsection

@section('script_end')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ical.js/1.5.0/ical.min.js"></script>
    <script>
        let calendar;
        const colors = [
            '#3788d8', '#ff9f89', '#66c2a5', '#fc8d62', '#8da0cb',
            '#e78ac3', '#a6d854', '#ffd92f', '#e5c494', '#b3b3b3'
        ];
        const matiereColors = {};
        let colorIndex = 0;
        let calendarEvents = [];

        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('calendar');
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth', // Vue mensuelle par défaut
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                locale: 'fr', // Langue française
                editable: true,
                selectable: true,
                eventContent: function (arg) {
                    // Affiche seulement le titre avec la couleur de fond
                    return {
                        html: `
                        <div class="fc-event-title" style="background-color: ${arg.event.backgroundColor}; color: white; padding: 2px 5px; border-radius: 3px;">
                            ${arg.event.title}
                        </div>
                    `
                    };
                }
            });
            calendar.render();

            loadEdtData();

            document.getElementById('exportButton').addEventListener('click', exportToICS);
        });

        function loadEdtData() {
            fetch('/edt/data')
                .then(response => response.json())
                .then(data => {
                    console.log("Données JSON chargées:", data);
                    updateCalendar(data);
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des données:', error);
                });
        }

        function updateCalendar(data) {
            calendarEvents = generateEvents(data);
            console.log("Événements générés:", calendarEvents);
            calendar.removeAllEvents();
            calendar.addEventSource(calendarEvents);
        }

        function generateEvents(data) {
            if (!data || !data.weeks) {
                console.error("Format de données invalide");
                return [];
            }

            return data.weeks.flatMap(week => {
                const weekStartDate = new Date(week.start_date);

                return week.emploi_du_temps.flatMap((jour, index) => {
                    if (!jour.cours) {
                        console.warn(`Pas de cours pour le jour ${index} de la semaine commençant le ${week.start_date}`);
                        return [];
                    }

                    // Calculer la date du jour en fonction de son index dans la semaine
                    const jourDate = new Date(weekStartDate);
                    jourDate.setDate(weekStartDate.getDate() + index);

                    return jour.cours.map(cours => {
                        if (!cours.heure_debut || !cours.heure_fin || !cours.matiere) {
                            console.warn(`Cours invalide pour le jour ${jourDate.toISOString().split('T')[0]}:,`, cours);
                            return null;
                        }

                        if (!matiereColors[cours.matiere]) {
                            matiereColors[cours.matiere] = colors[colorIndex % colors.length];
                            colorIndex++;
                        }

                        const startTime = parseTime(cours.heure_debut);
                        const endTime = parseTime(cours.heure_fin);

                        const startDate = new Date(jourDate);
                        startDate.setHours(parseInt(startTime.split(':')[0]), parseInt(startTime.split(':')[1]));

                        const endDate = new Date(jourDate);
                        endDate.setHours(parseInt(endTime.split(':')[0]), parseInt(endTime.split(':')[1]));

                        if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
                            console.warn(`Date invalide pour le cours:`, cours);
                            return null;
                        }

                        return {
                            title: cours.matiere,
                            start: startDate,
                            end: endDate,
                            backgroundColor: matiereColors[cours.matiere],
                            borderColor: matiereColors[cours.matiere],
                            extendedProps: {
                                professor: cours.professeur,
                                room: cours.salle
                            }
                        };
                    });
                });
            }).filter(event => event !== null);
        }

        function parseTime(timeStr) {
            const [hours, minutes] = timeStr.split('h');
            return `${hours.padStart(2, '0')}:${(minutes || '00').padStart(2, '0')}`;
        }

        function exportToICS() {
            const cal = new ICAL.Component(['vcalendar', [], []]);
            cal.updatePropertyWithValue('prodid', '-//Licence STS Dev Calendar');
            cal.updatePropertyWithValue('version', '2.0');

            calendarEvents.forEach(event => {
                const vevent = new ICAL.Component('vevent');
                const eventStart = ICAL.Time.fromJSDate(event.start, true);
                const eventEnd = ICAL.Time.fromJSDate(event.end, true);

                vevent.updatePropertyWithValue('summary', event.title);
                vevent.updatePropertyWithValue('dtstart', eventStart);
                vevent.updatePropertyWithValue('dtend', eventEnd);
                vevent.updatePropertyWithValue('description', `Professeur: ${event.extendedProps.professor}\nSalle: ${event.extendedProps.room || 'Non spécifiée'}`);

                cal.addSubcomponent(vevent);
            });

            const icsData = cal.toString();
            const blob = new Blob([icsData], {type: 'text/calendar;charset=utf-8'});
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'emploi_du_temps.ics';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
@endsection
