<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('irsa_tranches', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_base', 12, 2)->default(0);
            $table->decimal('max_base', 12, 2)->nullable();
            $table->decimal('taux', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('irsa_tranches');
    }
};
