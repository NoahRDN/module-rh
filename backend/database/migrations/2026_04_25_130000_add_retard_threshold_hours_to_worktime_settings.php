<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('worktime_settings', function (Blueprint $table) {
            $table->decimal('retard_threshold_hours', 5, 2)
                ->default(2)
                ->after('retard_tolerance_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('worktime_settings', function (Blueprint $table) {
            $table->dropColumn('retard_threshold_hours');
        });
    }
};
