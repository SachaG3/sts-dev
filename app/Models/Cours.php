<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cours extends Model
{
    protected $fillable = ['jour_id', 'heure_debut', 'heure_fin', 'matiere', 'professeur', 'salle'];

    public function jour()
    {
        return $this->belongsTo(Jour::class);
    }
}
