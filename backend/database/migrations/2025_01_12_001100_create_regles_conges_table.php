<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('regles_conges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_conge_id')->constrained('types_conges')->cascadeOnDelete();
            $table->integer('anciennete_min')->default(0); // en années
            $table->decimal('jours_acquis_par_mois', 8, 2)->default(2.5);
            $table->string('contrat_type')->nullable(); // CDI/CDD/Stage etc.
            $table->decimal('temps_partiel_ratio', 4, 2)->nullable(); // 0.5 = 50%
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regles_conges');
    }
};
