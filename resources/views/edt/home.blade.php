@extends("include/app")
@section('head')
    <title>Emploi du temps - Licence STS Dev</title>
@endsection
@section('content')
    <body class="bg-base-100 min-h-screen">
    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold mb-4">Emploi du temps - Licence STS Dev</h1>
        <div class="mb-4">
            <a href="{{ route('calendar.feed') }}" class="btn btn-secondary ml-2">S'abonner au calendrier</a>
            <button id="exportPdfButton" class="btn btn-primary ml-2">Exporter la semaine en PDF</button>
        </div>
        <div id="calendar" class="bg-base-200 p-4 rounded-lg shadow-lg"></div>
    </div>
    </body>
@endsection
@section('style')
    /* Styles pour les boutons et le titre sur mobile */
    @media (max-width: 768px) {
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dom-to-image/2.6.0/dom-to-image.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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
            const isMobileView = window.innerWidth <= 768;
            const initialView = isMobileView ? 'timeGridDay' : 'timeGridWeek'; // Vue par défaut conditionnée

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: initialView, // Vue par défaut
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
                dayHeaderFormat: isMobileView ? {weekday: 'narrow'} : {weekday: 'long'},
                eventContent: function (arg) {
                    const professor = arg.event.extendedProps.professor;
                    const room = arg.event.extendedProps.room;
                    let additionalInfo = '';

                    const titleStyle = isMobileView ? 'font-size: 0.9em;' : 'font-size: 1em;';
                    const professorStyle = isMobileView ? 'font-size: 0.7em;' : 'font-size: 0.85em;';
                    const roomStyle = isMobileView ? 'font-size: 0.6em;' : 'font-size: 0.75em;';

                    if (professor) {
                        additionalInfo += `<div class="fc-event-professor" style="${professorStyle}">Prof: ${professor}</div>`;
                    }

                    if (room) {
                        additionalInfo += `<div class="fc-event-room" style="${roomStyle}">Salle: ${room}</div>`;
                    }

                    return {
                        html: `
                    <div style="background-color: ${arg.event.backgroundColor}; color: white; padding: 2px 5px; border-radius: 3px;">
                        <div class="fc-event-title" style="${titleStyle}">${arg.event.title}</div>
                        ${additionalInfo}
                    </div>
                `
                    };
                },
                windowResize: function (view) {
                    if (window.innerWidth <= 768) {
                        calendar.changeView('timeGridDay');
                        calendar.setOption('dayHeaderFormat', {weekday: 'narrow'});
                    } else {
                        calendar.changeView('timeGridWeek');
                        calendar.setOption('dayHeaderFormat', {weekday: 'long'});
                    }
                },
                eventClick: function (info) {
                    let eventDetails = `<p><strong>Début:</strong> ${info.event.start.toLocaleTimeString('fr-FR', {
                        hour: '2-digit',
                        minute: '2-digit'
                    })}</p>
                                <p><strong>Fin:</strong> ${info.event.end.toLocaleTimeString('fr-FR', {
                        hour: '2-digit',
                        minute: '2-digit'
                    })}</p>`;

                    if (info.event.extendedProps.professor) {
                        eventDetails = `<p><strong>Professeur:</strong> ${info.event.extendedProps.professor}</p>` + eventDetails;
                    }

                    if (info.event.extendedProps.room) {
                        eventDetails = `<p><strong>Salle:</strong> ${info.event.extendedProps.room}</p>` + eventDetails;
                    }

                    Swal.fire({
                        title: info.event.title,
                        html: eventDetails,
                        icon: 'info',
                        confirmButtonText: 'Fermer'
                    });
                }
            });
            calendar.render();

            loadEdtData();

            // Fonction pour exporter la vue actuelle en PDF
            document.getElementById('exportPdfButton').addEventListener('click', function () {
                console.log("Export PDF button clicked");

                domtoimage.toPng(calendarEl)
                    .then(function (dataUrl) {
                        console.log("Image created");
                        const {jsPDF} = window.jspdf;
                        const pdf = new jsPDF('landscape');
                        pdf.addImage(dataUrl, 'PNG', 10, 10, 280, 190);
                        pdf.save('semaine_calendrier.pdf');
                        console.log("PDF saved");
                    })
                    .catch(function (error) {
                        console.error("Error generating PDF:", error);
                    });
            });

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
    </script>
@endsection
