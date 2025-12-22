<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')->constrained('employes')->cascadeOnDelete();
            $table->string('mois'); // format YYYY-MM

            $table->decimal('salaire_base', 12, 2)->default(0);
            $table->decimal('heures_travaillees', 8, 2)->default(0);
            $table->decimal('heures_supplementaires', 8, 2)->default(0);
            $table->decimal('montant_hs', 12, 2)->default(0);

            $table->decimal('prime_transport', 12, 2)->default(0);
            $table->decimal('prime_presence', 12, 2)->default(0);
            $table->decimal('autres_primes', 12, 2)->default(0);

            $table->decimal('retenue_cnaps', 12, 2)->default(0);
            $table->decimal('retenue_ostie', 12, 2)->default(0);
            $table->decimal('retenue_irsa', 12, 2)->default(0);

            $table->decimal('total_brut', 12, 2)->default(0);
            $table->decimal('total_retenues', 12, 2)->default(0);
            $table->decimal('net_a_payer', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paies');
    }
};
