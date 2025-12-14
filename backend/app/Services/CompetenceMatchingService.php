<?php

namespace App\Services;

use App\Models\Employe;
use App\Models\Poste;
use App\Models\Formation;
use Illuminate\Support\Collection;

class CompetenceMatchingService
{
    /**
     * Calculer le score de compatibilité entre un employé et un poste
     * 
     * @param Employe $employe
     * @param Poste $poste
     * @return array Score détaillé
     */
    public function calculerCompatibilite(Employe $employe, Poste $poste): array
    {
        $posteCompetences = $poste->competences()->get();
        $employeCompetences = $employe->competences()->get()->keyBy('id');

        if ($posteCompetences->isEmpty()) {
            return [
                'score_global' => 100,
                'score_obligatoires' => 100,
                'score_souhaitees' => 100,
                'competences_manquantes' => [],
                'competences_insuffisantes' => [],
                'competences_ok' => [],
                'message' => 'Aucune compétence requise pour ce poste'
            ];
        }

        $totalPoidsObligatoires = 0;
        $scoreObligatoires = 0;
        $totalPoidsSouhaitees = 0;
        $scoreSouhaitees = 0;

        $competencesManquantes = [];
        $competencesInsuffisantes = [];
        $competencesOk = [];

        foreach ($posteCompetences as $competence) {
            $niveauRequis = $competence->pivot->niveau_requis;
            $obligatoire = $competence->pivot->obligatoire;
            $poids = $competence->pivot->poids;

            $employeComp = $employeCompetences->get($competence->id);
            $niveauEmploye = $employeComp ? $employeComp->pivot->niveau : 0;

            // Calcul du score pour cette compétence
            $scoreCompetence = min(100, ($niveauEmploye / $niveauRequis) * 100);

            $competenceInfo = [
                'competence_id' => $competence->id,
                'nom' => $competence->nom,
                'niveau_requis' => $niveauRequis,
                'niveau_employe' => $niveauEmploye,
                'ecart' => $niveauRequis - $niveauEmploye,
                'obligatoire' => $obligatoire,
                'poids' => $poids,
            ];

            if ($niveauEmploye === 0) {
                $competencesManquantes[] = $competenceInfo;
            } elseif ($niveauEmploye < $niveauRequis) {
                $competencesInsuffisantes[] = $competenceInfo;
            } else {
                $competencesOk[] = $competenceInfo;
            }

            if ($obligatoire) {
                $totalPoidsObligatoires += $poids;
                $scoreObligatoires += $scoreCompetence * $poids;
            } else {
                $totalPoidsSouhaitees += $poids;
                $scoreSouhaitees += $scoreCompetence * $poids;
            }
        }

        // Calcul des scores moyens pondérés
        $moyenneObligatoires = $totalPoidsObligatoires > 0 
            ? round($scoreObligatoires / $totalPoidsObligatoires, 2) 
            : 100;
        
        $moyenneSouhaitees = $totalPoidsSouhaitees > 0 
            ? round($scoreSouhaitees / $totalPoidsSouhaitees, 2) 
            : 100;

        // Score global : 70% obligatoires, 30% souhaitées
        $scoreGlobal = round(($moyenneObligatoires * 0.7) + ($moyenneSouhaitees * 0.3), 2);

        return [
            'score_global' => $scoreGlobal,
            'score_obligatoires' => $moyenneObligatoires,
            'score_souhaitees' => $moyenneSouhaitees,
            'competences_manquantes' => $competencesManquantes,
            'competences_insuffisantes' => $competencesInsuffisantes,
            'competences_ok' => $competencesOk,
            'message' => $this->genererMessage($scoreGlobal),
        ];
    }

    /**
     * Trouver les meilleurs candidats pour un poste
     * 
     * @param Poste $poste
     * @param int $limit Nombre maximum de candidats à retourner
     * @return Collection
     */
    public function trouverCandidats(Poste $poste, int $limit = 10): Collection
    {
        $employes = Employe::with('competences')->get();

        $candidats = $employes->map(function($employe) use ($poste) {
            $compatibilite = $this->calculerCompatibilite($employe, $poste);
            return [
                'employe' => [
                    'id' => $employe->id,
                    'matricule' => $employe->matricule,
                    'nom' => $employe->nom,
                    'prenom' => $employe->prenom,
                    'poste_actuel' => $employe->poste?->nom,
                    'departement' => $employe->departement?->nom,
                ],
                'compatibilite' => $compatibilite,
            ];
        })
        ->sortByDesc('compatibilite.score_global')
        ->take($limit)
        ->values();

        return $candidats;
    }

    /**
     * Trouver les postes compatibles pour un employé
     * 
     * @param Employe $employe
     * @param int $limit
     * @return Collection
     */
    public function trouverPostesCompatibles(Employe $employe, int $limit = 10): Collection
    {
        $postes = Poste::with('competences')->get();

        $postesCompatibles = $postes->map(function($poste) use ($employe) {
            $compatibilite = $this->calculerCompatibilite($employe, $poste);
            return [
                'poste' => [
                    'id' => $poste->id,
                    'nom' => $poste->nom,
                    'departement' => $poste->departement?->nom,
                    'categorie' => $poste->categorie,
                ],
                'compatibilite' => $compatibilite,
            ];
        })
        ->sortByDesc('compatibilite.score_global')
        ->take($limit)
        ->values();

        return $postesCompatibles;
    }

