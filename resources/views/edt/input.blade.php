@extends("include/app")
@section('head')
    <title>Ajouter un json - Licence STS Dev</title>
@endsection
@section('content')
    <div class="flex items-center justify-center min-h-screen bg-base-200">
        <div class="w-full max-w-2xl p-8 bg-base-100 rounded-xl shadow-2xl">
            <h1 class="text-3xl font-bold mb-6 text-center text-primary">Entrer l'emploi du temps</h1>
            <form action="{{ route('edt.store') }}" method="POST" class="space-y-6">
                @csrf
                <div>
                    <textarea
                        name="json_data"
                        id="json_data"
                        rows="10"
                        class="textarea textarea-bordered w-full resize-none focus:textarea-primary"
                        placeholder="Collez votre JSON ici..."
                        required
                    ></textarea>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <button type="reset" class="btn btn-outline">Réinitialiser</button>
                </div>
            </form>
        </div>
    </div>
@endsection
