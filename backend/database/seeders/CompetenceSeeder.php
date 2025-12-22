<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employe;
use App\Models\Poste;
use App\Models\Competence;
use App\Models\NiveauCompetence;
use App\Models\Formation;
use App\Models\EmployeCompetence;
use App\Models\PosteCompetence;
use App\Models\FormationEmploye;
use Carbon\Carbon;

class CompetenceSeeder extends Seeder
{
    /**
     * Seeder pour les données de test des compétences.
     * Ajoute des compétences aux employés, des requis aux postes,
     * et des inscriptions aux formations.
     */
    public function run(): void
    {
        // Récupérer les données existantes
        $employes = Employe::all();
        $postes = Poste::all();
        $competences = Competence::all();
        $niveaux = NiveauCompetence::all();
        $formations = Formation::all();

        if ($employes->isEmpty() || $competences->isEmpty()) {
            $this->command->info('Aucun employé ou compétence trouvé. Veuillez d\'abord exécuter les migrations.');
            return;
        }

        // Assigner des compétences aléatoires aux employés
        $this->command->info('Attribution des compétences aux employés...');
        
        foreach ($employes as $employe) {
            // Chaque employé a entre 3 et 8 compétences
            $nbCompetences = rand(3, 8);
            $competencesEmploye = $competences->random(min($nbCompetences, $competences->count()));
            
            foreach ($competencesEmploye as $competence) {
                // Vérifier si la relation n'existe pas déjà
                $exists = EmployeCompetence::where('employe_id', $employe->id)
                    ->where('competence_id', $competence->id)
                    ->exists();
                
                if (!$exists) {
                    EmployeCompetence::create([
                        'employe_id' => $employe->id,
                        'competence_id' => $competence->id,
                        'niveau' => $niveaux->random()->niveau,
                        'date_evaluation' => Carbon::now()->subDays(rand(0, 365)),
                        'commentaire' => $this->genererCommentaire(),
                    ]);
                }
            }
        }

        // Assigner des compétences requises aux postes
        $this->command->info('Attribution des compétences requises aux postes...');
        
        foreach ($postes as $poste) {
            // Chaque poste requiert entre 4 et 10 compétences
            $nbCompetences = rand(4, 10);
            $competencesPoste = $competences->random(min($nbCompetences, $competences->count()));
            
            foreach ($competencesPoste as $index => $competence) {
                $exists = PosteCompetence::where('poste_id', $poste->id)
                    ->where('competence_id', $competence->id)
                    ->exists();
                
                if (!$exists) {
                    // Les 2-3 premières sont obligatoires
                    $obligatoire = $index < rand(2, 3);
                    
                    PosteCompetence::create([
                        'poste_id' => $poste->id,
                        'competence_id' => $competence->id,
                        'niveau_requis' => rand(2, 5),
                        'obligatoire' => $obligatoire,
                        'poids' => $obligatoire ? rand(15, 25) : rand(5, 15),
                    ]);
                }
            }
        }

        // Créer des inscriptions aux formations
        if ($formations->isNotEmpty()) {
            $this->command->info('Création des inscriptions aux formations...');
            
            foreach ($employes->random(min(10, $employes->count())) as $employe) {
                // Chaque employé sélectionné est inscrit à 1-3 formations
                $nbFormations = rand(1, 3);
                $formationsEmploye = $formations->random(min($nbFormations, $formations->count()));
                
                foreach ($formationsEmploye as $formation) {
                    $exists = FormationEmploye::where('employe_id', $employe->id)
                        ->where('formation_id', $formation->id)
                        ->exists();
                    
                    if (!$exists) {
                        $statut = $this->getRandomStatut();
                        
                        FormationEmploye::create([
                            'formation_id' => $formation->id,
                            'employe_id' => $employe->id,
                            'date_debut' => $statut !== 'planifiee' ? Carbon::now()->subDays(rand(5, 30)) : null,
                            'date_fin' => $statut === 'terminee' ? Carbon::now()->subDays(rand(1, 10)) : null,
                            'statut' => $statut,
                            'note' => $statut === 'terminee' ? rand(60, 100) / 10 : null,
                            'certificat_obtenu' => $statut === 'terminee' && rand(0, 1) ? true : false,
                            'commentaire' => $statut === 'terminee' ? $this->genererCommentaireFormation() : null,
                        ]);
                    }
                }
            }
        }

        $this->command->info('Seeding des compétences terminé!');
    }

    private function genererCommentaire(): ?string
    {
        $commentaires = [
            'Compétence acquise lors du dernier projet',
            'À améliorer',
            'Excellente maîtrise',
            'En cours de développement',
            'Formation récente',
            'Expérience terrain',
            null,
            null,
        ];
        
        return $commentaires[array_rand($commentaires)];
    }

    private function genererCommentaireFormation(): ?string
    {
        $commentaires = [
            'Très bonne formation, applicable immédiatement',
            'Formation enrichissante',
            'Contenu un peu théorique mais utile',
            'Excellent formateur',
            null,
        ];
        
        return $commentaires[array_rand($commentaires)];
    }

    private function getRandomStatut(): string
    {
        $statuts = ['planifiee', 'planifiee', 'en_cours', 'en_cours', 'terminee', 'terminee', 'terminee', 'annulee'];
        return $statuts[array_rand($statuts)];
    }
}