    /**
     * Suggérer des formations pour combler les écarts de compétences
     * 
     * @param Employe $employe
     * @param Poste|null $poste Poste cible (optionnel)
     * @return Collection
     */
    public function suggererFormations(Employe $employe, ?Poste $poste = null): Collection
    {
        $employeCompetences = $employe->competences()->get()->keyBy('id');
        
        // Si un poste est spécifié, on se base sur ses compétences requises
        if ($poste) {
            $competencesCibles = $poste->competences()->get();
        } else {
            // Sinon, on utilise le poste actuel de l'employé
            $posteActuel = $employe->poste;
            if (!$posteActuel) {
                return collect([]);
            }
            $competencesCibles = $posteActuel->competences()->get();
        }

        $ecarts = [];

        foreach ($competencesCibles as $competence) {
            $niveauRequis = $competence->pivot->niveau_requis;
            $employeComp = $employeCompetences->get($competence->id);
            $niveauEmploye = $employeComp ? $employeComp->pivot->niveau : 0;

            if ($niveauEmploye < $niveauRequis) {
                $ecarts[] = [
                    'competence_id' => $competence->id,
                    'competence_nom' => $competence->nom,
                    'niveau_actuel' => $niveauEmploye,
                    'niveau_requis' => $niveauRequis,
                    'ecart' => $niveauRequis - $niveauEmploye,
                    'obligatoire' => $competence->pivot->obligatoire,
                ];
            }
        }

        if (empty($ecarts)) {
            return collect([]);
        }

        // Trier par priorité (obligatoires d'abord, puis par écart)
        usort($ecarts, function($a, $b) {
            if ($a['obligatoire'] !== $b['obligatoire']) {
                return $b['obligatoire'] <=> $a['obligatoire'];
            }
            return $b['ecart'] <=> $a['ecart'];
        });

        // Trouver les formations correspondantes
        $suggestions = [];
        $formationsDejaProposees = [];

        foreach ($ecarts as $ecart) {
            $formations = Formation::actif()
                ->whereHas('competences', function($q) use ($ecart) {
                    $q->where('competence_id', $ecart['competence_id'])
                      ->where('niveau_apport', '>=', $ecart['niveau_requis']);
                })
                ->with('competences')
                ->get();

            foreach ($formations as $formation) {
                if (in_array($formation->id, $formationsDejaProposees)) {
                    continue;
                }

                $formationsDejaProposees[] = $formation->id;
                
                $suggestions[] = [
                    'formation' => $formation,
                    'pour_competence' => $ecart['competence_nom'],
                    'ecart_comble' => $ecart['ecart'],
                    'priorite' => $ecart['obligatoire'] ? 'haute' : 'normale',
                    'raison' => sprintf(
                        'Cette formation permettra d\'atteindre le niveau requis en %s (niveau %d → %d)',
                        $ecart['competence_nom'],
                        $ecart['niveau_actuel'],
                        $ecart['niveau_requis']
                    ),
                ];
            }
        }

        return collect($suggestions);
    }

    /**
     * Générer un message basé sur le score
     */
    private function genererMessage(float $score): string
    {
        if ($score >= 90) {
            return 'Excellent matching ! Le profil correspond très bien au poste.';
        } elseif ($score >= 75) {
            return 'Bon matching. Quelques compétences à développer.';
        } elseif ($score >= 50) {
            return 'Matching moyen. Des formations seraient nécessaires.';
        } elseif ($score >= 25) {
            return 'Matching faible. Écarts importants à combler.';
        } else {
            return 'Profil peu adapté à ce poste.';
        }
    }

    /**
     * Analyse globale des compétences de l'entreprise
     */
    public function analyseGlobale(): array
    {
        $employes = Employe::with('competences.categorie')->get();
        
        $stats = [
            'total_employes' => $employes->count(),
            'par_categorie' => [],
            'competences_rares' => [],
            'competences_communes' => [],
        ];

        $competenceCount = [];
        $categorieStats = [];

        foreach ($employes as $employe) {
            foreach ($employe->competences as $competence) {
                $compId = $competence->id;
                $catId = $competence->categorie_id;
                $catNom = $competence->categorie->nom;

                if (!isset($competenceCount[$compId])) {
                    $competenceCount[$compId] = [
                        'nom' => $competence->nom,
                        'categorie' => $catNom,
                        'count' => 0,
                        'moyenne_niveau' => 0,
                        'niveaux' => [],
                    ];
                }
                $competenceCount[$compId]['count']++;
                $competenceCount[$compId]['niveaux'][] = $competence->pivot->niveau;

                if (!isset($categorieStats[$catId])) {
                    $categorieStats[$catId] = [
                        'nom' => $catNom,
                        'count' => 0,
                        'moyenne_niveau' => 0,
                        'niveaux' => [],
                    ];
                }
                $categorieStats[$catId]['count']++;
                $categorieStats[$catId]['niveaux'][] = $competence->pivot->niveau;
            }
        }

        // Calculer les moyennes
        foreach ($competenceCount as $id => &$data) {
            $data['moyenne_niveau'] = round(array_sum($data['niveaux']) / count($data['niveaux']), 2);
            $data['pourcentage_employes'] = round(($data['count'] / $employes->count()) * 100, 1);
            unset($data['niveaux']);
        }

        foreach ($categorieStats as $id => &$data) {
            $data['moyenne_niveau'] = round(array_sum($data['niveaux']) / count($data['niveaux']), 2);
            unset($data['niveaux']);
        }

        // Trier par fréquence
        uasort($competenceCount, fn($a, $b) => $b['count'] <=> $a['count']);

        $stats['par_categorie'] = array_values($categorieStats);
        $stats['competences_communes'] = array_slice(array_values($competenceCount), 0, 10);
        $stats['competences_rares'] = array_slice(array_values(array_reverse($competenceCount)), 0, 10);

        return $stats;
    }
}
