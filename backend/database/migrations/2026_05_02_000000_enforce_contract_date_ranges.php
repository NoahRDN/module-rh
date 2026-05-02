<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('contrats')
            ->whereNotNull('date_fin')
            ->whereColumn('date_fin', '<', 'date_debut')
            ->update(['date_fin' => DB::raw('date_debut')]);

        DB::table('contrats')
            ->whereNotNull('periode_essai_debut')
            ->whereNotNull('periode_essai_fin')
            ->whereColumn('periode_essai_fin', '<', 'periode_essai_debut')
            ->update(['periode_essai_fin' => DB::raw('periode_essai_debut')]);

        if (Schema::hasTable('contrat_historiques')) {
            DB::table('contrat_historiques')
                ->whereNotNull('date_fin')
                ->whereColumn('date_fin', '<', 'date_debut')
                ->update(['date_fin' => DB::raw('date_debut')]);

            DB::table('contrat_historiques')
                ->whereNotNull('periode_essai_debut')
                ->whereNotNull('periode_essai_fin')
                ->whereColumn('periode_essai_fin', '<', 'periode_essai_debut')
                ->update(['periode_essai_fin' => DB::raw('periode_essai_debut')]);
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE contrats ADD CONSTRAINT contrats_date_range_check CHECK (date_fin IS NULL OR date_debut <= date_fin)");
            DB::statement("ALTER TABLE contrats ADD CONSTRAINT contrats_essai_date_range_check CHECK (periode_essai_debut IS NULL OR periode_essai_fin IS NULL OR periode_essai_debut <= periode_essai_fin)");

            if (Schema::hasTable('contrat_historiques')) {
                DB::statement("ALTER TABLE contrat_historiques ADD CONSTRAINT contrat_historiques_date_range_check CHECK (date_fin IS NULL OR date_debut <= date_fin)");
                DB::statement("ALTER TABLE contrat_historiques ADD CONSTRAINT contrat_historiques_essai_date_range_check CHECK (periode_essai_debut IS NULL OR periode_essai_fin IS NULL OR periode_essai_debut <= periode_essai_fin)");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE contrats DROP CONSTRAINT IF EXISTS contrats_date_range_check');
            DB::statement('ALTER TABLE contrats DROP CONSTRAINT IF EXISTS contrats_essai_date_range_check');

            if (Schema::hasTable('contrat_historiques')) {
                DB::statement('ALTER TABLE contrat_historiques DROP CONSTRAINT IF EXISTS contrat_historiques_date_range_check');
                DB::statement('ALTER TABLE contrat_historiques DROP CONSTRAINT IF EXISTS contrat_historiques_essai_date_range_check');
            }
        }
    }
};
