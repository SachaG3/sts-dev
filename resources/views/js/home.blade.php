<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mailIcon = document.querySelector('#mailIcon');
        const agendaIcon = document.querySelector('#agendaIcon');
        const passwordModal = document.getElementById('passwordModal');
        const passwordForm = document.getElementById('passwordForm');
        const passwordSection = document.getElementById('passwordSection');
        const contentSection = document.getElementById('contentSection');
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
    });
</script>
