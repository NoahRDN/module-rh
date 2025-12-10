<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Supprimer la vue existante si elle dépend d'anciennes colonnes
        DB::statement('DROP VIEW IF EXISTS view_solde_conges');

        // Supprimer les tables devenues inutiles
        if (Schema::hasTable('soldes_conges')) {
            Schema::drop('soldes_conges');
        }
        if (Schema::hasTable('absences_types')) {
            Schema::drop('absences_types');
        }

        // Retirer la colonne jours_utilises qui n'est plus utilisée
        Schema::table('acquis_conges', function (Blueprint $table) {
            if (Schema::hasColumn('acquis_conges', 'jours_utilises')) {
                $table->dropColumn('jours_utilises');
            }
        });

        // Créer une vue de solde calculé à partir d'acquis + consommations
        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW view_solde_conges AS
SELECT
    row_number() OVER () AS id,
    ac.employe_id,
    e.matricule AS employe_matricule,
    e.nom AS employe_nom,
    e.prenom AS employe_prenom,
    ac.type_conge_id,
    tc.libelle AS type_conge_libelle,
    tc.code AS type_conge_code,
    MIN(ac.id) FILTER (WHERE ac.id IS NOT NULL) AS premier_acquis_id,
    MIN(ac.acquis_le) AS premier_acquis,
    MAX(ac.expire_le) AS derniere_expiration,
    -- disponible à partir du 1er jour du mois suivant l'acquisition
    MIN(date_trunc('month', ac.acquis_le) + interval '1 month') AS disponible_le,
    SUM(ac.jours_acquis) AS total_acquis,
    COALESCE(SUM(cons.jours_utilises), 0) AS total_utilise,
    SUM(ac.jours_acquis) - COALESCE(SUM(cons.jours_utilises), 0) AS solde_actuel,
    SUM(ac.jours_acquis) - COALESCE(SUM(cons.jours_utilises), 0) AS solde_annuel
FROM acquis_conges ac
JOIN employes e ON e.id = ac.employe_id
JOIN types_conges tc ON tc.id = ac.type_conge_id
LEFT JOIN consommations_conges cons ON cons.acquis_conge_id = ac.id
WHERE ac.expire_le >= CURRENT_DATE
  AND (date_trunc('month', ac.acquis_le) + interval '1 month') <= CURRENT_DATE
GROUP BY ac.employe_id, e.matricule, e.nom, e.prenom, ac.type_conge_id, tc.libelle, tc.code;
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS view_solde_conges');

        // Restaurer la colonne si besoin
        Schema::table('acquis_conges', function (Blueprint $table) {
            if (!Schema::hasColumn('acquis_conges', 'jours_utilises')) {
                $table->decimal('jours_utilises', 8, 2)->default(0);
            }
        });
    }
};
