<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('evaluations')->onDelete('cascade');
            $table->foreignId('critere_id')->constrained('criteres_evaluation')->onDelete('cascade');
            $table->decimal('note', 5, 2)->default(0); // Note sur 100
            $table->text('commentaire')->nullable();
            $table->timestamps();
            
            $table->unique(['evaluation_id', 'critere_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_details');
    }
};
