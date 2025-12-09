<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('soldes_conges', function (Blueprint $table) {
            if (!Schema::hasColumn('soldes_conges', 'type_conge_id')) {
                $table->foreignId('type_conge_id')->nullable()->constrained('types_conges')->nullOnDelete()->after('employe_id');
            }
            if (!Schema::hasColumn('soldes_conges', 'expire_le')) {
                $table->date('expire_le')->nullable()->after('solde_annuel');
            }
        });
    }

    public function down(): void
    {
        Schema::table('soldes_conges', function (Blueprint $table) {
            if (Schema::hasColumn('soldes_conges', 'type_conge_id')) {
                $table->dropConstrainedForeignId('type_conge_id');
            }
            if (Schema::hasColumn('soldes_conges', 'expire_le')) {
                $table->dropColumn('expire_le');
            }
        });
    }
};
