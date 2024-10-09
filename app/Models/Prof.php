<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prof extends Model
{
    use HasFactory;

    protected $fillable = ['first_name', 'last_name'];

    public function matieres()
    {
        return $this->belongsToMany(Matiere::class, 'matiere_prof');
    }
}
