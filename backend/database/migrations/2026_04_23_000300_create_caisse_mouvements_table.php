<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caisse_mouvements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caisse_id')->constrained('caisses')->cascadeOnDelete();
            $table->foreignId('paie_id')->nullable()->constrained('paies')->nullOnDelete();
            $table->string('type', 16);
            $table->decimal('montant', 14, 2);
            $table->string('source');
            $table->text('description')->nullable();
            $table->string('statut', 32)->default('en_attente_validation');
            $table->timestamp('demande_validation_le')->nullable();
            $table->timestamp('valide_le')->nullable();
            $table->timestamp('rejete_le')->nullable();
            $table->timestamps();

            $table->index(['caisse_id', 'statut'], 'idx_caisse_mouvements_caisse_statut');
            $table->index(['paie_id', 'statut'], 'idx_caisse_mouvements_paie_statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caisse_mouvements');
    }
};
