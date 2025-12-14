<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poste_competences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poste_id')->constrained('postes')->onDelete('cascade');
            $table->foreignId('competence_id')->constrained('competences')->onDelete('cascade');
            $table->integer('niveau_requis')->default(1); // Niveau minimum requis (1-5)
            $table->boolean('obligatoire')->default(true); // Compétence obligatoire ou souhaitée
            $table->integer('poids')->default(1); // Importance pour le matching (1-10)
            $table->timestamps();

            $table->unique(['poste_id', 'competence_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poste_competences');
    }
};
