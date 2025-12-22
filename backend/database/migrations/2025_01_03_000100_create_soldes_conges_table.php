<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table conservée pour compatibilité : pas de FK vers absences_types (supprimée)
        Schema::create('soldes_conges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employe_id')->nullable();
            $table->unsignedBigInteger('type_conge_id')->nullable();
            $table->decimal('solde_actuel', 8, 2)->default(0);
            $table->decimal('solde_annuel', 8, 2)->default(0);
            $table->date('expire_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soldes_conges');
    }
};
