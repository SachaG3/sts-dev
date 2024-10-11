@extends("/include/app")
@section('script')
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
@endsection
@section('script_end')
    <script>
        lucide.createIcons();
    </script>
    @include("js.main")

@endsection

@section('content')
    <div class="min-h-screen flex flex-col justify-center items-center">
        <h1 class="text-4xl font-bold mb-12 text-center">Mes Applications</h1>

        <div class="grid grid-cols-5 gap-6 p-8">
            @php
                $icons = ['calendar', 'mail', 'book-text', 'hourglass', 'hourglass', 'globe', 'github', 'archive', 'hourglass', 'hourglass'];
                $icon_href = ['/', '#', '#', '#', '#', 'https://slamwiki2.kobject.net/', 'https://github.com/jcheron', 'https://www.iutcaen.unicaen.fr/dokuc3/departement_info/personnels/pb/mp3', '#', '#'];
                $datatip = ['Calendrier', 'les mails', 'Agenda', 'En cours', 'En cours', 'slamwiki', 'GitHub de JC', 'Brutus', 'En cours', 'En cours'];
            @endphp
            @foreach ($icons as $index => $icon)
                <div class="icon relative group">
                    @switch($icon)
                        @case('mail')
                            <button id="mailIcon" class="w-full">
                                <div
                                    class="absolute inset-0 bg-gradient-to-br from-transparent to-transparent group-hover:from-white/10 group-hover:to-white/5 rounded-lg transition-all duration-300"></div>
                                <div
                                    class="relative p-5 flex items-center justify-center bg-gray-800 rounded-lg group-hover:bg-gray-700 transition-all duration-300 tooltip"
                                    data-tip="{{$datatip[$index]}}">
                                    <i data-lucide="{{$icon}}"
                                       class="lucid-{{ $icon }} text-white text-opacity-70 text-5xl group-hover:text-opacity-100 transition-all duration-300"
                                       style="width: 6vh; height: 6vh;"></i>
                                </div>
                            </button>
                            @break
                        @case('book-text')
                            <div class="icon relative group">
                                <button id="agendaIcon" class="w-full">
                                    <div
                                        class="absolute inset-0 bg-gradient-to-br from-transparent to-transparent group-hover:from-white/10 group-hover:to-white/5 rounded-lg transition-all duration-300"></div>
                                    <div
                                        class="relative p-5 flex items-center justify-center bg-gray-800 rounded-lg group-hover:bg-gray-700 transition-all duration-300 tooltip"
                                        data-tip="{{$datatip[$index]}}">
                                        <i data-lucide="{{$icon}}"
                                           class="lucid-{{ $icon }} text-white text-opacity-70 text-5xl group-hover:text-opacity-100 transition-all duration-300"
                                           style="width: 6vh; height: 6vh;"></i>

                                    </div>
                                </button>
                            </div>
                            @break
                        @default
                            <a href="{{$icon_href[$index]}}">
                                <div
                                    class="absolute inset-0 bg-gradient-to-br from-transparent to-transparent group-hover:from-white/10 group-hover:to-white/5 rounded-lg transition-all duration-300"></div>
                                <div
                                    class="relative p-5 flex items-center justify-center bg-gray-800 rounded-lg group-hover:bg-gray-700 transition-all duration-300 tooltip"
                                    data-tip="{{$datatip[$index]}}">
                                    <i data-lucide="{{$icon}}"
                                       class="lucid-{{ $icon }} text-white text-opacity-70 text-5xl group-hover:text-opacity-100 transition-all duration-300"
                                       style="width: 6vh; height: 6vh;"></i>

                                </div>
                            </a>
                    @endswitch
                </div>
            @endforeach
        </div>
    </div>

    <div id="passwordModal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center hidden">
        <div id="modalContent"
             class="relative bg-white p-6 rounded-lg max-w-5xl mx-2 max-h-screen overflow-y-auto">
            <button class="absolute top-4 right-4 modal-close btn btn-sm btn-error">X</button>
            <div id="passwordSection" class="hidden">
                <h2 class="text-xl font-bold mb-4">Entrez votre mot de passe</h2>
                <form id="passwordForm">
                    <input type="password" id="password" class="input input-bordered w-full mb-4"
                           placeholder="Mot de passe" required>
                    <button type="submit" class="btn btn-primary w-full">Valider</button>
                </form>
            </div>
            <div id="contentSection" class="hidden">
                <!-- Section pour les emails -->
                <div id="emailsSection" class="hidden">
                    <input id="searchInput" type="text" placeholder="Rechercher..."
                           class="input input-bordered w-full mb-4"/>
                    <div class="overflow-y-auto max-h-96">
                        <table class="table w-full mt-4">
                            <thead>
                            <tr>
                                <th>Prénom</th>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody id="emailsTableBody">
                            </tbody>
                        </table>
                    </div>
                    <button id="addEmailButton" class="btn btn-sm btn-success mt-4">Ajouter une adresse</button>
                    <div id="addEmailSection" class="hidden mt-4">
                        <h3 class="text-lg font-bold mb-2">Ajouter une nouvelle adresse</h3>
                        <form id="addEmailForm">
                            <div class="flex space-x-2 mb-2">
                                <input type="text" name="first_name" class="input input-bordered w-full"
                                       placeholder="Prénom" required/>
                                <input type="text" name="last_name" class="input input-bordered w-full"
                                       placeholder="Nom"
                                       required/>
                                <select name="type" class="input input-bordered w-full mb-2" required>
                                    <option value="CNAM">CNAM</option>
                                    <option value="Administration">Administration</option>
                                    <option value="Professeur">Professeur</option>
                                    <option value="Élève">Élève</option>
                                    <option value="Autre">Autre</option>
                                </select>
                            </div>

                            <input type="email" name="email" class="input input-bordered w-full mb-2"
                                   placeholder="Email"
                                   required/>
                            <button type="submit" class="btn btn-primary w-full">Ajouter</button>
                        </form>
                    </div>
                </div>
                <!-- Section pour l'agenda -->
                <div id="agendaSection" class="hidden">
                    <h2 class="text-xl font-bold mb-4">Mes Devoirs</h2>
                    <div id="agendaContent" class="overflow-y-auto max-h-96">
                    </div>
                    <form id="addAssignmentForm" class="mt-4 hidden">
                        <div class="flex space-x-2 mb-2">
                            <select id="matiereSelectForm" name="matiere_id" class="select select-bordered w-full mt-1"
                                    required>
                            </select>
                            <select name="due_date" id="weekSelect" class="select select-bordered w-full mb-2">
                                <option value="">Chargement...</option>
                            </select>
                        </div>

                        <textarea name="description" class="input input-bordered w-full mb-2"
                                  placeholder="Description du devoir"></textarea>

                        <button type="submit" class="btn btn-primary w-full">Ajouter</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
