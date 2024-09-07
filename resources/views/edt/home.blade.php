@extends("include/app")
@section('head')
    <title>Emploi du temps - Licence STS Dev</title>
@endsection
@section('content')
    <body class="bg-base-100 min-h-screen">
    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold mb-4">Emploi du temps - Licence STS Dev</h1>
        <div class="mb-4">
            <a href="{{ route('calendar.feed') }}" class="btn btn-primary ml-2">S'abonner au calendrier</a>
        </div>
        <div id="calendar" class="bg-base-200 p-4 rounded-lg shadow-lg"></div>
    </div>
    </body>
@endsection
@section('style')

    .fc-time-grid-event.fc-full-width-event {
    display: block;
    width: 100% !important;  /* Forcer l'événement à occuper toute la largeur */
    white-space: normal !important;  /* Permettre aux titres longs de s'étendre sur plusieurs lignes */
    padding: 5px;  /* Ajouter du padding pour aérer un peu */
    box-sizing: border-box;  /* S'assurer que le padding n'affecte pas la taille totale */
    }

    .fc-daygrid-event.fc-full-width-event {
    display: block;
    width: 100% !important; /* S'assure que l'événement occupe toute la largeur */
    white-space: normal !important; /* Permet à l'événement de s'étendre sur plusieurs lignes si nécessaire */
    padding: 5px; /* Ajoute un peu de padding interne */
    }

    .fc-daygrid-event-dot {
    display: none; /* Masque les points dans la vue mensuelle */
    }

    @media (max-width: 768px) {
    .fc-timegrid-event.fc-full-width-event{
    font-size:50%;
    }

    .fc-daygrid-event.fc-full-width-event {
    font-size:60%;
    }
    .fc-toolbar {
    display: flex;
    flex-direction: column;
    align-items: center; /* Centrer les éléments */
    }

    .fc-toolbar-title {
    font-size: 14px; /* Réduire la taille de la police du titre */
    margin-bottom: 10px; /* Ajouter un espace en dessous du titre */
    }

    .fc-toolbar-chunk {
    display: flex;
    justify-content: center;
    flex-wrap: wrap; /* Permet de mettre tous les boutons sur une ligne */
    gap: 5px; /* Espacement entre les boutons */
    }

    .fc-button {
    font-size: 12px; /* Réduire la taille de la police des boutons */
    padding: 5px 10px; /* Réduire le padding interne des boutons */
    }
    }
@endsection

@section('script')
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.css' rel='stylesheet'/>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@endsection

