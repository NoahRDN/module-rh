<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendrier_evenements', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // conge, absence, ferié, rh, paie...
            $table->foreignId('employe_id')->nullable()->constrained('employes')->nullOnDelete();
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendrier_evenements');
    }
};
