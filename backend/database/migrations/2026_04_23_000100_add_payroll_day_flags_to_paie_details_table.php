<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paie_details', function (Blueprint $table) {
            if (!Schema::hasColumn('paie_details', 'absent')) {
                $table->boolean('absent')->default(false)->after('retard_minutes');
            }
            if (!Schema::hasColumn('paie_details', 'absence_justifiee')) {
                $table->boolean('absence_justifiee')->default(false)->after('absent');
            }
            if (!Schema::hasColumn('paie_details', 'ferie')) {
                $table->boolean('ferie')->default(false)->after('absence_justifiee');
            }
            if (!Schema::hasColumn('paie_details', 'weekend')) {
                $table->boolean('weekend')->default(false)->after('ferie');
            }
            if (!Schema::hasColumn('paie_details', 'present_partiel')) {
                $table->boolean('present_partiel')->default(false)->after('weekend');
            }
        });
    }

    public function down(): void
    {
        Schema::table('paie_details', function (Blueprint $table) {
            foreach (['present_partiel', 'weekend', 'ferie', 'absence_justifiee', 'absent'] as $column) {
                if (Schema::hasColumn('paie_details', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

