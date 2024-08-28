<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('semaines', function (Blueprint $table) {
            $table->id();
            $table->integer('numero');
            $table->string('dates');
            $table->string('annee_scolaire');
            $table->string('formation');
            $table->json('json_data');
            $table->float('total_heures');
            $table->float('par_option');
            $table->dateTime('date_edition');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semaines');
    }
};
