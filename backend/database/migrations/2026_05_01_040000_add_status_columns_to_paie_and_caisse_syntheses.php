<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paie_synthese_mensuelle', function (Blueprint $table) {
            $table->string('synthese_status', 32)->default('available')->after('generated_at');
            $table->string('refreshed_by', 64)->nullable()->after('synthese_status');
            $table->text('error_message')->nullable()->after('refreshed_by');
            $table->index(['mois', 'synthese_status'], 'idx_paie_synthese_mois_status');
        });

        Schema::table('caisse_synthese_journaliere', function (Blueprint $table) {
            $table->string('synthese_status', 32)->default('available')->after('generated_at');
            $table->string('refreshed_by', 64)->nullable()->after('synthese_status');
            $table->text('error_message')->nullable()->after('refreshed_by');
            $table->index(['jour', 'synthese_status'], 'idx_caisse_synthese_jour_status');
        });
    }

    public function down(): void
    {
        Schema::table('paie_synthese_mensuelle', function (Blueprint $table) {
            $table->dropIndex('idx_paie_synthese_mois_status');
            $table->dropColumn(['synthese_status', 'refreshed_by', 'error_message']);
        });

        Schema::table('caisse_synthese_journaliere', function (Blueprint $table) {
            $table->dropIndex('idx_caisse_synthese_jour_status');
            $table->dropColumn(['synthese_status', 'refreshed_by', 'error_message']);
        });
    }
};
