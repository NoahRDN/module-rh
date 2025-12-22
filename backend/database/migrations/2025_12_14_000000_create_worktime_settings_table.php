<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worktime_settings', function (Blueprint $table) {
            $table->id();
            $table->json('working_days')->nullable(); // ex: ["mon","tue","wed","thu","fri"]
            $table->string('saturday_mode')->default('normal'); // normal|hs
            $table->unsignedTinyInteger('start_hour')->default(8);
            $table->unsignedTinyInteger('start_minute')->default(0);
            $table->decimal('hours_per_day', 5, 2)->default(8);
            $table->decimal('weekly_threshold', 5, 2)->default(40);
            $table->json('multipliers')->nullable(); // {weekday_first8:30,...} en %
            $table->string('night_start')->default('22:00');
            $table->string('night_end')->default('05:00');
            $table->decimal('night_rate', 5, 2)->default(20); // en %
            $table->boolean('deduct_from_leave_balance')->default(false);
            $table->boolean('deduct_from_salary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worktime_settings');
    }
};
