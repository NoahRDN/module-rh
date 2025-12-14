<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorie_competences', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('couleur', 7)->default('#3B82F6'); // Couleur hex pour l'affichage
            $table->boolean('actif')->default(true);
            $table->integer('ordre')->default(0);
            $table->timestamps();
        });

        // Catégories de compétences par défaut
        \DB::table('categorie_competences')->insert([
            [
                'code' => 'technique',
                'nom' => 'Compétences Techniques',
                'description' => 'Compétences liées aux outils, technologies et méthodologies techniques',
                'couleur' => '#3B82F6',
                'actif' => true,
                'ordre' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'linguistique',
                'nom' => 'Compétences Linguistiques',
                'description' => 'Maîtrise des langues étrangères',
                'couleur' => '#10B981',
                'actif' => true,
                'ordre' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'management',
                'nom' => 'Compétences Managériales',
                'description' => 'Compétences de gestion d\'équipe et leadership',
                'couleur' => '#8B5CF6',
                'actif' => true,
                'ordre' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'soft_skills',
                'nom' => 'Soft Skills',
                'description' => 'Compétences comportementales et relationnelles',
                'couleur' => '#F59E0B',
                'actif' => true,
                'ordre' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'bureautique',
                'nom' => 'Compétences Bureautiques',
                'description' => 'Maîtrise des outils bureautiques (Office, Google Suite, etc.)',
                'couleur' => '#EF4444',
                'actif' => true,
                'ordre' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'metier',
                'nom' => 'Compétences Métier',
                'description' => 'Compétences spécifiques au domaine d\'activité',
                'couleur' => '#06B6D4',
                'actif' => true,
                'ordre' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('categorie_competences');
    }
};
