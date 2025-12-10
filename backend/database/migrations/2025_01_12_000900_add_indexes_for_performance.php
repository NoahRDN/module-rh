<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->index('poste_id', 'idx_employes_poste');
            $table->index('departement_id', 'idx_employes_departement');
            if (Schema::hasColumn('employes', 'actif')) {
                $table->index('actif', 'idx_employes_actif');
            }
        });

        Schema::table('contrats', function (Blueprint $table) {
            $table->index('employe_id', 'idx_contrats_employe');
            $table->index('type_contrat', 'idx_contrats_type');
            $table->index('statut', 'idx_contrats_statut');
            $table->index('date_debut', 'idx_contrats_date_debut');
            $table->index('date_fin', 'idx_contrats_date_fin');
        });

        Schema::table('demandes_conges', function (Blueprint $table) {
            $table->index('employe_id', 'idx_demandes_employe');
            // $table->index('type_id', 'idx_demandes_type');
            $table->index('statut', 'idx_demandes_statut');
            $table->index(['date_debut', 'date_fin'], 'idx_demandes_dates');
            $table->index('approuve_par', 'idx_demandes_approbateur');
        });

        Schema::table('soldes_conges', function (Blueprint $table) {
            $table->index('employe_id', 'idx_soldes_employe');
            // $table->index('type_id', 'idx_soldes_type');
        });

        Schema::table('documents_employes', function (Blueprint $table) {
            $table->index('employe_id', 'idx_docs_employe');
            $table->index('type_document', 'idx_docs_type');
            $table->index('date_expiration', 'idx_docs_exp');
        });

        Schema::table('historique_postes', function (Blueprint $table) {
            $table->index('employe_id', 'idx_hist_postes_employe');
            $table->index('poste_id', 'idx_hist_postes_poste');
            $table->index('departement_id', 'idx_hist_postes_departement');
            $table->index('date_changement', 'idx_hist_postes_date');
        });

        Schema::table('pointages', function (Blueprint $table) {
            $table->index('type', 'idx_pointages_type');
        });
    }

    public function down(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->dropIndex('idx_employes_poste');
            $table->dropIndex('idx_employes_departement');
            if (Schema::hasColumn('employes', 'actif')) {
                $table->dropIndex('idx_employes_actif');
            }
        });

        Schema::table('contrats', function (Blueprint $table) {
            $table->dropIndex('idx_contrats_employe');
            $table->dropIndex('idx_contrats_type');
            $table->dropIndex('idx_contrats_statut');
            $table->dropIndex('idx_contrats_date_debut');
            $table->dropIndex('idx_contrats_date_fin');
        });

        Schema::table('demandes_conges', function (Blueprint $table) {
            $table->dropIndex('idx_demandes_employe');
            $table->dropIndex('idx_demandes_type');
            $table->dropIndex('idx_demandes_statut');
            $table->dropIndex('idx_demandes_dates');
            $table->dropIndex('idx_demandes_approbateur');
        });

        Schema::table('soldes_conges', function (Blueprint $table) {
            $table->dropIndex('idx_soldes_employe');
            $table->dropIndex('idx_soldes_type');
        });

        Schema::table('documents_employes', function (Blueprint $table) {
            $table->dropIndex('idx_docs_employe');
            $table->dropIndex('idx_docs_type');
            $table->dropIndex('idx_docs_exp');
        });

        Schema::table('historique_postes', function (Blueprint $table) {
            $table->dropIndex('idx_hist_postes_employe');
            $table->dropIndex('idx_hist_postes_poste');
            $table->dropIndex('idx_hist_postes_departement');
            $table->dropIndex('idx_hist_postes_date');
        });

        Schema::table('pointages', function (Blueprint $table) {
            $table->dropIndex('idx_pointages_type');
        });
    }
};
