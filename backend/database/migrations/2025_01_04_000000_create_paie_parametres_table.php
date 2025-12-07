<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paie_parametres', function (Blueprint $table) {
            $table->id();
            $table->decimal('cnaps', 5, 2)->default(1.0);
            $table->decimal('ostie', 5, 2)->default(1.0);
            $table->decimal('irsa_base', 10, 2)->default(350000);
            $table->decimal('irsa_taux', 5, 2)->default(20);
            $table->decimal('hs_taux', 5, 2)->default(1.3);
            $table->decimal('prime_transport', 10, 2)->default(0);
            $table->decimal('prime_presence', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paie_parametres');
    }
};
