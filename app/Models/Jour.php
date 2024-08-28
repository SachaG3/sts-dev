<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jour extends Model
{
    protected $fillable = ['semaine_id', 'jour', 'date'];

    public function semaine()
    {
        return $this->belongsTo(Semaine::class);
    }

    public function cours()
    {
        return $this->hasMany(Cours::class);
    }


}
