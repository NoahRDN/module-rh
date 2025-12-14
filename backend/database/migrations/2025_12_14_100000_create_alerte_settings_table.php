<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerte_settings', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // ex: 'fin_contrat', 'conges_non_pris', 'absences_maladie'
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->integer('seuil_jours')->nullable(); // ex: 30 jours avant fin contrat
            $table->integer('seuil_nombre')->nullable(); // ex: 4 absences maladie
            $table->integer('periode_jours')->nullable(); // ex: sur 60 jours
            $table->string('niveau')->default('warning'); // warning, danger, info
            $table->timestamps();
        });

        // Insérer les paramètres par défaut
        \DB::table('alerte_settings')->insert([
            [
                'code' => 'fin_contrat',
                'libelle' => 'Fin de contrat proche',
                'description' => 'Alerte X jours avant la fin d\'un contrat',
                'actif' => true,
                'seuil_jours' => 30,
                'seuil_nombre' => null,
                'periode_jours' => null,
                'niveau' => 'warning',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'conges_non_pris',
                'libelle' => 'Congés non pris',
                'description' => 'Alerte si plus de X jours de congés non pris',
                'actif' => true,
                'seuil_jours' => null,
                'seuil_nombre' => 15,
                'periode_jours' => null,
                'niveau' => 'info',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'absences_maladie',
                'libelle' => 'Absences maladie fréquentes',
                'description' => 'Alerte si plus de X absences maladie sur Y jours',
                'actif' => true,
                'seuil_jours' => null,
                'seuil_nombre' => 4,
                'periode_jours' => 60,
                'niveau' => 'warning',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'absences_exceptionnelles',
                'libelle' => 'Congés exceptionnels fréquents',
                'description' => 'Alerte si plus de X congés exceptionnels sur Y jours',
                'actif' => true,
                'seuil_jours' => null,
                'seuil_nombre' => 3,
                'periode_jours' => 90,
                'niveau' => 'warning',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'conge_en_attente',
                'libelle' => 'Demande en attente',
                'description' => 'Demandes de congé en attente depuis plus de X heures',
                'actif' => true,
                'seuil_jours' => 2, // 48 heures = 2 jours
                'seuil_nombre' => null,
                'periode_jours' => null,
                'niveau' => 'warning',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'conge_proche',
                'libelle' => 'Congé imminent non validé',
                'description' => 'Congé commençant dans X jours mais non validé',
                'actif' => true,
                'seuil_jours' => 2,
                'seuil_nombre' => null,
                'periode_jours' => null,
                'niveau' => 'danger',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('alerte_settings');
    }
};