@section('script_end')
    <script>
        let calendar;
        const colors = [
            '#3788d8', '#ff9f89', '#66c2a5', '#fc8d62', '#8da0cb',
            '#e78ac3', '#a6d854', '#ffd92f', '#e5c494', '#b3b3b3',
            '#9f4949',
            '#c8cbcd',
        ];

        const matiereColors = {};
        let colorIndex = 0;
        let calendarEvents = [];
        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('calendar');
            const isMobileView = window.innerWidth <= 768;
            const initialView = isMobileView ? 'timeGridDay' : 'timeGridWeek';

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: initialView,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                locale: 'fr',
                buttonText: {
                    today: 'Aujourd\'hui',
                    month: 'Mois',
                    week: 'Semaine',
                    day: 'Jour',
                    list: 'Liste'
                },
                editable: true,
                selectable: true,
                height: 'auto',
                contentHeight: 'auto',
                expandRows: true,
                slotMinTime: '08:00',
                slotMaxTime: '18:00',
                hiddenDays: [0, 6],
                views: {
                    timeGridWeek: {
                        dayHeaderFormat: isMobileView ? {weekday: 'narrow'} : {weekday: 'long', day: '2-digit'}, // Vue semaine
                    },
                    dayGridMonth: {
                        dayHeaderFormat: isMobileView ? {weekday: 'narrow'} : {weekday: 'long'}, // Vue mois
                    },
                    timeGridDay: {
                        dayHeaderFormat: {weekday: 'long'}, // Vue jour (toujours "Lundi" pour mobile et PC)
                    }
                },
                eventClassNames: function (arg) {
                    if (calendar.view.type === 'dayGridMonth') {
                        // Pour la vue mois, appliquer la classe 'fc-full-width-event'
                        return ['fc-full-width-event'];
                    } else if (calendar.view.type === 'timeGridWeek') {
                        // Pour la vue semaine, appliquer également la classe 'fc-full-width-event'
                        return ['fc-full-width-event']; // Vous pouvez changer cette classe ou la réutiliser
                    }
                    return [];
                },


                eventContent: function (arg) {
                    const isMonthView = calendar.view.type === 'dayGridMonth';
                    const titleStyle = 'font-size: 1em;';

                    if (isMonthView) {
                        return {
                            html: `<div style="background-color: ${arg.event.backgroundColor}; color: white; padding: 2px 5px; border-radius: 3px;">
                                        <div class="fc-event-title" style="${titleStyle}">${arg.event.title}</div>
                                   </div>`
                        };
                    } else {
                        const professor = arg.event.extendedProps.professor;
                        const room = arg.event.extendedProps.room;
                        let additionalInfo = '';

                        if (professor) {
                            additionalInfo += `<div class="fc-event-professor" style="font-size: 0.85em;">${professor}</div>`;
                        }

                        if (room) {
                            additionalInfo += `<div class="fc-event-room" style="font-size: 0.75em;">Salle: ${room}</div>`;
                        }

                        return {
                            html: `<div style="background-color: ${arg.event.backgroundColor}; color: white; padding: 2px 5px; border-radius: 3px;">
                                        <div class="fc-event-title" style="${titleStyle}">${arg.event.title}</div>
                                        ${additionalInfo}
                                   </div>`
                        };
                    }
                },

                eventClick: function (info) {
                    const startTime = info.event.start.toLocaleTimeString('fr-FR', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    const endTime = info.event.end.toLocaleTimeString('fr-FR', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    const eventDetails = `
                        <p><strong>Début:</strong> ${startTime}</p>
                        <p><strong>Fin:</strong> ${endTime}</p>
                        ${info.event.extendedProps.professor ? `<p><strong>Professeur:</strong> ${info.event.extendedProps.professor}</p>` : ''}
                        ${info.event.extendedProps.room ? `<p><strong>Salle:</strong> ${info.event.extendedProps.room}</p>` : ''}
                    `;

                    Swal.fire({
                        title: info.event.title,
                        html: eventDetails,
                        icon: 'info',
                        confirmButtonText: 'Fermer'
                    });
                },

                windowResize: function (view) {
                    if (window.innerWidth <= 768) {
                        calendar.changeView('timeGridDay');
                        calendar.setOption('dayHeaderFormat', {weekday: 'narrow'});
                    } else {
                        calendar.changeView('timeGridWeek');
                        calendar.setOption('dayHeaderFormat', {weekday: 'long'});
                    }
                }
            });
            calendar.render();

            loadEdtData();

        });

        function loadEdtData() {
            fetch('/edt/data')
                .then(response => response.json())
                .then(data => {
                    updateCalendar(data);
                    loadRemainingWeeks();
                })
        }

        function loadRemainingWeeks() {
            fetch('/edt/remaining-weeks')
                .then(response => response.json())
                .then(data => {
                    updateCalendar(data);
                })
        }

        function updateCalendar(data) {
            calendarEvents = generateEvents(data);
            calendar.removeAllEvents();
            calendar.addEventSource(calendarEvents);
        }

        function generateEvents(data) {
            if (!data || !data.weeks) {
                return [];
            }

            return data.weeks.flatMap(week => {
                const weekStartDate = new Date(week.start_date);

                return week.emploi_du_temps.flatMap((jour, index) => {
                    if (!jour.cours) {
                        return [];
                    }

                    const jourDate = new Date(weekStartDate);
                    jourDate.setDate(weekStartDate.getDate() + index);

                    return jour.cours.map(cours => {
                        if (!cours.heure_debut || !cours.heure_fin || !cours.matiere) {
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

    </script>
@endsection
