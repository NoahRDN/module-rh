<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paie_synthese_mensuelle', function (Blueprint $table) {
            $table->id();
            $table->string('mois', 7);
            $table->foreignId('employe_id')->constrained('employes')->cascadeOnDelete();
            $table->foreignId('contrat_id')->nullable()->constrained('contrats')->nullOnDelete();
            $table->foreignId('paie_id')->nullable()->constrained('paies')->nullOnDelete();
            $table->string('statut', 32);
            $table->string('statut_label')->nullable();
            $table->string('contrat_numero')->nullable();
            $table->date('contrat_debut')->nullable();
            $table->date('contrat_fin')->nullable();
            $table->string('employe_matricule')->nullable();
            $table->string('employe_nom')->nullable();
            $table->string('employe_prenom')->nullable();
            $table->string('source_montants', 32)->nullable();
            $table->string('source_montants_label')->nullable();
            $table->text('source_montants_description')->nullable();
            $table->boolean('est_prevision')->default(false);
            $table->decimal('salaire_base', 14, 2)->default(0);
            $table->decimal('total_brut', 14, 2)->default(0);
            $table->decimal('total_retenues', 14, 2)->default(0);
            $table->decimal('net_a_payer', 14, 2)->default(0);
            $table->decimal('retenue_cnaps', 14, 2)->default(0);
            $table->decimal('retenue_ostie', 14, 2)->default(0);
            $table->decimal('retenue_irsa', 14, 2)->default(0);
            $table->decimal('cnaps_employeur', 14, 2)->default(0);
            $table->decimal('ostie_employeur', 14, 2)->default(0);
            $table->decimal('charges_patronales', 14, 2)->default(0);
            $table->decimal('cotisations_a_reverser', 14, 2)->default(0);
            $table->decimal('salaire_previsionnel', 14, 2)->default(0);
            $table->decimal('net_a_payer_previsionnel', 14, 2)->default(0);
            $table->decimal('brut_previsionnel', 14, 2)->default(0);
            $table->date('paye_le')->nullable();
            $table->timestamp('demande_validation_le')->nullable();
            $table->timestamp('valide_le')->nullable();
            $table->foreignId('paiement_mouvement_id')->nullable()->constrained('caisse_mouvements')->nullOnDelete();
            $table->timestamp('paiement_demande_le')->nullable();
            $table->timestamp('paiement_valide_le')->nullable();
            $table->string('caisse_nom')->nullable();
            $table->json('details_paie')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['mois', 'employe_id'], 'paie_synthese_mois_employe_unique');
            $table->index(['mois', 'statut'], 'idx_paie_synthese_mois_statut');
            $table->index(['mois', 'paie_id'], 'idx_paie_synthese_mois_paie');
            $table->index(['employe_id', 'mois'], 'idx_paie_synthese_employe_mois');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paie_synthese_mensuelle');
    }
};
