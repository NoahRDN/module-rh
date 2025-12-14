<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('niveaux_competence', function (Blueprint $table) {
            $table->id();
            $table->integer('niveau')->unique(); // 1 à 5
            $table->string('code')->unique();
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Niveaux de compétence standards
        \DB::table('niveaux_competence')->insert([
            [
                'niveau' => 1,
                'code' => 'debutant',
                'libelle' => 'Débutant',
                'description' => 'Connaissances de base, nécessite un accompagnement',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'niveau' => 2,
                'code' => 'intermediaire',
                'libelle' => 'Intermédiaire',
                'description' => 'Maîtrise partielle, peut travailler avec supervision',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'niveau' => 3,
                'code' => 'confirme',
                'libelle' => 'Confirmé',
                'description' => 'Maîtrise solide, travaille en autonomie',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'niveau' => 4,
                'code' => 'avance',
                'libelle' => 'Avancé',
                'description' => 'Expertise reconnue, peut former d\'autres personnes',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'niveau' => 5,
                'code' => 'expert',
                'libelle' => 'Expert',
                'description' => 'Référent dans le domaine, maîtrise complète',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('niveaux_competence');
    }
};
