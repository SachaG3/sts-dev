<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matiere extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'long_name', 'color'];

    public function profs()
    {
        return $this->belongsToMany(Prof::class, 'matiere_prof');
    }
}
