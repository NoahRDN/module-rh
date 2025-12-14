<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formation_employes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')->constrained('employes')->onDelete('cascade');
            $table->foreignId('formation_id')->constrained('formations')->onDelete('cascade');
            $table->enum('statut', ['planifiee', 'en_cours', 'terminee', 'annulee'])->default('planifiee');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->decimal('note', 5, 2)->nullable(); // Note obtenue si applicable
            $table->text('commentaire')->nullable();
            $table->boolean('certificat_obtenu')->default(false);
            $table->foreignId('demande_par')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('valide_par')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['employe_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formation_employes');
    }
};
