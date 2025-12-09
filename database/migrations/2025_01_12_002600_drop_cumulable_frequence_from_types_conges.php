<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('types_conges', function (Blueprint $table) {
            if (Schema::hasColumn('types_conges', 'cumulable_frequence')) {
                $table->dropColumn('cumulable_frequence');
            }
        });
    }

    public function down(): void
    {
        Schema::table('types_conges', function (Blueprint $table) {
            if (!Schema::hasColumn('types_conges', 'cumulable_frequence')) {
                $table->string('cumulable_frequence', 50)->nullable()->after('cumulable_duree');
            }
        });
    }
};
