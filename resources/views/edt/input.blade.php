@extends('include.app')

@section('content')
    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold mb-4">Entrer l'emploi du temps</h1>
        <form action="{{ route('edt.store') }}" method="POST" class="bg-base-200 p-4 rounded-lg shadow-lg">
            @csrf
            <div class="mb-4">
                <label for="json_data" class="block mb-2">JSON de l'emploi du temps</label>
                <textarea name="json_data" id="json_data" rows="10" class="textarea textarea-bordered w-full"
                          required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </form>
    </div>
@endsection
