<?php

namespace App\Http\Controllers;

use App\Models\Matiere;
use App\Models\Prof;
use Illuminate\Http\Request;

class MatiereController extends Controller
{
    public function index()
    {
        $matieres = Matiere::all();
        $profs = Prof::all();

        return view('education.index', compact('matieres', 'profs'));
    }

    public function storeMatiere(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'long_name' => 'nullable|string|max:255',
        ]);

        Matiere::create($request->all());

        return redirect()->route('education.index')->with('success', 'Matière créée avec succès.');
    }

    public function storeProf(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
        ]);

        Prof::create($request->all());

        return redirect()->route('education.index')->with('success', 'Professeur créé avec succès.');
    }

    public function storeMatiereProf(Request $request)
    {
        $request->validate([
            'matiere_id' => 'required|exists:matieres,id',
            'prof_id' => 'required|exists:profs,id',
        ]);

        $matiere = Matiere::find($request->matiere_id);
        $matiere->profs()->attach($request->prof_id);

        return redirect()->route('education.index')->with('success', 'Association créée avec succès.');
    }
}
