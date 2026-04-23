<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paies', function (Blueprint $table) {
            if (!Schema::hasColumn('paies', 'statut')) {
                $table->string('statut', 32)->default('en_attente_validation')->after('mois');
                $table->index(['mois', 'statut'], 'idx_paies_mois_statut');
            }
            if (!Schema::hasColumn('paies', 'demande_validation_le')) {
                $table->timestamp('demande_validation_le')->nullable()->after('statut');
            }
            if (!Schema::hasColumn('paies', 'valide_le')) {
                $table->timestamp('valide_le')->nullable()->after('paye_le');
            }
        });
    }

    public function down(): void
    {
        Schema::table('paies', function (Blueprint $table) {
            if (Schema::hasColumn('paies', 'valide_le')) {
                $table->dropColumn('valide_le');
            }
            if (Schema::hasColumn('paies', 'demande_validation_le')) {
                $table->dropColumn('demande_validation_le');
            }
            if (Schema::hasColumn('paies', 'statut')) {
                $table->dropIndex('idx_paies_mois_statut');
                $table->dropColumn('statut');
            }
        });
    }
};
