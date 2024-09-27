<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cours extends Model
{
    protected $fillable = ['jour_id', 'heure_debut', 'heure_fin', 'matiere', 'matiere_id', 'professeur', 'salle'];

    public function jour()
    {
        return $this->belongsTo(Jour::class);
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class, 'matiere_id');
    }

}

