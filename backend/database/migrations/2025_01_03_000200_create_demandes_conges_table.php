<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes_conges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')->constrained('employes')->cascadeOnDelete();
            // $table->foreignId('type_id')->constrained('absences_types')->cascadeOnDelete();
            $table->date('date_debut');
            $table->date('date_fin');
            $table->enum('statut', ['en_attente', 'manager_valide', 'rh_valide', 'rejete'])->default('en_attente');
            $table->text('motif')->nullable();
            $table->foreignId('approuve_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_conges');
    }
};
