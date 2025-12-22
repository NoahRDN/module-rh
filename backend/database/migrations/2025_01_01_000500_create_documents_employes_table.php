<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents_employes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employe_id')
                ->constrained('employes')
                ->cascadeOnDelete();

            $table->string('type_document');
            $table->string('fichier');
            $table->date('date_expiration')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents_employes');
    }
};
