<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Passage de photo en TEXT pour accepter les chaînes base64 plus longues
        DB::statement('ALTER TABLE employes ALTER COLUMN photo TYPE TEXT');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Revenir en VARCHAR(255) si nécessaire
        DB::statement('ALTER TABLE employes ALTER COLUMN photo TYPE VARCHAR(255)');
    }
};
