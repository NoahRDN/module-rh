<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS view_solde_conges');
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
CREATE VIEW view_solde_conges AS
WITH latest AS (
    SELECT employe_id, type_conge_id, MAX(acquis_first) AS last_acquis
    FROM acquis_conges
    GROUP BY employe_id, type_conge_id
),
filtered AS (
    SELECT ac.*
    FROM acquis_conges ac
    JOIN latest l
      ON l.employe_id = ac.employe_id
     AND l.type_conge_id = ac.type_conge_id
    WHERE ac.acquis_first >= l.last_acquis - interval '3 year'
)
SELECT
    row_number() OVER () AS id,
    f.employe_id,
    e.matricule AS employe_matricule,
    e.nom AS employe_nom,
    e.prenom AS employe_prenom,
    f.type_conge_id,
    tc.libelle AS type_conge_libelle,
    tc.code AS type_conge_code,
    MIN(f.acquis_first) AS acquis_first,
    MAX(f.expire_first) AS expire_first,
    MIN(f.id) FILTER (WHERE f.id IS NOT NULL) AS premier_acquis_id,
    MIN(f.acquis_le) AS premier_acquis,
    MAX(f.expire_le) AS derniere_expiration,
    MIN(date_trunc('month', f.acquis_le) + interval '1 month') AS disponible_le,
    SUM(f.jours_acquis) AS total_acquis,
    COALESCE(SUM(cons.jours_utilises), 0) AS total_utilise,
    SUM(f.jours_acquis) - COALESCE(SUM(cons.jours_utilises), 0) AS solde_actuel,
    SUM(f.jours_acquis) - COALESCE(SUM(cons.jours_utilises), 0) AS solde_annuel
FROM filtered f
JOIN employes e ON e.id = f.employe_id
JOIN types_conges tc ON tc.id = f.type_conge_id
LEFT JOIN consommations_conges cons ON cons.acquis_conge_id = f.id
GROUP BY f.employe_id, e.matricule, e.nom, e.prenom, f.type_conge_id, tc.libelle, tc.code;
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS view_solde_conges');
    }
};
