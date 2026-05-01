<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caisse_synthese_journaliere', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caisse_id')->constrained('caisses')->cascadeOnDelete();
            $table->date('jour');
            $table->decimal('total_entrees', 14, 2)->default(0);
            $table->decimal('total_sorties', 14, 2)->default(0);
            $table->decimal('solde_net', 14, 2)->default(0);
            $table->decimal('solde_caisse', 14, 2)->default(0);
            $table->unsignedInteger('mouvements_total')->default(0);
            $table->unsignedInteger('mouvements_valides')->default(0);
            $table->unsignedInteger('mouvements_en_attente')->default(0);
            $table->unsignedInteger('mouvements_rejetes')->default(0);
            $table->json('par_categorie')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['caisse_id', 'jour'], 'caisse_synthese_caisse_jour_unique');
            $table->index(['jour', 'caisse_id'], 'idx_caisse_synthese_jour_caisse');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caisse_synthese_journaliere');
    }
};
