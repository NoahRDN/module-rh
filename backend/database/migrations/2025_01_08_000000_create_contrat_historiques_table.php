<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrat_historiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrat_id')->constrained('contrats')->cascadeOnDelete();
            $table->string('numero')->nullable();
            $table->foreignId('employe_id')->constrained('employes')->cascadeOnDelete();
            $table->string('type_contrat');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->date('periode_essai_debut')->nullable();
            $table->date('periode_essai_fin')->nullable();
            $table->boolean('renouvelable')->default(false);
            $table->decimal('salaire_base', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrat_historiques');
    }
};
