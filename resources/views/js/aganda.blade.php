<script>
    document.addEventListener('DOMContentLoaded', function () {
        const agendaIcon = document.querySelector('#agendaIcon');
        const agendaModal = document.getElementById('agendaModal');
        const agendaModalContent = document.getElementById('agendaContent');
        const agendaModalClose = document.querySelector('.agenda-modal-close');
        const addAssignmentForm = document.getElementById('addAssignmentForm');
        const matiereSelectForm = document.getElementById('matiereSelectForm');
        const weekSelectForm = document.getElementById('weekSelect');

        agendaIcon.addEventListener('click', function (event) {
            event.preventDefault();
            fetchAssignments();
            fetchMatieres();
            fetchAvailableWeeks();
            agendaModal.classList.remove('hidden');
        });

        agendaModalClose.addEventListener('click', function () {
            agendaModal.classList.add('hidden');
        });

        agendaModal.addEventListener('click', function (event) {
            if (event.target === agendaModal) {
                agendaModal.classList.add('hidden');
            }
        });

        function fetchAssignments(matiereId = null) {
            let url = `{{ route('get_assignments') }}`;
            if (matiereId) {
                url += `?matiere_id=${matiereId}`;
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        agendaModalContent.innerHTML = data.html;
                    } else {
                        alert('Erreur lors de la récupération des devoirs');
                    }
                })
                .catch(error => console.error('Error fetching assignments:', error));
        }

        function fetchMatieres() {
            fetch(`{{ route('get_matieres') }}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length) {
                        matiereSelectForm.innerHTML = '<option value="">Sélectionnez une matière</option>';
                        data.forEach(matiere => {
                            matiereSelectForm.innerHTML += `<option value="${matiere.id}"> ${matiere.name}${matiere.long_name ? ` (${matiere.long_name})` : ''}</option>`;
                        });
                        addAssignmentForm.classList.remove('hidden');
                    } else {
                        matiereSelectForm.innerHTML = '<option value="">Aucune matière disponible</option>';
                        addAssignmentForm.classList.add('hidden');
                    }
                })
                .catch(error => console.error('Error fetching matieres:', error));
        }

        function fetchAvailableWeeks() {
            fetch('{{ route('get_weeks') }}')
                .then(response => response.json())
                .then(data => {
                    const weekSelectForm = document.getElementById('weekSelect');
                    weekSelectForm.innerHTML = '';

                    data.forEach(week => {
                        const option = document.createElement('option');
                        option.value = week.date;
                        option.textContent = week.formatted;
                        weekSelectForm.appendChild(option);
                    });

                    if (weekSelectForm.options.length > 0) {
                        weekSelectForm.options[0].selected = true;
                    }
                })
                .catch(error => console.error('Erreur lors de la récupération des semaines :', error));
        }

        addAssignmentForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(addAssignmentForm);

            fetch('{{ route('add_assignment') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        agendaModalContent.innerHTML = data.html;
                        alert('Devoir ajouté avec succès');
                    } else {
                        alert('Erreur lors de l\'ajout du devoir');
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    });
</script>
