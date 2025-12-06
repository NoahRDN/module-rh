<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historique_postes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employe_id')
                ->constrained('employes')
                ->cascadeOnDelete();

            $table->foreignId('poste_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('departement_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->date('date_changement');
            $table->string('motif')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_postes');
    }
};
