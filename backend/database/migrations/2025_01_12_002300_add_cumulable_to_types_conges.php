<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('types_conges', function (Blueprint $table) {
            if (!Schema::hasColumn('types_conges', 'cumulable')) {
                $table->boolean('cumulable')->default(true)->after('frequence_id');
            }
            if (!Schema::hasColumn('types_conges', 'cumulable_duree')) {
                $table->integer('cumulable_duree')->nullable()->after('cumulable');
            }
            if (!Schema::hasColumn('types_conges', 'cumulable_frequence')) {
                $table->string('cumulable_frequence', 50)->nullable()->after('cumulable_duree');
            }
        });
    }

    public function down(): void
    {
        Schema::table('types_conges', function (Blueprint $table) {
            if (Schema::hasColumn('types_conges', 'cumulable_frequence')) {
                $table->dropColumn('cumulable_frequence');
            }
            if (Schema::hasColumn('types_conges', 'cumulable_duree')) {
                $table->dropColumn('cumulable_duree');
            }
            if (Schema::hasColumn('types_conges', 'cumulable')) {
                $table->dropColumn('cumulable');
            }
        });
    }
};
