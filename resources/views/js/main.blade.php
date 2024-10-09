<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mailIcon = document.querySelector('#mailIcon');
        const agendaIcon = document.querySelector('#agendaIcon');
        const passwordModal = document.getElementById('passwordModal');
        const passwordForm = document.getElementById('passwordForm');
        const passwordSection = document.getElementById('passwordSection');
        const contentSection = document.getElementById('contentSection');
        const emailsSection = document.getElementById('emailsSection');
        const agendaSection = document.getElementById('agendaSection');
        const modalClose = document.querySelector('.modal-close');
        let nextAction = null;
        let isAuthenticated = false;

        // Fonctions utilitaires pour afficher/masquer des éléments
        function showElement(element) {
            element.classList.remove('hidden');
        }

        function hideElement(element) {
            element.classList.add('hidden');
        }

        // Fermeture du modal
        modalClose.addEventListener('click', closeModal);
        passwordModal.addEventListener('click', function (event) {
            if (event.target === passwordModal) {
                closeModal();
            }
        });

        function closeModal() {
            hideElement(passwordModal);
            hideElement(contentSection);
            showElement(passwordSection);
            passwordForm.reset();
            passwordModal.classList.remove('large-modal');
            nextAction = null;
        }

        // Vérifie si l'utilisateur est authentifié
        function checkAuthentication() {
            return fetch('{{ route('check_authentication') }}', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
                .then(response => response.json())
                .then(data => {
                    isAuthenticated = data.authenticated;
                    return isAuthenticated;
                });
        }

        // Gestion des clics sur les icônes
        function handleIconClick(event, action) {
            event.preventDefault();
            checkAuthentication().then(authenticated => {
                if (authenticated) {
                    if (action === 'mail') {
                        openMail();
                    } else if (action === 'agenda') {
                        openAgenda();
                    }
                } else {
                    nextAction = action;
                    showElement(passwordModal);
                    showElement(passwordSection);
                    hideElement(contentSection);
                }
            });
        }

        mailIcon.addEventListener('click', function (event) {
            handleIconClick(event, 'mail');
        });

        agendaIcon.addEventListener('click', function (event) {
            handleIconClick(event, 'agenda');
        });

        // Gestion de l'authentification par mot de passe
        passwordForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const password = document.getElementById('password').value;

            fetch('{{ route('verify_password') }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({password: password})
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        isAuthenticated = true;
                        hideElement(passwordSection);
                        passwordModal.classList.add('large-modal');
                        if (nextAction === 'mail') {
                            openMail();
                        } else if (nextAction === 'agenda') {
                            openAgenda();
                        }
                        nextAction = null;
                    } else {
                        alert('Mot de passe incorrect');
                    }
                });
        });

        // Ouverture de la section mail
        function openMail() {
            showElement(passwordModal);
            passwordModal.classList.add('large-modal');
            hideElement(passwordSection);
            showElement(contentSection);
            showElement(emailsSection);
            hideElement(agendaSection);
            fetchEmails().then(emails => {
                showMailContent(emails);
            });
        }

        // Ouverture de la section agenda
        function openAgenda() {
            showElement(passwordModal);
            passwordModal.classList.add('large-modal');
            hideElement(passwordSection);
            showElement(contentSection);
            showElement(agendaSection);
            hideElement(emailsSection);
            fetchAssignments();
            fetchMatieres();
            fetchAvailableWeeks();
        }

        // Gestion des emails
        let emailsData = [];
        const searchInput = document.getElementById('searchInput');

        function fetchEmails() {
            return fetch('{{ route('get_emails') }}', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        emailsData = data.emails;
                        return emailsData;
                    } else {
                        throw new Error('Erreur lors de la récupération des emails');
                    }
                });
        }

        function showMailContent(emails) {
            updateEmailsTable(emailsData);
            attachAddEmailEventListeners();

            searchInput.addEventListener('input', function () {
                filterEmails(emailsData, searchInput.value);
            });
        }

        function attachAddEmailEventListeners() {
            const addEmailButton = document.getElementById('addEmailButton');
            const addEmailForm = document.getElementById('addEmailForm');

            if (addEmailButton) {
                addEmailButton.addEventListener('click', function () {
                    showElement(document.getElementById('addEmailSection'));
                });
            }

            if (addEmailForm) {
                addEmailForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const formData = new FormData(addEmailForm);

                    fetch('{{ route('add_email') }}', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                emailsData = data.emails;
                                updateEmailsTable(emailsData);
                                hideElement(document.getElementById('addEmailSection'));
                                addEmailForm.reset();
                            } else {
                                alert('Erreur lors de l\'ajout de l\'email');
                            }
                        })
                        .catch(error => console.error('Error:', error));
                });
            }
        }

        function updateEmailsTable(emails) {
            const emailsTableBody = document.getElementById('emailsTableBody');
            emailsTableBody.innerHTML = emails.map(email =>
                `<tr>
                    <td>${email.first_name}</td>
                    <td>${email.last_name}</td>
                    <td>${email.email}</td>
                    <td>${email.type}</td>
                    <td class="flex space-x-2">
                        <button class="btn btn-sm btn-outline copy-email tooltip" data-email="${email.email}">Copier</button>
                        <button class="btn btn-sm btn-primary send-email" data-email="${email.email}">Envoyer un mail</button>
                    </td>
                </tr>`).join('');

            // Ajouter les écouteurs d'événements pour les boutons Copier et Envoyer un mail
            document.querySelectorAll('.copy-email').forEach(button => {
                button.addEventListener('click', function () {
                    copyEmail(this);
                });
            });

            document.querySelectorAll('.send-email').forEach(button => {
                button.addEventListener('click', function () {
                    openMailApp(this.getAttribute('data-email'));
                });
            });
        }

        function copyEmail(element) {
            const email = element.getAttribute('data-email');
            navigator.clipboard.writeText(email).then(() => {
                element.setAttribute('data-tip', 'Email copié !');
                setTimeout(() => {
                    element.parentElement.setAttribute('data-tip', 'Cliquez pour copier');
                }, 2000);
            }).catch(err => {
                console.error('Échec de la copie de l\'email :', err);
            });
        }


        function openMailApp(email) {
            window.location.href = `mailto:${email}`;
        }

        function filterEmails(emails, searchTerm) {
            const tableBody = document.getElementById('emailsTableBody');
            const regex = new RegExp(`(${searchTerm})`, 'gi');
            tableBody.innerHTML = emails
                .filter(email => {
                    return email.first_name.match(regex) || email.last_name.match(regex) || email.email.match(regex) || email.type.match(regex);
                })
                .map(email =>
                    `<tr>
                        <td>${highlightMatch(email.first_name, regex)}</td>
                        <td>${highlightMatch(email.last_name, regex)}</td>
                        <td>${highlightMatch(email.email, regex)}</td>
                        <td>${highlightMatch(email.type, regex)}</td>
                        <td class="flex space-x-2">
                            <button class="btn btn-sm btn-outline copy-email" data-email="${email.email}">Copier</button>
                            <button class="btn btn-sm btn-primary send-email" data-email="${email.email}">Envoyer un mail</button>
                        </td>
                    </tr>`
                ).join('');

            // Réattacher les écouteurs d'événements après le filtrage
            document.querySelectorAll('.copy-email').forEach(button => {
                button.addEventListener('click', function () {
                    copyEmail(this.getAttribute('data-email'));
                });
            });

            document.querySelectorAll('.send-email').forEach(button => {
                button.addEventListener('click', function () {
                    openMailApp(this.getAttribute('data-email'));
                });
            });
        }

        function highlightMatch(text, regex) {
            return text.replace(regex, '<span class="bg-yellow-300">$1</span>');
        }

        // Gestion de l'agenda
        const addAssignmentForm = document.getElementById('addAssignmentForm');
        const matiereSelectForm = document.getElementById('matiereSelectForm');
        const weekSelectForm = document.getElementById('weekSelect');
        const agendaContent = document.getElementById('agendaContent');

        function fetchAssignments(matiereId = null) {
            let url = '{{ route('get_assignments') }}';
            if (matiereId) {
                url += `?matiere_id=${matiereId}`;
            }

            fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        agendaContent.innerHTML = data.html;
                    } else {
                        alert('Erreur lors de la récupération des devoirs');
                    }
                })
                .catch(error => console.error('Error fetching assignments:', error));
        }

        function fetchMatieres() {
            fetch('{{ route('get_matieres') }}', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
                .then(response => response.json())
                .then(data => {
                    matiereSelectForm.innerHTML = '<option value="">Sélectionnez une matière</option>';
                    if (data.length) {
                        data.forEach(matiere => {
                            matiereSelectForm.innerHTML += `<option value="${matiere.id}">${matiere.name}${matiere.long_name ? ` (${matiere.long_name})` : ''}</option>`;
                        });
                        showElement(addAssignmentForm);
                    } else {
                        matiereSelectForm.innerHTML = '<option value="">Aucune matière disponible</option>';
                        hideElement(addAssignmentForm);
                    }
                })
                .catch(error => console.error('Error fetching matieres:', error));
        }

        function fetchAvailableWeeks() {
            fetch('{{ route('get_weeks') }}', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
                .then(response => response.json())
                .then(data => {
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
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        agendaContent.innerHTML = data.html;
                        alert('Devoir ajouté avec succès');
                        addAssignmentForm.reset();
                    } else {
                        alert('Erreur lors de l\'ajout du devoir');
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    });
</script>
