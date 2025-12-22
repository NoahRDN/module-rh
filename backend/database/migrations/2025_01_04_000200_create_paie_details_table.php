<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paie_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paie_id')->constrained('paies')->cascadeOnDelete();
            $table->date('jour');
            $table->decimal('heures_travaillees', 8, 2)->default(0);
            $table->decimal('heures_supplementaires', 8, 2)->default(0);
            $table->decimal('retard_minutes', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paie_details');
    }
};
