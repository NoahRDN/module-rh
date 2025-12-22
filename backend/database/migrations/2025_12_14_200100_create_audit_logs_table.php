<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table de journalisation des actions pour l'audit.
     * Enregistre toutes les actions CRUD avec les valeurs avant/après.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            
            // Utilisateur qui a effectué l'action
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            // Type d'action effectuée
            $table->enum('action', ['create', 'update', 'delete', 'login', 'logout', 'view', 'export', 'approve', 'reject', 'archive'])
                ->index();
            
            // Entité concernée (nom de la table/modèle)
            $table->string('auditable_type')->index();
            $table->unsignedBigInteger('auditable_id')->nullable()->index();
            
            // Description lisible de l'action
            $table->string('description')->nullable();
            
            // Valeurs avant modification (JSON)
            $table->json('old_values')->nullable();
            
            // Valeurs après modification (JSON)
            $table->json('new_values')->nullable();
            
            // Informations de contexte
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();
            
            // Métadonnées additionnelles
            $table->json('metadata')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            
            // Index composite pour les recherches fréquentes
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
