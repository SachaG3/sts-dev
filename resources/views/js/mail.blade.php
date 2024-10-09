<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mailIcon = document.querySelector('#mailIcon');
        const modal = document.getElementById('passwordModal');
        const modalClose = document.querySelector('.modal-close');
        const passwordSection = document.getElementById('passwordSection');
        const emailsSection = document.getElementById('emailsSection');
        const searchInput = document.getElementById('searchInput');

        let emailsData = [];

        fetch('{{ route('get_emails') }}', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    emailsData = data.emails;
                    mailIcon.addEventListener('click', function (event) {
                        event.preventDefault();
                        passwordSection.classList.add('hidden');
                        emailsSection.classList.remove('hidden');
                        updateEmailsTable(emailsData);
                        attachAddEmailEventListeners();
                        modal.classList.remove('hidden');
                        modal.classList.add('large-modal');
                    });

                    searchInput.addEventListener('input', function () {
                        filterEmails(emailsData, searchInput.value);
                    });
                } else {
                    mailIcon.addEventListener('click', function (event) {
                        event.preventDefault();
                        modal.classList.remove('hidden');
                    });
                }
            });

        modalClose.addEventListener('click', function () {
            modal.classList.add('hidden');
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                modal.classList.add('hidden');
            }
        });

        document.getElementById('passwordForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const password = document.getElementById('password').value;

            fetch('{{ route('verify_password') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({password: password})
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        emailsData = data.emails;
                        passwordSection.classList.add('hidden');
                        emailsSection.classList.remove('hidden');
                        updateEmailsTable(emailsData);
                        attachAddEmailEventListeners();
                        modal.classList.add('large-modal');
                    } else {
                        alert('Mot de passe incorrect');
                    }
                });
        });

        function attachAddEmailEventListeners() {
            const addEmailButton = document.getElementById('addEmailButton');
            const addEmailForm = document.getElementById('addEmailForm');

            if (addEmailButton) {
                addEmailButton.addEventListener('click', function () {
                    document.getElementById('addEmailSection').classList.remove('hidden');
                });
            }

            if (addEmailForm) {
                addEmailForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const formData = new FormData(addEmailForm);

                    fetch('{{ route('add_email') }}', {
                        method: 'POST',
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
                                document.getElementById('addEmailSection').classList.add('hidden');
                                attachAddEmailEventListeners();
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
            emailsTableBody.innerHTML = emails.map(email => `
            <tr>
                <td>${email.first_name}</td>
                <td>${email.last_name}</td>
                <td>${email.email}</td>
                <td>${email.type}</td>
                <td class="flex space-x-2">
                    <button class="btn btn-sm btn-outline copy-email" data-email="${email.email}" onclick="copyEmail(this)">Copier</button>
                    <button class="btn btn-sm btn-primary" data-email="${email.email}" onclick="openMailApp(this)">Envoyer un mail</button>
                </td>
            </tr>`).join('');
        }

        function filterEmails(emails, searchTerm) {
            const tableBody = document.getElementById('emailsTableBody');
            const regex = new RegExp(`(${searchTerm})`, 'gi');
            tableBody.innerHTML = emails
                .filter(email => {
                    return email.first_name.match(regex) || email.last_name.match(regex) || email.email.match(regex) || email.type.match(regex);
                })
                .map(email => `
                <tr>
                    <td>${highlightMatch(email.first_name, regex)}</td>
                    <td>${highlightMatch(email.last_name, regex)}</td>
                    <td>${highlightMatch(email.email, regex)}</td>
                    <td>${highlightMatch(email.type, regex)}</td>
                    <td class="flex space-x-2">
                        <button class="btn btn-sm btn-outline copy-email" data-email="${email.email}" onclick="copyEmail(this)">Copier</button>
                        <button class="btn btn-sm btn-primary" data-email="${email.email}" onclick="openMailApp(this)">Envoyer un mail</button>
                    </td>
                </tr>`
                ).join('');
        }

        function highlightMatch(text, regex) {
            return text.replace(regex, '<span class="bg-yellow-300">$1</span>');
        }

    });

</script>
