<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS view_solde_conges');

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW view_solde_conges AS
SELECT
    row_number() OVER () AS id,
    t.employe_id,
    t.employe_matricule,
    t.employe_nom,
    t.employe_prenom,
    t.type_conge_id,
    t.type_conge_libelle,
    t.type_conge_code,
    t.acquis_first,
    t.expire_first,
    t.premier_acquis_id,
    t.premier_acquis,
    t.derniere_expiration,
    t.disponible_le,
    t.total_acquis,
    t.total_utilise,
    t.solde_actuel,
    t.solde_annuel
FROM (
    -- Agrégation pour les congés PAYE (normal)
    SELECT
        ac.employe_id,
        e.matricule AS employe_matricule,
        e.nom AS employe_nom,
        e.prenom AS employe_prenom,
        ac.type_conge_id,
        tc.libelle AS type_conge_libelle,
        tc.code AS type_conge_code,
        MIN(ac.acquis_first) AS acquis_first,
        MAX(ac.expire_first) AS expire_first,
        MIN(ac.id) FILTER (WHERE ac.id IS NOT NULL) AS premier_acquis_id,
        MIN(ac.acquis_le) AS premier_acquis,
        MAX(ac.expire_le) AS derniere_expiration,
        MIN(date_trunc('month', ac.acquis_le) + interval '1 month') AS disponible_le,
        SUM(ac.jours_acquis) AS total_acquis,
        COALESCE(SUM(cons.jours_utilises), 0) AS total_utilise,
        SUM(ac.jours_acquis) - COALESCE(SUM(cons.jours_utilises), 0) AS solde_actuel,
        SUM(ac.jours_acquis) - COALESCE(SUM(cons.jours_utilises), 0) AS solde_annuel
    FROM acquis_conges ac
    JOIN employes e ON e.id = ac.employe_id
    JOIN types_conges tc ON tc.id = ac.type_conge_id
    LEFT JOIN consommations_conges cons ON cons.acquis_conge_id = ac.id
    WHERE tc.code = 'PAYE'
    GROUP BY ac.employe_id, e.matricule, e.nom, e.prenom, ac.type_conge_id, tc.libelle, tc.code

    UNION ALL

    -- Détail unitaire pour les autres types (non PAYE)
    SELECT
        ac.employe_id,
        e.matricule AS employe_matricule,
        e.nom AS employe_nom,
        e.prenom AS employe_prenom,
        ac.type_conge_id,
        tc.libelle AS type_conge_libelle,
        tc.code AS type_conge_code,
        ac.acquis_le AS acquis_first,
        ac.expire_first AS expire_first,
        ac.id AS premier_acquis_id,
        ac.acquis_le AS premier_acquis,
        ac.expire_le AS derniere_expiration,
        (date_trunc('month', ac.acquis_le) + interval '1 month') AS disponible_le,
        ac.jours_acquis AS total_acquis,
        COALESCE(SUM(cons.jours_utilises), 0) AS total_utilise,
        ac.jours_acquis - COALESCE(SUM(cons.jours_utilises), 0) AS solde_actuel,
        ac.jours_acquis - COALESCE(SUM(cons.jours_utilises), 0) AS solde_annuel
    FROM acquis_conges ac
    JOIN employes e ON e.id = ac.employe_id
    JOIN types_conges tc ON tc.id = ac.type_conge_id
    LEFT JOIN consommations_conges cons ON cons.acquis_conge_id = ac.id
    WHERE tc.code <> 'PAYE'
    GROUP BY ac.id, ac.employe_id, e.matricule, e.nom, e.prenom, ac.type_conge_id, tc.libelle, tc.code, ac.acquis_le, ac.expire_first, ac.expire_le
) t;
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS view_solde_conges');
    }
};
