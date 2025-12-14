<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des documents archivés pour la conformité légale.
     */
    public function up(): void
    {
        Schema::create('archived_documents', function (Blueprint $table) {
            $table->id();
            
            // Document original
            $table->string('document_type'); // Type de document (bulletin_paie, contrat, etc.)
            $table->unsignedBigInteger('original_id')->nullable(); // ID du document original
            $table->string('original_table')->nullable(); // Table d'origine
            
            // Employé concerné
            $table->foreignId('employe_id')
                ->nullable()
                ->constrained('employes')
                ->nullOnDelete();
            
            // Informations du document
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('fichier_path'); // Chemin vers le fichier archivé
            $table->string('fichier_hash')->nullable(); // Hash SHA256 pour l'intégrité
            $table->unsignedBigInteger('fichier_size')->nullable(); // Taille en octets
            
            // Dates importantes
            $table->date('date_document'); // Date du document original
            $table->date('date_archivage'); // Date d'archivage
            $table->date('date_expiration'); // Date d'expiration selon rétention
            
            // Paramètre de rétention utilisé
            $table->foreignId('archive_setting_id')
                ->nullable()
                ->constrained('archive_settings')
                ->nullOnDelete();
            
            // Statut de l'archive
            $table->enum('statut', ['actif', 'expire', 'supprime', 'en_revision'])->default('actif');
            
            // Utilisateur qui a archivé
            $table->foreignId('archived_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            // Métadonnées JSON (données originales du document)
            $table->json('metadata')->nullable();
            
            // Notes ou commentaires
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Index pour les recherches
            $table->index(['employe_id', 'document_type']);
            $table->index(['date_expiration', 'statut']);
            $table->index(['document_type', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archived_documents');
    }
};
