<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contrat_historiques', function (Blueprint $table) {
            if (!Schema::hasColumn('contrat_historiques', 'statut')) {
                $table->string('statut')->default('en_cours')->after('salaire_base');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contrat_historiques', function (Blueprint $table) {
            if (Schema::hasColumn('contrat_historiques', 'statut')) {
                $table->dropColumn('statut');
            }
        });
    }
};
