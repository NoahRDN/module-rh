<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jours_feries', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->date('date');
            $table->boolean('recurrent')->default(false); // si vrai, revient chaque année à la même date (jour/mois)
            $table->timestamps();
            $table->unique(['date', 'recurrent']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jours_feries');
    }
};
