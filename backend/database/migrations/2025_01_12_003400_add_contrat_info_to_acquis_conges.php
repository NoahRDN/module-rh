<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('acquis_conges', function (Blueprint $table) {
            if (!Schema::hasColumn('acquis_conges', 'contrat_type')) {
                $table->string('contrat_type')->nullable()->after('type_conge_id');
            }
            if (!Schema::hasColumn('acquis_conges', 'contrat_fin')) {
                $table->date('contrat_fin')->nullable()->after('contrat_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('acquis_conges', function (Blueprint $table) {
            if (Schema::hasColumn('acquis_conges', 'contrat_fin')) {
                $table->dropColumn('contrat_fin');
            }
            if (Schema::hasColumn('acquis_conges', 'contrat_type')) {
                $table->dropColumn('contrat_type');
            }
        });
    }
};
