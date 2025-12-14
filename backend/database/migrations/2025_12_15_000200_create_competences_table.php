<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competences', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->foreignId('categorie_id')->constrained('categorie_competences')->onDelete('cascade');
            $table->boolean('actif')->default(true);
            $table->integer('ordre')->default(0);
            $table->timestamps();
        });

        // Compétences par défaut
        \DB::table('competences')->insert([
            // Compétences Techniques (categorie_id = 1)
            ['code' => 'php', 'nom' => 'PHP', 'description' => 'Développement backend PHP', 'categorie_id' => 1, 'actif' => true, 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'javascript', 'nom' => 'JavaScript', 'description' => 'Développement JavaScript frontend et backend', 'categorie_id' => 1, 'actif' => true, 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'python', 'nom' => 'Python', 'description' => 'Programmation Python', 'categorie_id' => 1, 'actif' => true, 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'sql', 'nom' => 'SQL', 'description' => 'Gestion de bases de données relationnelles', 'categorie_id' => 1, 'actif' => true, 'ordre' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'laravel', 'nom' => 'Laravel', 'description' => 'Framework PHP Laravel', 'categorie_id' => 1, 'actif' => true, 'ordre' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'vuejs', 'nom' => 'Vue.js', 'description' => 'Framework JavaScript Vue.js', 'categorie_id' => 1, 'actif' => true, 'ordre' => 6, 'created_at' => now(), 'updated_at' => now()],
            
            // Compétences Linguistiques (categorie_id = 2)
            ['code' => 'francais', 'nom' => 'Français', 'description' => 'Maîtrise du français', 'categorie_id' => 2, 'actif' => true, 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'anglais', 'nom' => 'Anglais', 'description' => 'Maîtrise de l\'anglais', 'categorie_id' => 2, 'actif' => true, 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'malgache', 'nom' => 'Malgache', 'description' => 'Maîtrise du malgache', 'categorie_id' => 2, 'actif' => true, 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],
            
            // Compétences Managériales (categorie_id = 3)
            ['code' => 'gestion_equipe', 'nom' => 'Gestion d\'équipe', 'description' => 'Capacité à diriger et motiver une équipe', 'categorie_id' => 3, 'actif' => true, 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'gestion_projet', 'nom' => 'Gestion de projet', 'description' => 'Planification et suivi de projets', 'categorie_id' => 3, 'actif' => true, 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'prise_decision', 'nom' => 'Prise de décision', 'description' => 'Capacité à prendre des décisions stratégiques', 'categorie_id' => 3, 'actif' => true, 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],
            
            // Soft Skills (categorie_id = 4)
            ['code' => 'communication', 'nom' => 'Communication', 'description' => 'Capacité à communiquer efficacement', 'categorie_id' => 4, 'actif' => true, 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'travail_equipe', 'nom' => 'Travail en équipe', 'description' => 'Collaboration et esprit d\'équipe', 'categorie_id' => 4, 'actif' => true, 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'adaptabilite', 'nom' => 'Adaptabilité', 'description' => 'Capacité à s\'adapter aux changements', 'categorie_id' => 4, 'actif' => true, 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'resolution_problemes', 'nom' => 'Résolution de problèmes', 'description' => 'Analyse et résolution de problèmes complexes', 'categorie_id' => 4, 'actif' => true, 'ordre' => 4, 'created_at' => now(), 'updated_at' => now()],
            
            // Compétences Bureautiques (categorie_id = 5)
            ['code' => 'excel', 'nom' => 'Microsoft Excel', 'description' => 'Maîtrise d\'Excel (tableaux, formules, macros)', 'categorie_id' => 5, 'actif' => true, 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'word', 'nom' => 'Microsoft Word', 'description' => 'Traitement de texte Word', 'categorie_id' => 5, 'actif' => true, 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'powerpoint', 'nom' => 'Microsoft PowerPoint', 'description' => 'Création de présentations', 'categorie_id' => 5, 'actif' => true, 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],
            
            // Compétences Métier (categorie_id = 6)
            ['code' => 'comptabilite', 'nom' => 'Comptabilité', 'description' => 'Gestion comptable et financière', 'categorie_id' => 6, 'actif' => true, 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'rh', 'nom' => 'Ressources Humaines', 'description' => 'Gestion des ressources humaines', 'categorie_id' => 6, 'actif' => true, 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'marketing', 'nom' => 'Marketing', 'description' => 'Stratégie marketing et communication', 'categorie_id' => 6, 'actif' => true, 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('competences');
    }
};
