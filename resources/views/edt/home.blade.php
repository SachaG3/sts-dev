@extends("include/app")
@section('head')
    <title>Emploi du temps - Licence STS Dev</title>
@endsection

@section('content')
    <div class="bg-base-100 min-h-screen">
        <div class="container mx-auto p-4">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold">Emploi du temps - Licence STS Dev</h1>
                <a href="{{ route('calendar.feed') }}" class="text-center text-primary hover:!text-black">S'abonner au
                    calendrier</a>

            </div>
            <div class="calendar-wrapper">
                <div id="calendar" class="bg-base-200 p-4 rounded-lg shadow-lg"></div>
            </div>

            <div id="matiere-modal" class="modal">
                <div class="modal-box">
                    <h3 class="font-bold text-2xl text-center mb-4" id="modal-title"></h3>
                    <div id="modal-content" class="py-4 text-center">
                    </div>
                    <div class="modal-action justify-center">
                        <button class="btn hidden" id="close-modal">Fermer</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
    #modal-content p {
    margin-bottom: 0.5rem;
    font-size: 1rem;
    }

    .fc-timegrid-event.fc-full-width-event,
    .fc-daygrid-event.fc-full-width-event {
    display: block;
    width: 100% !important;
    white-space: normal !important;
    padding: 5px;
    box-sizing: border-box;
    }

    .fc-daygrid-event-dot {
    display: none;
    }

    .fc-event {
    cursor: pointer;
    }

    @media (max-width: 768px) {
    .fc-timegrid-event.fc-full-width-event,
    .fc-daygrid-event.fc-full-width-event {
    font-size: 41%;
    padding: 1px;
    }

    .fc-event {
    margin: 1px;
    }

    .fc-timegrid-event .fc-event-title,
    .fc-timegrid-event .fc-event-time,
    .fc-timegrid-event .fc-event-location {
    font-size: 0.8em;
    }

    .fc-timegrid-axis {
    font-size: 0.7em;
    }

    .fc-col-header-cell {
    font-size: 0.8em;
    padding: 2px;
    }

    .fc-toolbar {
    display: flex;
    flex-direction: column;
    align-items: center;
    }

    .fc-toolbar-title {
    font-size: 14px;
    margin-bottom: 5px;
    }

    .fc-toolbar-chunk {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 2px;
    }

    .fc-button {
    font-size: 12px;
    padding: 4px 8px;
    }

    .fc-timegrid-slot {
    height: 1.5em; /* Ajuster selon les besoins */
    }
    }
@endsection


@section('script')
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.css' rel='stylesheet'/>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.js'></script>
@endsection

