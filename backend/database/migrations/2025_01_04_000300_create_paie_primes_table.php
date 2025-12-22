<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paie_primes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paie_id')->constrained('paies')->cascadeOnDelete();
            $table->string('libelle');
            $table->decimal('montant', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paie_primes');
    }
};
