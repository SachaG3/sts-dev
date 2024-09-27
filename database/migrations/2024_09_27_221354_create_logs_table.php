<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogsTable extends Migration
{
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('date'); // Date de l'événement
            $table->string('type');    // Type de l'événement (ex : 'ecoledirect')
            $table->string('state');   // État de l'événement (ex : 'succès', 'échec')
            $table->text('message');   // Description de l'événement
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('logs');
    }
}

