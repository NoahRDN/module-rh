<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pointages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')->constrained('employes')->cascadeOnDelete();
            $table->enum('type', ['entree', 'sortie', 'pause_debut', 'pause_fin']);
            $table->timestamp('pointe_a');
            $table->string('source')->default('manuel');
            $table->string('commentaire')->nullable();
            $table->timestamps();

            $table->index(['employe_id', 'pointe_a']);
            $table->index(['pointe_a']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pointages');
    }
};
