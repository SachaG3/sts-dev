<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMatiereProfTable extends Migration
{
    public function up()
    {
        Schema::create('matiere_prof', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prof_id')->constrained('profs');
            $table->foreignId('matiere_id')->constrained('matieres');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('matiere_prof');
    }
}
