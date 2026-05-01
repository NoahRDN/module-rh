<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_stats', function (Blueprint $table) {
            $table->id();
            $table->string('filtre', 20);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->json('statistiques');
            $table->json('donnees_rapides')->nullable();
            $table->json('alertes_recentes')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['filtre', 'date_debut', 'date_fin'], 'dashboard_stats_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_stats');
    }
};
