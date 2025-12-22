<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consommations_conges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_conge_id')->constrained('demandes_conges')->cascadeOnDelete();
            $table->foreignId('acquis_conge_id')->constrained('acquis_conges')->cascadeOnDelete();
            $table->decimal('jours_utilises', 8, 2);
            $table->timestamps();
            $table->index(['demande_conge_id', 'acquis_conge_id'], 'idx_consommations_demande');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consommations_conges');
    }
};
