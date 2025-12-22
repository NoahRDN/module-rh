<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employes', function (Blueprint $table) {
            $table->id();

            $table->string('matricule')->unique();

            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();

            $table->string('telephone')->nullable();
            $table->string('adresse')->nullable();
            $table->date('date_naissance')->nullable();

            $table->foreignId('poste_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('departement_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('photo')->nullable();

            $table->date('date_embauche');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employes');
    }
};
