@extends("include/app")
@section('content')
    <body class="bg-base-100 min-h-screen">
    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold mb-4">Emploi du temps - Licence STS Dev</h1>
        <button id="exportButton" class="btn btn-primary mb-4">Exporter pour Google Calendar</button>
        <div id="calendar" class="bg-base-200 p-4 rounded-lg shadow-lg"></div>
    </div>
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
                    initialView: 'timeGridWeek',
                    slotMinTime: '08:00',
                    slotMaxTime: '18:00',
                    allDaySlot: false,
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'timeGridWeek,timeGridDay'
                    },
                    locale: 'fr',
                    eventContent: function (arg) {
                        return {
                            html: `
                        <div class="fc-event-title">${arg.event.title}</div>
                        <div class="fc-event-description">${arg.event.extendedProps.professor}</div>
                        ${arg.event.extendedProps.room ? `<div class="fc-event-room">${arg.event.extendedProps.room}</div>` : ''}
                    `
                        };
                    }
                });
                calendar.render();

                loadEdtData();

                document.getElementById('exportButton').addEventListener('click', exportToICS);
            });

            function loadEdtData() {
                fetch('edt/edt_semaine.json')
                    .then(response => response.json())
                    .then(data => {
                        console.log("Données JSON chargées:", data);
                        updateCalendar(data);
                    })
                    .catch(error => {
                        console.error('Erreur lors du chargement du fichier JSON:', error);
                    });
            }

            function updateCalendar(emploiDuTemps) {
                calendarEvents = generateEvents(emploiDuTemps);
                console.log("Événements générés:", calendarEvents);
                calendar.removeAllEvents();
                calendar.addEventSource(calendarEvents);
                if (calendarEvents.length > 0 && calendarEvents[0].start) {
                    calendar.gotoDate(calendarEvents[0].start);
                } else {
                    console.warn("Aucun événement valide n'a été trouvé dans l'emploi du temps.");
                }
            }

            // Fonctions parseDate, parseTime, et generateEvents restent inchangées...

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


            function parseDate(dateStr) {
                if (!dateStr) return null;
                const [day, month, year] = dateStr.split('/');
                return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
            }

            function parseTime(timeStr) {
                const [hours, minutes] = timeStr.split('h');
                return `${hours.padStart(2, '0')}:${(minutes || '00').padStart(2, '0')}`;
            }

            function generateEvents(emploiDuTemps) {
                if (!emploiDuTemps || !emploiDuTemps.emploi_du_temps) {
                    console.error("Format d'emploi du temps invalide");
                    return [];
                }

                let currentDate = null;

                return emploiDuTemps.emploi_du_temps.flatMap((jour, index) => {
                    if (jour.date) {
                        currentDate = parseDate(jour.date);
                    } else if (currentDate) {
                        // Si pas de date spécifiée, on ajoute un jour à la date précédente
                        const tempDate = new Date(currentDate);
                        tempDate.setDate(tempDate.getDate() + 1);
                        currentDate = tempDate.toISOString().split('T')[0];
                    } else {
                        console.warn(`Impossible de déterminer la date pour le jour ${index + 1}`);
                        return [];
                    }

                    if (!jour.cours) {
                        console.warn(`Pas de cours pour le jour ${currentDate}`);
                        return [];
                    }

                    console.log(`Traitement du jour: ${currentDate}`);

                    return jour.cours.map(cours => {
                        if (!cours.heure_debut || !cours.heure_fin || !cours.matiere) {
                            console.warn(`Cours invalide pour le jour ${currentDate}:`, cours);
                            return null;
                        }

                        if (!matiereColors[cours.matiere]) {
                            matiereColors[cours.matiere] = colors[colorIndex % colors.length];
                            colorIndex++;
                        }

                        const startTime = parseTime(cours.heure_debut);
                        const endTime = parseTime(cours.heure_fin);

                        const startDate = new Date(`${currentDate}T${startTime}`);
                        const endDate = new Date(`${currentDate}T${endTime}`);

                        if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
                            console.warn(`Date invalide pour le cours du ${currentDate}:`, cours);
                            return null;
                        }

                        console.log(`Cours créé: ${cours.matiere} le ${currentDate} de ${startTime} à ${endTime}`);

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
                }).filter(event => event !== null);
            }


        </script>
@endsection