@section('script_end')
    <script>
        let calendar;
        let calendarEvents = [];

        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('calendar');
            let isMobileView = window.innerWidth <= 768;
            let initialView = isMobileView ? 'timeGridDay' : 'timeGridWeek';
            let availableViews = isMobileView
                ? 'dayGridMonth,timeGridWeek,timeGridDay'
                : 'dayGridMonth,timeGridWeek';

            calendar = new FullCalendar.Calendar(calendarEl, {

                initialView: initialView,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: availableViews
                },
                locale: 'fr',
                buttonText: {
                    today: 'Aujourd\'hui',
                    month: 'Mois',
                    week: 'Semaine',
                    day: 'Jour',
                    list: 'Liste'
                },
                editable: false,
                selectable: false,
                height: 'auto',
                contentHeight: 'auto',
                expandRows: true,
                slotMinTime: '08:00',
                slotMaxTime: '17:30',
                hiddenDays: [0, 6],
                allDaySlot: false,
                views: {
                    timeGridWeek: {
                        dayHeaderFormat: isMobileView ? {weekday: 'narrow'} : {weekday: 'long', day: '2-digit'},
                    },
                    dayGridMonth: {
                        dayHeaderFormat: isMobileView ? {weekday: 'narrow'} : {weekday: 'long'},
                    },
                    timeGridDay: {
                        dayHeaderFormat: {weekday: 'long'},
                    }
                },

                eventClassNames: function (arg) {
                    if (['dayGridMonth', 'timeGridWeek'].includes(calendar.view.type)) {
                        return ['fc-full-width-event'];
                    }
                    return [];
                },

                eventContent: function (arg) {
                    const isMonthView = calendar.view.type === 'dayGridMonth';
                    const titleStyle = 'font-size: 1em;';
                    const {backgroundColor, title, extendedProps} = arg.event;

                    let htmlContent = `<div style="background-color: ${backgroundColor}; color: white; padding: 2px 5px; border-radius: 3px;">
                        <div class="fc-event-title" style="${titleStyle}">${title}</div>`;

                    if (!isMonthView) {
                        if (extendedProps.professor) {
                            htmlContent += `<div class="fc-event-professor" style="font-size: 0.85em;">${extendedProps.professor}</div>`;
                        }
                        if (extendedProps.room) {
                            htmlContent += `<div class="fc-event-room" style="font-size: 0.75em;">Salle: ${extendedProps.room}</div>`;
                        }
                    }

                    htmlContent += '</div>';

                    return {html: htmlContent};
                },

                eventClick: function (info) {
                    const {start, end, title, extendedProps} = info.event;
                    const startTime = start.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'});
                    const endTime = end.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'});

                    const eventDetails = `
                    <p><strong>📚 Nom :</strong> ${extendedProps.matiere_name}</p>
                    <p><strong>⏰ Heure :</strong> ${startTime} - ${endTime}</p>
                    ${extendedProps.professor ? `<p><strong>👨‍🏫 Professeur :</strong> ${extendedProps.professor}</p>` : ''}
                    ${extendedProps.room ? `<p><strong>🏫 Salle :</strong> ${extendedProps.room}</p>` : ''}
                    `;


                    document.getElementById('modal-title').innerText = title;
                    document.getElementById('modal-content').innerHTML = eventDetails;

                    document.getElementById('matiere-modal').classList.add('modal-open');
                },


                windowResize: function () {
                    const isMobileView = window.innerWidth <= 768;
                    calendar.setOption('views', {
                        timeGridWeek: {
                            dayHeaderFormat: isMobileView ? {weekday: 'narrow'} : {weekday: 'long', day: '2-digit'}
                        },
                        dayGridMonth: {
                            dayHeaderFormat: isMobileView ? {weekday: 'narrow'} : {weekday: 'long'}
                        },
                        timeGridDay: {
                            dayHeaderFormat: {weekday: 'long'}
                        }
                    });
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
                });
        }

        function loadRemainingWeeks() {
            fetch('/edt/remaining-weeks')
                .then(response => response.json())
                .then(data => {
                    updateCalendar(data);
                });
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
                        if (!cours.heure_debut || !cours.heure_fin || !cours.matiere || !cours.color) {
                            return null;
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
                            backgroundColor: cours.color,
                            borderColor: cours.color,
                            extendedProps: {
                                matiere_name: cours.matiere_name,
                                professor: cours.professeur,
                                room: cours.salle
                            }
                        };
                    }).filter(event => event !== null);
                });
            });
        }

        function parseTime(timeStr) {
            const [hours, minutes] = timeStr.split('h');
            return `${hours.padStart(2, '0')}:${(minutes || '00').padStart(2, '0')}`;
        }

        document.getElementById('close-modal').addEventListener('click', function () {
            document.getElementById('matiere-modal').classList.remove('modal-open');
        });

        document.getElementById('matiere-modal').addEventListener('click', function (event) {
            if (event.target === this) {
                this.classList.remove('modal-open');
            }
        });

        document.querySelector('.modal-box').addEventListener('click', function (event) {
            event.stopPropagation();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft') {
                calendar.prev();
            } else if (event.key === 'ArrowRight') {
                calendar.next();
            }
        });

        const calendarElement = document.getElementById('calendar');
        let touchStartX = 0;
        let touchEndX = 0;

        const SWIPE_THRESHOLD = 80;

        calendarElement.addEventListener('touchstart', function (event) {
            touchStartX = event.changedTouches[0].screenX;
        });

        calendarElement.addEventListener('touchend', function (event) {
            touchEndX = event.changedTouches[0].screenX;
            handleSwipeGesture();
        });

        function handleSwipeGesture() {
            const deltaX = touchEndX - touchStartX;

            if (Math.abs(deltaX) > SWIPE_THRESHOLD) {
                if (deltaX < 0) {
                    calendar.next();
                } else {
                    calendar.prev();
                }
            }
        }
    </script>
@endsection
