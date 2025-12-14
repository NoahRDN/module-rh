<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('employe_id')->nullable()->after('role')->constrained('employes')->onDelete('set null');
        });

        // Mettre à jour l'enum role pour inclure 'employe'
        // Note: En SQLite/MySQL, on peut avoir besoin d'une approche différente
        // Ici on suppose que role est un string, pas un enum strict
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['employe_id']);
            $table->dropColumn('employe_id');
        });
    }
};
