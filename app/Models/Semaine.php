<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semaine extends Model
{
    protected $fillable = ['numero', 'dates', 'annee_scolaire', 'formation', 'json_data', 'total_heures', 'par_option', 'date_edition'];

    public function jours()
    {
        return $this->hasMany(Jour::class);
    }
}
