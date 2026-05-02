<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dashboard_stats', function (Blueprint $table) {
            $table->string('period_type', 20)->nullable()->after('filtre');
            $table->string('refreshed_by', 64)->nullable()->after('generated_at');
            $table->string('status', 32)->default('available')->after('refreshed_by');
            $table->text('error_message')->nullable()->after('status');
            $table->index(['period_type', 'date_debut', 'date_fin'], 'idx_dashboard_stats_period_type');
            $table->index(['status', 'updated_at'], 'idx_dashboard_stats_status_updated');
        });

        DB::table('dashboard_stats')->whereNull('period_type')->update([
            'period_type' => DB::raw('filtre'),
        ]);
    }

    public function down(): void
    {
        Schema::table('dashboard_stats', function (Blueprint $table) {
            $table->dropIndex('idx_dashboard_stats_period_type');
            $table->dropIndex('idx_dashboard_stats_status_updated');
            $table->dropColumn(['period_type', 'refreshed_by', 'status', 'error_message']);
        });
    }
};
