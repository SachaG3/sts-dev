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
        Schema::table('cours', function (Blueprint $table) {
            $table->foreignId('matiere_id')->nullable()->constrained('matieres')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('cours', function (Blueprint $table) {
            $table->dropForeign(['matiere_id']);
            $table->dropColumn('matiere_id');
        });
    }

};
