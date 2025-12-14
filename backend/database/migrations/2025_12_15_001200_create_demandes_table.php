<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique(); // Numéro de demande généré
            $table->foreignId('employe_id')->constrained('employes')->onDelete('cascade');
            $table->foreignId('type_demande_id')->constrained('types_demandes')->onDelete('cascade');
            $table->enum('statut', ['brouillon', 'soumise', 'en_cours', 'approuvee', 'rejetee', 'annulee'])->default('brouillon');
            $table->text('motif')->nullable(); // Motif de la demande
            $table->json('donnees')->nullable(); // Données spécifiques selon le type
            $table->decimal('montant', 10, 2)->nullable(); // Pour les remboursements
            $table->text('commentaire_employe')->nullable();
            $table->text('commentaire_rh')->nullable();
            $table->date('date_soumission')->nullable();
            $table->date('date_traitement')->nullable();
            $table->foreignId('traite_par')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['employe_id', 'statut']);
            $table->index(['type_demande_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes');
    }
};
