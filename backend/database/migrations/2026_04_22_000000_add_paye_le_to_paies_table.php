<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paies', function (Blueprint $table) {
            if (!Schema::hasColumn('paies', 'paye_le')) {
                $table->date('paye_le')->nullable()->after('net_a_payer');
                $table->index(['mois', 'paye_le'], 'idx_paies_mois_paye_le');
            }
        });
    }

    public function down(): void
    {
        Schema::table('paies', function (Blueprint $table) {
            if (Schema::hasColumn('paies', 'paye_le')) {
                $table->dropIndex('idx_paies_mois_paye_le');
                $table->dropColumn('paye_le');
            }
        });
    }
};

