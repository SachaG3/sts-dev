<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = ['description', 'due_date', 'matiere_id'];

    public function matiere()
    {
        return $this->belongsTo(Matiere::class);
    }
}
