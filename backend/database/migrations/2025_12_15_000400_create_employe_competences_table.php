<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employe_competences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')->constrained('employes')->onDelete('cascade');
            $table->foreignId('competence_id')->constrained('competences')->onDelete('cascade');
            $table->integer('niveau')->default(1); // 1 à 5
            $table->date('date_evaluation')->nullable(); // Date de dernière évaluation
            $table->text('commentaire')->nullable();
            $table->foreignId('evalue_par')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['employe_id', 'competence_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employe_competences');
    }
};
