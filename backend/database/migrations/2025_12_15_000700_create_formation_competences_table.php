<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formation_competences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formation_id')->constrained('formations')->onDelete('cascade');
            $table->foreignId('competence_id')->constrained('competences')->onDelete('cascade');
            $table->integer('niveau_apport')->default(1); // Niveau que la formation permet d'atteindre (1-5)
            $table->timestamps();

            $table->unique(['formation_id', 'competence_id']);
        });

        // Liaisons formations-compétences
        \DB::table('formation_competences')->insert([
            // Formation PHP Init -> PHP niveau 2
            ['formation_id' => 1, 'competence_id' => 1, 'niveau_apport' => 2, 'created_at' => now(), 'updated_at' => now()],
            // Formation PHP Avancé -> PHP niveau 4, Laravel niveau 4
            ['formation_id' => 2, 'competence_id' => 1, 'niveau_apport' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['formation_id' => 2, 'competence_id' => 5, 'niveau_apport' => 4, 'created_at' => now(), 'updated_at' => now()],
            // Formation JS -> JavaScript niveau 3
            ['formation_id' => 3, 'competence_id' => 2, 'niveau_apport' => 3, 'created_at' => now(), 'updated_at' => now()],
            // Formation Vue.js -> Vue.js niveau 4, JavaScript niveau 3
            ['formation_id' => 4, 'competence_id' => 6, 'niveau_apport' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['formation_id' => 4, 'competence_id' => 2, 'niveau_apport' => 3, 'created_at' => now(), 'updated_at' => now()],
            // Formation SQL -> SQL niveau 3
            ['formation_id' => 5, 'competence_id' => 4, 'niveau_apport' => 3, 'created_at' => now(), 'updated_at' => now()],
            // Anglais pro -> Anglais niveau 3
            ['formation_id' => 6, 'competence_id' => 8, 'niveau_apport' => 3, 'created_at' => now(), 'updated_at' => now()],
            // Business English -> Anglais niveau 4, Communication niveau 3
            ['formation_id' => 7, 'competence_id' => 8, 'niveau_apport' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['formation_id' => 7, 'competence_id' => 13, 'niveau_apport' => 3, 'created_at' => now(), 'updated_at' => now()],
            // Management -> Gestion équipe niveau 3
            ['formation_id' => 8, 'competence_id' => 10, 'niveau_apport' => 3, 'created_at' => now(), 'updated_at' => now()],
            // Agile -> Gestion projet niveau 4
            ['formation_id' => 9, 'competence_id' => 11, 'niveau_apport' => 4, 'created_at' => now(), 'updated_at' => now()],
            // Communication -> Communication niveau 3
            ['formation_id' => 10, 'competence_id' => 13, 'niveau_apport' => 3, 'created_at' => now(), 'updated_at' => now()],
            // Travail équipe -> Travail en équipe niveau 3
            ['formation_id' => 11, 'competence_id' => 14, 'niveau_apport' => 3, 'created_at' => now(), 'updated_at' => now()],
            // Excel avancé -> Excel niveau 4
            ['formation_id' => 12, 'competence_id' => 17, 'niveau_apport' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('formation_competences');
    }
};
