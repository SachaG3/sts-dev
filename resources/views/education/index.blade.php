@extends('include.app')

@section('content')
    <div class="container mx-auto p-8">
        <div class="bg-white shadow rounded-lg p-6">
            <h1 class="text-3xl font-bold mb-8 text-center text-gray-800">Gestion des Matières, Professeurs et
                Associations</h1>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6"
                     role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            {{-- Formulaire pour créer une matière --}}
            <div class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">Créer une Matière</h2>
                <form action="{{ route('education.storeMatiere') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nom de la matière</label>
                        <input type="text" id="name" name="name" class="input input-bordered w-full mt-1" required>
                    </div>
                    <div>
                        <label for="long_name" class="block text-sm font-medium text-gray-700">Nom long de la
                            matière</label>
                        <input type="text" id="long_name" name="long_name" class="input input-bordered w-full mt-1">
                    </div>
                    <button type="submit" class="btn btn-primary w-full">Créer Matière</button>
                </form>
            </div>

            {{-- Liste des matières --}}
            <div class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">Liste des Matières</h2>
                <ul class="list-disc list-inside">
                    @foreach($matieres as $matiere)
                        <li class="py-2 px-4 bg-gray-50 rounded-lg shadow mb-2">
                            <strong>{{ $matiere->name }}</strong>
                            @if($matiere->long_name)
                                <p class="text-gray-600 text-sm">{{ $matiere->long_name }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Formulaire pour créer un professeur --}}
            <div class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">Créer un Professeur</h2>
                <form action="{{ route('education.storeProf') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700">Prénom</label>
                        <input type="text" id="first_name" name="first_name" class="input input-bordered w-full mt-1"
                               required>
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700">Nom</label>
                        <input type="text" id="last_name" name="last_name" class="input input-bordered w-full mt-1"
                               required>
                    </div>
                    <button type="submit" class="btn btn-primary w-full">Créer Professeur</button>
                </form>
            </div>

            {{-- Liste des professeurs --}}
            <div class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">Liste des Professeurs</h2>
                <ul class="list-disc list-inside">
                    @foreach($profs as $prof)
                        <li class="py-2 px-4 bg-gray-50 rounded-lg shadow mb-2">{{ $prof->first_name }} {{ $prof->last_name }}</li>
                    @endforeach
                </ul>
            </div>

            {{-- Formulaire pour associer un professeur à une matière --}}
            <div class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">Associer une Matière à un Professeur</h2>
                <form action="{{ route('education.storeMatiereProf') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="matiere_id" class="block text-sm font-medium text-gray-700">Matière</label>
                        <select id="matiere_id" name="matiere_id" class="select select-bordered w-full mt-1" required>
                            @foreach($matieres as $matiere)
                                <option value="{{ $matiere->id }}">{{ $matiere->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="prof_id" class="block text-sm font-medium text-gray-700">Professeur</label>
                        <select id="prof_id" name="prof_id" class="select select-bordered w-full mt-1" required>
                            @foreach($profs as $prof)
                                <option value="{{ $prof->id }}">{{ $prof->first_name }} {{ $prof->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-full">Associer</button>
                </form>
            </div>
        </div>
    </div>
@endsection
