<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')->constrained('employes')->onDelete('cascade');
            $table->foreignId('evaluateur_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('date_evaluation');
            $table->string('periode'); // Format: YYYY-MM pour mensuel
            $table->decimal('score_global', 5, 2)->default(0); // Score en pourcentage 0-100
            $table->text('points_forts')->nullable();
            $table->text('axes_amelioration')->nullable();
            $table->text('objectifs')->nullable();
            $table->text('commentaire_general')->nullable();
            $table->enum('statut', ['brouillon', 'valide', 'archive'])->default('brouillon');
            $table->timestamps();
            
            // Index pour éviter les doublons d'évaluation par employé/période
            $table->unique(['employe_id', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
