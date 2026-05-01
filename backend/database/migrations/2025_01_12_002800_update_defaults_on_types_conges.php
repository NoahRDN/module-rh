<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // La vue dépend des colonnes, on la supprime avant d'altérer puis on la recrée.
        DB::statement('DROP VIEW IF EXISTS view_types_conges_full');

        Schema::table('types_conges', function (Blueprint $table) {
            $table->boolean('utilise_solde')->default(false)->change();
            $table->boolean('cumulable')->default(false)->change();
        });

        DB::statement('UPDATE types_conges SET utilise_solde = false WHERE utilise_solde IS NULL');
        DB::statement('UPDATE types_conges SET cumulable = false WHERE cumulable IS NULL');

        // Recréation de la vue
        DB::statement(<<<'SQL'
CREATE VIEW view_types_conges_full AS
SELECT
    tc.id,
    tc.libelle,
    tc.code,
    tc.jours_forfait,
    tc.utilise_solde,
    tc.paye,
    tc.limite,
    tc.limite_frequence_id,
    lf.code AS limite_frequence_code,
    lf.libelle AS limite_frequence_libelle,
    tc.frequence_id,
    f.code AS frequence_code,
    f.libelle AS frequence_libelle,
    tc.cumulable,
    tc.cumulable_duree,
    tc.cumulable_frequence_id,
    cf.code AS cumulable_frequence_code,
    cf.libelle AS cumulable_frequence_libelle,
    tc.justificatif_obligatoire,
    tc.sexe_autorise,
    tc.description,
    tc.created_at,
    tc.updated_at
FROM types_conges tc
LEFT JOIN frequence_conges f ON f.id = tc.frequence_id
LEFT JOIN frequence_conges cf ON cf.id = tc.cumulable_frequence_id
LEFT JOIN frequence_conges lf ON lf.id = tc.limite_frequence_id;
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS view_types_conges_full');

        Schema::table('types_conges', function (Blueprint $table) {
            $table->boolean('utilise_solde')->default(true)->change();
            $table->boolean('cumulable')->default(true)->change();
        });

        DB::statement(<<<'SQL'
CREATE VIEW view_types_conges_full AS
SELECT
    tc.id,
    tc.libelle,
    tc.code,
    tc.jours_forfait,
    tc.utilise_solde,
    tc.paye,
    tc.limite,
    tc.limite_frequence_id,
    lf.code AS limite_frequence_code,
    lf.libelle AS limite_frequence_libelle,
    tc.frequence_id,
    f.code AS frequence_code,
    f.libelle AS frequence_libelle,
    tc.cumulable,
    tc.cumulable_duree,
    tc.cumulable_frequence_id,
    cf.code AS cumulable_frequence_code,
    cf.libelle AS cumulable_frequence_libelle,
    tc.justificatif_obligatoire,
    tc.sexe_autorise,
    tc.description,
    tc.created_at,
    tc.updated_at
FROM types_conges tc
LEFT JOIN frequence_conges f ON f.id = tc.frequence_id
LEFT JOIN frequence_conges cf ON cf.id = tc.cumulable_frequence_id
LEFT JOIN frequence_conges lf ON lf.id = tc.limite_frequence_id;
SQL);
    }
};
