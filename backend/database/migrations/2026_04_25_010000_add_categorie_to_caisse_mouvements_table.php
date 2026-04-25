<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caisse_mouvements', function (Blueprint $table) {
            $table->string('categorie', 64)->nullable()->after('type');
            $table->index(['type', 'categorie'], 'idx_caisse_mouvements_type_categorie');
        });
    }

    public function down(): void
    {
        Schema::table('caisse_mouvements', function (Blueprint $table) {
            $table->dropIndex('idx_caisse_mouvements_type_categorie');
            $table->dropColumn('categorie');
        });
    }
};
