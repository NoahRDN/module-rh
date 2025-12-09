<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Supprimer la vue avant modification des colonnes
        DB::statement('DROP VIEW IF EXISTS view_types_conges_full');

        Schema::table('types_conges', function (Blueprint $table) {
            if (!Schema::hasColumn('types_conges', 'limite')) {
                $table->integer('limite')->nullable()->after('paye');
            }
            if (!Schema::hasColumn('types_conges', 'limite_frequence_id')) {
                $table->foreignId('limite_frequence_id')->nullable()->constrained('frequence_conges')->nullOnDelete()->after('limite');
            }
            if (Schema::hasColumn('types_conges', 'limite_par_mois')) {
                $table->dropColumn('limite_par_mois');
            }
            if (Schema::hasColumn('types_conges', 'limite_par_an')) {
                $table->dropColumn('limite_par_an');
            }
        });

        $this->recreateView();
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS view_types_conges_full');

        Schema::table('types_conges', function (Blueprint $table) {
            if (!Schema::hasColumn('types_conges', 'limite_par_mois')) {
                $table->integer('limite_par_mois')->nullable();
            }
            if (!Schema::hasColumn('types_conges', 'limite_par_an')) {
                $table->integer('limite_par_an')->nullable();
            }
            if (Schema::hasColumn('types_conges', 'limite_frequence_id')) {
                $table->dropConstrainedForeignId('limite_frequence_id');
            }
            if (Schema::hasColumn('types_conges', 'limite')) {
                $table->dropColumn('limite');
            }
        });

        $this->recreateView();
    }

    private function recreateView(): void
    {
        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW view_types_conges_full AS
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
