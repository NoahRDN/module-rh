<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Paramètres de rétention pour l'archivage légal des documents.
     * Configurable par type de document.
     */
    public function up(): void
    {
        Schema::create('archive_settings', function (Blueprint $table) {
            $table->id();
            
            // Code unique pour identifier le type de document
            $table->string('code')->unique();
            
            // Libellé descriptif
            $table->string('libelle');
            
            // Description détaillée
            $table->text('description')->nullable();
            
            // Durée de rétention en années
            $table->integer('retention_years')->default(5);
            
            // Durée de rétention en mois (pour plus de précision)
            $table->integer('retention_months')->default(0);
            
            // Action après expiration: 'archive', 'delete', 'review'
            $table->enum('action_on_expiry', ['archive', 'delete', 'review'])->default('archive');
            
            // Notification avant expiration (en jours)
            $table->integer('notify_before_days')->default(30);
            
            // Référence légale (ex: Code du travail Art. L3243-4)
            $table->string('legal_reference')->nullable();
            
            // Si actif
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });

        // Insertion des paramètres par défaut conformes à la législation française
        DB::table('archive_settings')->insert([
            [
                'code' => 'BULLETIN_PAIE',
                'libelle' => 'Bulletins de paie',
                'description' => 'Conservation des bulletins de paie des employés',
                'retention_years' => 5,
                'retention_months' => 0,
                'action_on_expiry' => 'archive',
                'notify_before_days' => 60,
                'legal_reference' => 'Code du travail Art. L3243-4',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'CONTRAT_TRAVAIL',
                'libelle' => 'Contrats de travail',
                'description' => 'Conservation des contrats de travail après fin de contrat',
                'retention_years' => 5,
                'retention_months' => 0,
                'action_on_expiry' => 'archive',
                'notify_before_days' => 90,
                'legal_reference' => 'Code du travail Art. L1221-1',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'REGISTRE_PERSONNEL',
                'libelle' => 'Registre unique du personnel',
                'description' => 'Conservation du registre après départ du salarié',
                'retention_years' => 5,
                'retention_months' => 0,
                'action_on_expiry' => 'archive',
                'notify_before_days' => 60,
                'legal_reference' => 'Code du travail Art. D1221-23',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'DOCUMENT_FORMATION',
                'libelle' => 'Documents de formation',
                'description' => 'Conservation des attestations et certificats de formation',
                'retention_years' => 6,
                'retention_months' => 0,
                'action_on_expiry' => 'archive',
                'notify_before_days' => 60,
                'legal_reference' => 'Code du travail Art. L6331-1',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'EVALUATION_PERFORMANCE',
                'libelle' => 'Évaluations de performance',
                'description' => 'Conservation des évaluations annuelles',
                'retention_years' => 3,
                'retention_months' => 0,
                'action_on_expiry' => 'archive',
                'notify_before_days' => 30,
                'legal_reference' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'JUSTIFICATIF_ABSENCE',
                'libelle' => 'Justificatifs d\'absence',
                'description' => 'Conservation des justificatifs médicaux et autres',
                'retention_years' => 3,
                'retention_months' => 0,
                'action_on_expiry' => 'delete',
                'notify_before_days' => 30,
                'legal_reference' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'DOCUMENT_IDENTITE',
                'libelle' => 'Documents d\'identité',
                'description' => 'Copies de pièces d\'identité',
                'retention_years' => 2,
                'retention_months' => 0,
                'action_on_expiry' => 'delete',
                'notify_before_days' => 30,
                'legal_reference' => 'RGPD',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'AUDIT_LOG',
                'libelle' => 'Journaux d\'audit',
                'description' => 'Conservation des traces d\'audit système',
                'retention_years' => 3,
                'retention_months' => 0,
                'action_on_expiry' => 'archive',
                'notify_before_days' => 30,
                'legal_reference' => 'RGPD Art. 30',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_settings');
    }
};
