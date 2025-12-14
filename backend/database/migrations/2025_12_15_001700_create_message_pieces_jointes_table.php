<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_pieces_jointes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->onDelete('cascade');
            $table->string('nom');
            $table->string('chemin');
            $table->string('type_mime')->nullable();
            $table->integer('taille')->nullable(); // En octets
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_pieces_jointes');
    }
};
