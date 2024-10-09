@extends('layouts.app')

@section('content')
    <div class="container mx-auto mt-5">
        <h2 class="text-2xl font-bold mb-4">Modifier le Cours</h2>

        <form action="{{ route('cours.update', $cours->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="matiere_id" class="block text-sm font-medium text-gray-700">Matière</label>
                <select name="matiere_id" id="matiere_id" class="form-select mt-1 block w-full">
                    @foreach ($matieres as $matiere)
                        <option value="{{ $matiere->id }}" {{ $cours->matiere_id == $matiere->id ? 'selected' : '' }}>
                            {{ $matiere->nom }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="enseignant_id" class="block text-sm font-medium text-gray-700">Enseignant</label>
            </div>

            <div class="mb-4">
                <label for="salle" class="block text-sm font-medium text-gray-700">Salle</label>
                <input type="text" name="salle" id="salle" class="form-input mt-1 block w-full"
                       value="{{ $cours->salle }}">
            </div>

            <div class="mb-4">
                <button type="submit" class="btn btn-primary">Mettre à jour</button>
            </div>
        </form>
    </div>
@endsection
