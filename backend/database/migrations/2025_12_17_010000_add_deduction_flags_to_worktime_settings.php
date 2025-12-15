<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('worktime_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('worktime_settings', 'deduct_from_leave_balance')) {
                $table->boolean('deduct_from_leave_balance')->default(false)->after('night_rate');
            }
            if (!Schema::hasColumn('worktime_settings', 'deduct_from_salary')) {
                $table->boolean('deduct_from_salary')->default(false)->after('deduct_from_leave_balance');
            }
        });
    }

    public function down(): void
    {
        Schema::table('worktime_settings', function (Blueprint $table) {
            $table->dropColumn(['deduct_from_leave_balance', 'deduct_from_salary']);
        });
    }
};
