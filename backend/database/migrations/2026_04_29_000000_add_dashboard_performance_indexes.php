<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $indexes = [
            'CREATE INDEX IF NOT EXISTS idx_employes_date_embauche ON employes (date_embauche)',
            'CREATE INDEX IF NOT EXISTS idx_employes_created_at ON employes (created_at)',
            'CREATE INDEX IF NOT EXISTS idx_employes_nom_prenom_matricule ON employes (nom, prenom, matricule)',
            'CREATE INDEX IF NOT EXISTS idx_contrats_active_lookup ON contrats (employe_id, date_debut, date_fin)',
            'CREATE INDEX IF NOT EXISTS idx_contrats_date_fin_employe ON contrats (date_fin, employe_id) WHERE date_fin IS NOT NULL',
            'CREATE INDEX IF NOT EXISTS idx_demandes_conges_statut_created ON demandes_conges (statut, created_at)',
            'CREATE INDEX IF NOT EXISTS idx_demandes_conges_statut_debut ON demandes_conges (statut, date_debut)',
            'CREATE INDEX IF NOT EXISTS idx_demandes_conges_employe_dates ON demandes_conges (employe_id, date_debut, date_fin)',
            'CREATE INDEX IF NOT EXISTS idx_demandes_conges_type_debut ON demandes_conges (type_conge_id, date_debut)',
            'CREATE INDEX IF NOT EXISTS idx_pointages_employe_pointe_type ON pointages (employe_id, pointe_a, type)',
            'CREATE INDEX IF NOT EXISTS idx_evaluations_statut_periode ON evaluations (statut, periode)',
            'CREATE INDEX IF NOT EXISTS idx_evaluations_date ON evaluations (date_evaluation)',
            'CREATE INDEX IF NOT EXISTS idx_calendrier_evenements_type_debut ON calendrier_evenements (type, date_debut)',
            'CREATE INDEX IF NOT EXISTS idx_calendrier_evenements_employe_debut ON calendrier_evenements (employe_id, date_debut)',
            'CREATE INDEX IF NOT EXISTS idx_jours_feries_recurrent_date ON jours_feries (recurrent, date)',
            'CREATE INDEX IF NOT EXISTS idx_devises_active_code ON devises (active, code)',
            'CREATE INDEX IF NOT EXISTS idx_entreprise_settings_devise ON entreprise_settings (devise)',
            'CREATE INDEX IF NOT EXISTS idx_notifications_user_lu_created ON notifications (user_id, lu, created_at DESC)',
        ];

        foreach ($indexes as $sql) {
            DB::statement($sql);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $indexes = [
            'idx_employes_date_embauche',
            'idx_employes_created_at',
            'idx_employes_nom_prenom_matricule',
            'idx_contrats_active_lookup',
            'idx_contrats_date_fin_employe',
            'idx_demandes_conges_statut_created',
            'idx_demandes_conges_statut_debut',
            'idx_demandes_conges_employe_dates',
            'idx_demandes_conges_type_debut',
            'idx_pointages_employe_pointe_type',
            'idx_evaluations_statut_periode',
            'idx_evaluations_date',
            'idx_calendrier_evenements_type_debut',
            'idx_calendrier_evenements_employe_debut',
            'idx_jours_feries_recurrent_date',
            'idx_devises_active_code',
            'idx_entreprise_settings_devise',
            'idx_notifications_user_lu_created',
        ];

        foreach ($indexes as $index) {
            DB::statement("DROP INDEX IF EXISTS {$index}");
        }
    }
};
