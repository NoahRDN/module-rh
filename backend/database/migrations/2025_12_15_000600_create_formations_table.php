<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->integer('duree_heures')->default(8); // Durée en heures
            $table->enum('type', ['presentiel', 'distanciel', 'hybride'])->default('presentiel');
            $table->enum('niveau', ['debutant', 'intermediaire', 'avance'])->default('intermediaire');
            $table->decimal('cout', 10, 2)->nullable(); // Coût de la formation
            $table->string('organisme')->nullable(); // Organisme formateur
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // Formations par défaut
        \DB::table('formations')->insert([
            // Formations techniques
            [
                'code' => 'FORM-PHP-001',
                'titre' => 'Initiation à PHP',
                'description' => 'Formation aux bases du langage PHP et de la programmation web backend',
                'duree_heures' => 16,
                'type' => 'presentiel',
                'niveau' => 'debutant',
                'cout' => 500.00,
                'organisme' => 'TechFormation',
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'FORM-PHP-002',
                'titre' => 'PHP Avancé et Laravel',
                'description' => 'Maîtrise de PHP orienté objet et du framework Laravel',
                'duree_heures' => 24,
                'type' => 'presentiel',
                'niveau' => 'avance',
                'cout' => 800.00,
                'organisme' => 'TechFormation',
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'FORM-JS-001',
                'titre' => 'JavaScript Moderne',
                'description' => 'ES6+, async/await, modules et bonnes pratiques',
                'duree_heures' => 16,
                'type' => 'hybride',
                'niveau' => 'intermediaire',
                'cout' => 600.00,
                'organisme' => 'WebAcademy',
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'FORM-VUE-001',
                'titre' => 'Vue.js 3 - De zéro à héros',
                'description' => 'Formation complète sur Vue.js 3, Composition API et Pinia',
                'duree_heures' => 20,
                'type' => 'distanciel',
                'niveau' => 'intermediaire',
                'cout' => 700.00,
                'organisme' => 'WebAcademy',
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'FORM-SQL-001',
                'titre' => 'SQL et bases de données',
                'description' => 'Maîtrise de SQL, optimisation des requêtes et modélisation',
                'duree_heures' => 16,
                'type' => 'presentiel',
                'niveau' => 'intermediaire',
                'cout' => 550.00,
                'organisme' => 'DataSkills',
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Formations linguistiques
            [
                'code' => 'FORM-ANG-001',
                'titre' => 'Anglais professionnel',
                'description' => 'Améliorer son anglais pour le contexte professionnel',
                'duree_heures' => 40,
                'type' => 'hybride',
                'niveau' => 'intermediaire',
                'cout' => 1200.00,
                'organisme' => 'LangPro',
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'FORM-ANG-002',
                'titre' => 'Business English Avancé',
                'description' => 'Négociation, présentation et rédaction en anglais',
                'duree_heures' => 30,
                'type' => 'presentiel',
                'niveau' => 'avance',
                'cout' => 1500.00,
                'organisme' => 'LangPro',
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Formations management
            [
                'code' => 'FORM-MGT-001',
                'titre' => 'Les fondamentaux du management',
                'description' => 'Acquérir les compétences essentielles pour manager une équipe',
                'duree_heures' => 16,
                'type' => 'presentiel',
                'niveau' => 'debutant',
                'cout' => 900.00,
                'organisme' => 'LeadershipAcademy',
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'FORM-MGT-002',
                'titre' => 'Gestion de projet Agile',
                'description' => 'Scrum, Kanban et méthodologies agiles',
                'duree_heures' => 24,
                'type' => 'hybride',
                'niveau' => 'intermediaire',
                'cout' => 1100.00,
                'organisme' => 'AgileExperts',
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Formations soft skills
            [
                'code' => 'FORM-COM-001',
                'titre' => 'Communication efficace',
                'description' => 'Techniques de communication interpersonnelle et professionnelle',
                'duree_heures' => 8,
                'type' => 'presentiel',
                'niveau' => 'debutant',
                'cout' => 400.00,
                'organisme' => 'SoftSkillsPro',
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'FORM-TEAM-001',
                'titre' => 'Travail en équipe et collaboration',
                'description' => 'Développer l\'esprit d\'équipe et la collaboration',
                'duree_heures' => 8,
                'type' => 'presentiel',
                'niveau' => 'debutant',
                'cout' => 350.00,
                'organisme' => 'SoftSkillsPro',
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Formations bureautiques
            [
                'code' => 'FORM-XLS-001',
                'titre' => 'Excel - Niveau avancé',
                'description' => 'Tableaux croisés dynamiques, macros VBA et fonctions avancées',
                'duree_heures' => 16,
                'type' => 'presentiel',
                'niveau' => 'avance',
                'cout' => 450.00,
                'organisme' => 'OfficeMaster',
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('formations');
    }
};
