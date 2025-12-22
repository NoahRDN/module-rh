<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('sujet');
            $table->foreignId('employe_id')->constrained('employes')->onDelete('cascade');
            $table->enum('statut', ['ouverte', 'en_attente', 'fermee'])->default('ouverte');
            $table->enum('priorite', ['basse', 'normale', 'haute', 'urgente'])->default('normale');
            $table->foreignId('assigne_a')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('derniere_activite')->nullable();
            $table->timestamps();

            $table->index(['employe_id', 'statut']);
            $table->index(['assigne_a', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
