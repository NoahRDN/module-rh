<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('acquis_conges', function (Blueprint $table) {
            if (!Schema::hasColumn('acquis_conges', 'acquis_first')) {
                $table->date('acquis_first')->nullable()->after('expire_le');
            }
            if (!Schema::hasColumn('acquis_conges', 'expire_first')) {
                $table->date('expire_first')->nullable()->after('acquis_first');
            }
        });

        // Backfill : par défaut, caler acquis_first = acquis_le et expire_first = acquis_le + 3 ans
        DB::statement("UPDATE acquis_conges SET acquis_first = COALESCE(acquis_first, acquis_le)");
        DB::statement("UPDATE acquis_conges SET expire_first = COALESCE(expire_first, acquis_le + interval '3 year')");
    }

    public function down(): void
    {
        Schema::table('acquis_conges', function (Blueprint $table) {
            if (Schema::hasColumn('acquis_conges', 'expire_first')) {
                $table->dropColumn('expire_first');
            }
            if (Schema::hasColumn('acquis_conges', 'acquis_first')) {
                $table->dropColumn('acquis_first');
            }
        });
    }
};
