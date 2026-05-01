<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paies', function (Blueprint $table) {
            $table->index(['employe_id', 'mois'], 'idx_paies_employe_mois');
        });

        Schema::table('caisse_mouvements', function (Blueprint $table) {
            $table->index(['created_at', 'caisse_id', 'statut'], 'idx_caisse_mouvements_created_caisse_statut');
            $table->index(['statut', 'created_at'], 'idx_caisse_mouvements_statut_created');
        });
    }

    public function down(): void
    {
        Schema::table('caisse_mouvements', function (Blueprint $table) {
            $table->dropIndex('idx_caisse_mouvements_statut_created');
            $table->dropIndex('idx_caisse_mouvements_created_caisse_statut');
        });

        Schema::table('paies', function (Blueprint $table) {
            $table->dropIndex('idx_paies_employe_mois');
        });
    }
};
