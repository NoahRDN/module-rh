<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('types_conges', function (Blueprint $table) {
            // Colonnes déjà supprimées par migrations ultérieures ; ce fichier reste pour compat.
        });
    }

    public function down(): void
    {
        Schema::table('types_conges', function (Blueprint $table) {
            // Pas de recréation des anciennes colonnes
        });
    }
};
