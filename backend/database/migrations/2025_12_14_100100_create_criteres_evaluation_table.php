<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criteres_evaluation', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->integer('poids')->default(1); // Pondération du critère
            $table->boolean('actif')->default(true);
            $table->integer('ordre')->default(0);
            $table->timestamps();
        });

        // Critères d'évaluation par défaut selon les bonnes pratiques RH
        \DB::table('criteres_evaluation')->insert([
            [
                'code' => 'ponctualite',
                'libelle' => 'Ponctualité et assiduité',
                'description' => 'Respect des horaires de travail et présence régulière',
                'poids' => 15,
                'actif' => true,
                'ordre' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'qualite_travail',
                'libelle' => 'Qualité du travail',
                'description' => 'Précision, fiabilité et niveau de finition des livrables',
                'poids' => 20,
                'actif' => true,
                'ordre' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'productivite',
                'libelle' => 'Productivité',
                'description' => 'Volume de travail accompli et respect des délais',
                'poids' => 20,
                'actif' => true,
                'ordre' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'esprit_equipe',
                'libelle' => 'Esprit d\'équipe',
                'description' => 'Collaboration, entraide et contribution à l\'ambiance de travail',
                'poids' => 15,
                'actif' => true,
                'ordre' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'initiative',
                'libelle' => 'Initiative et autonomie',
                'description' => 'Capacité à prendre des initiatives et à travailler de manière autonome',
                'poids' => 15,
                'actif' => true,
                'ordre' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'competences',
                'libelle' => 'Compétences techniques',
                'description' => 'Maîtrise des outils et des compétences métier',
                'poids' => 15,
                'actif' => true,
                'ordre' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('criteres_evaluation');
    }
};
