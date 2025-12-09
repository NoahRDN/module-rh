<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('types_conges', function (Blueprint $table) {
            if (!Schema::hasColumn('types_conges', 'cumulable_frequence_id')) {
                $table->foreignId('cumulable_frequence_id')->nullable()->constrained('frequence_conges')->nullOnDelete()->after('cumulable_duree');
            }
        });
    }

    public function down(): void
    {
        Schema::table('types_conges', function (Blueprint $table) {
            if (Schema::hasColumn('types_conges', 'cumulable_frequence_id')) {
                $table->dropConstrainedForeignId('cumulable_frequence_id');
            }
        });
    }
};
