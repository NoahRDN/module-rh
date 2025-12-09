<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('demandes_conges', function (Blueprint $table) {
            if (!Schema::hasColumn('demandes_conges', 'type_conge_id')) {
                $table->foreignId('type_conge_id')->nullable()->constrained('types_conges')->nullOnDelete()->after('type_id');
            }
            if (!Schema::hasColumn('demandes_conges', 'jours_demandes')) {
                $table->decimal('jours_demandes', 8, 2)->nullable()->after('date_fin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('demandes_conges', function (Blueprint $table) {
            if (Schema::hasColumn('demandes_conges', 'type_conge_id')) {
                $table->dropConstrainedForeignId('type_conge_id');
            }
            if (Schema::hasColumn('demandes_conges', 'jours_demandes')) {
                $table->dropColumn('jours_demandes');
            }
        });
    }
};
