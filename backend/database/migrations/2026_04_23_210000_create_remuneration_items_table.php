<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remuneration_items', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('nature', 20); // prime | indemnite
            $table->string('scope_type', 20); // global | poste | employe | contrat
            $table->foreignId('poste_id')->nullable()->constrained('postes')->nullOnDelete();
            $table->foreignId('employe_id')->nullable()->constrained('employes')->nullOnDelete();
            $table->foreignId('contrat_id')->nullable()->constrained('contrats')->nullOnDelete();
            $table->string('recurrence_type', 20); // recurrent | ponctuel
            $table->string('mois_application', 7)->nullable();
            $table->string('condition_type', 30)->nullable(); // anciennete
            $table->string('condition_operator', 4)->nullable(); // >, <, =, >=, <=
            $table->decimal('condition_value', 10, 2)->nullable();
            $table->decimal('montant', 12, 2)->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index(['scope_type', 'actif'], 'idx_rem_item_scope_active');
            $table->index('mois_application', 'idx_rem_item_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remuneration_items');
    }
};
