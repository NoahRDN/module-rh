<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute le lien manager (responsable) au département.
     * Un employé avec un rôle manager peut être responsable d'un département.
     */
    public function up(): void
    {
        Schema::table('departements', function (Blueprint $table) {
            $table->foreignId('manager_id')
                ->nullable()
                ->after('description')
                ->constrained('employes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departements', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropColumn('manager_id');
        });
    }
};
