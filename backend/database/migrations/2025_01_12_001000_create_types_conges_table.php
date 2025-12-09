<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('types_conges', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('code')->unique();
            $table->decimal('jours_forfait', 8, 2)->nullable();
            $table->boolean('utilise_solde')->default(true);
            $table->boolean('paye')->default(true);
            $table->integer('limite_par_an')->nullable();
            $table->integer('limite_par_mois')->nullable();
            $table->boolean('justificatif_obligatoire')->default(false);
            $table->enum('sexe_autorise', ['homme', 'femme'])->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_conges');
    }
};
