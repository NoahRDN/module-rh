<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // if (Schema::hasTable('soldes_conges')) {
        //     DB::statement('ALTER TABLE soldes_conges ALTER COLUMN type_id DROP NOT NULL');
        // }
    }

    public function down(): void
    {
        // if (Schema::hasTable('soldes_conges')) {
        //     DB::statement('ALTER TABLE soldes_conges ALTER COLUMN type_id SET NOT NULL');
        // }
    }
};
