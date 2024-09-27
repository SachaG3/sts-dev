<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameMatiereColumnInCoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cours', function (Blueprint $table) {
            // Renommer la colonne 'matiere' en 'matiere_nom'
            $table->renameColumn('matiere', 'matiere_nom');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cours', function (Blueprint $table) {
            // Si besoin de revenir en arrière, renommer 'matiere_nom' en 'matiere'
            $table->renameColumn('matiere_nom', 'matiere');
        });
    }
}
