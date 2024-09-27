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
        Schema::table('emails', function (Blueprint $table) {
            $table->enum('type', ['CNAM', 'Administration', 'Professeur', 'Élève', 'Autre'])->default('Autre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};

