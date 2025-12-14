<?php

namespace App\Services;

use App\Models\Employe;
use App\Models\Contrat;
use App\Models\DemandeConge;
use App\Models\Evaluation;
use App\Models\Pointage;
use App\Models\Formation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TurnoverPredictionService
{
    /**
     * Poids des différents facteurs de risque
     */
    private array $factorWeights = [
        'anciennete' => 20,
        'satisfaction' => 25,
        'absences' => 15,
        'retards' => 10,
        'formations' => 10,
        'promotion' => 15,
        'salaire' => 5,
    ];

    /**
     * Calculer le risque de départ pour un employé
     */
    public function calculerRisqueEmploye(Employe $employe): array
    {
        $scores = [];
        $details = [];

        // 1. Facteur ancienneté
        $ancienneteResult = $this->evaluerAnciennete($employe);
        $scores['anciennete'] = $ancienneteResult['score'];
        $details['anciennete'] = $ancienneteResult;

        // 2. Facteur satisfaction (basé sur les évaluations)
        $satisfactionResult = $this->evaluerSatisfaction($employe);
        $scores['satisfaction'] = $satisfactionResult['score'];
        $details['satisfaction'] = $satisfactionResult;

        // 3. Facteur absences
        $absencesResult = $this->evaluerAbsences($employe);
        $scores['absences'] = $absencesResult['score'];
        $details['absences'] = $absencesResult;

        // 4. Facteur retards
        $retardsResult = $this->evaluerRetards($employe);
        $scores['retards'] = $retardsResult['score'];
        $details['retards'] = $retardsResult;

        // 5. Facteur formations (engagement)
        $formationsResult = $this->evaluerFormations($employe);
        $scores['formations'] = $formationsResult['score'];
        $details['formations'] = $formationsResult;

        // 6. Facteur évolution de carrière
        $promotionResult = $this->evaluerEvolutionCarriere($employe);
        $scores['promotion'] = $promotionResult['score'];
        $details['promotion'] = $promotionResult;

        // 7. Facteur compétitivité salariale
        $salaireResult = $this->evaluerSalaire($employe);
        $scores['salaire'] = $salaireResult['score'];
        $details['salaire'] = $salaireResult;

        // Calcul du score global pondéré
        $scoreGlobal = 0;
        $totalPoids = array_sum($this->factorWeights);
        
        foreach ($scores as $factor => $score) {
            $scoreGlobal += ($score * $this->factorWeights[$factor]) / $totalPoids;
        }

        $scoreGlobal = round($scoreGlobal, 1);
        $niveauRisque = $this->determinerNiveauRisque($scoreGlobal);

        return [
            'employe' => [
                'id' => $employe->id,
                'matricule' => $employe->matricule,
                'nom' => $employe->nom,
                'prenom' => $employe->prenom,
                'poste' => $employe->poste?->nom,
                'departement' => $employe->departement?->nom,
            ],
            'score_global' => $scoreGlobal,
            'niveau_risque' => $niveauRisque,
            'scores_facteurs' => $scores,
            'details' => $details,
            'recommandations' => $this->genererRecommandations($scores, $details),
        ];
    }

    /**
     * Évaluer le facteur ancienneté
     */
    private function evaluerAnciennete(Employe $employe): array
    {
        $dateEmbauche = $employe->date_embauche;
        
        if (!$dateEmbauche) {
            return [
                'score' => 50,
                'valeur' => 'Non définie',
                'analyse' => 'Date d\'embauche non renseignée',
            ];
        }

        $anciennete = Carbon::parse($dateEmbauche)->diffInMonths(now());
        
        // Risque plus élevé entre 6 mois et 2 ans (période critique)
        // et après 7 ans (stagnation potentielle)
        if ($anciennete < 6) {
            $score = 30; // Période d'adaptation
        } elseif ($anciennete < 12) {
            $score = 70; // Première année critique
        } elseif ($anciennete < 24) {
            $score = 60; // Période de consolidation
        } elseif ($anciennete < 60) {
            $score = 30; // Période stable
        } elseif ($anciennete < 84) {
            $score = 40; // Début de stagnation potentielle
        } else {
            $score = 50; // Longue ancienneté - vérifier satisfaction
        }

        return [
            'score' => $score,
            'valeur' => $anciennete . ' mois',
            'annees' => round($anciennete / 12, 1),
            'analyse' => $this->analyserAnciennete($anciennete),
        ];
    }

    /**
     * Analyser l'ancienneté
     */
    private function analyserAnciennete(int $mois): string
    {
        if ($mois < 6) {
            return 'Nouvel employé en période d\'intégration';
        } elseif ($mois < 12) {
            return 'Première année - période sensible aux départs';
        } elseif ($mois < 24) {
            return 'Consolidation - risque modéré si pas d\'évolution';
        } elseif ($mois < 60) {
            return 'Période stable - faible risque';
        } else {
            return 'Ancienneté importante - surveiller la motivation';
        }
    }

    /**
     * Évaluer le facteur satisfaction (via évaluations)
     */
    private function evaluerSatisfaction(Employe $employe): array
    {
        $evaluations = Evaluation::where('employe_id', $employe->id)
            ->orderBy('date_evaluation', 'desc')
            ->limit(3)
            ->get();

        if ($evaluations->isEmpty()) {
            return [
                'score' => 50,
                'valeur' => 'Aucune évaluation',
                'analyse' => 'Pas d\'évaluation disponible pour mesurer la satisfaction',
            ];
        }

        $moyenneNote = $evaluations->avg('note_globale');
        $derniereTendance = $this->calculerTendance($evaluations->pluck('note_globale')->toArray());

        // Score inversé : bonne note = faible risque
        $score = max(0, 100 - ($moyenneNote * 20));
        
        // Ajuster selon la tendance
        if ($derniereTendance < -0.5) {
            $score += 15; // Tendance négative = plus de risque
        } elseif ($derniereTendance > 0.5) {
            $score -= 10; // Tendance positive = moins de risque
        }

        return [
            'score' => min(100, max(0, $score)),
            'valeur' => round($moyenneNote, 1) . '/5',
            'tendance' => $derniereTendance > 0 ? 'positive' : ($derniereTendance < 0 ? 'négative' : 'stable'),
            'nb_evaluations' => $evaluations->count(),
            'analyse' => $moyenneNote >= 4 
                ? 'Bonnes évaluations - employé probablement satisfait'
                : ($moyenneNote >= 3 
                    ? 'Évaluations moyennes - surveiller'
                    : 'Évaluations faibles - risque élevé de départ'),
        ];
    }

    /**
     * Évaluer le facteur absences
     */
    private function evaluerAbsences(Employe $employe): array
    {
        $sixMoisAgo = Carbon::now()->subMonths(6);
        
        $absences = DemandeConge::where('employe_id', $employe->id)
            ->where('statut', 'approuvee')
            ->where('date_debut', '>=', $sixMoisAgo)
            ->sum('jours_demandes');

        $absencesNonJustifiees = DemandeConge::where('employe_id', $employe->id)
            ->whereIn('statut', ['rejetee', 'annulee'])
            ->where('created_at', '>=', $sixMoisAgo)
            ->count();

        // Plus d'absences = plus de risque (désengagement potentiel)
        $score = min(100, ($absences * 3) + ($absencesNonJustifiees * 10));

        return [
            'score' => $score,
            'jours_absences' => $absences,
            'demandes_rejetees' => $absencesNonJustifiees,
            'periode' => '6 derniers mois',
            'analyse' => $absences > 15 
                ? 'Nombreuses absences - possible désengagement'
                : ($absences > 8 
                    ? 'Absences modérées'
                    : 'Présence régulière'),
        ];
    }

    /**
     * Évaluer le facteur retards
     */
    private function evaluerRetards(Employe $employe): array
    {
        $troisMoisAgo = Carbon::now()->subMonths(3);
        
        // Compter les pointages tardifs (après 9h par exemple)
        $pointages = Pointage::where('employe_id', $employe->id)
            ->where('type', 'entree')
            ->where('pointe_a', '>=', $troisMoisAgo)
            ->get();

        $retards = 0;
        $heureNormale = '09:00:00'; // À ajuster selon config
        
        foreach ($pointages as $pointage) {
            if (Carbon::parse($pointage->pointe_a)->format('H:i:s') > $heureNormale) {
                $retards++;
            }
        }

        $totalPointages = $pointages->count();
        $tauxRetard = $totalPointages > 0 ? ($retards / $totalPointages) * 100 : 0;

        $score = min(100, $tauxRetard * 2);

        return [
            'score' => $score,
            'nb_retards' => $retards,
            'total_jours' => $totalPointages,
            'taux_retard' => round($tauxRetard, 1) . '%',
            'periode' => '3 derniers mois',
            'analyse' => $tauxRetard > 20 
                ? 'Retards fréquents - possible démotivation'
                : ($tauxRetard > 10 
                    ? 'Quelques retards à surveiller'
                    : 'Ponctualité satisfaisante'),
        ];
    }

    /**
     * Évaluer le facteur formations (engagement)
     */
    private function evaluerFormations(Employe $employe): array
    {
        $formations = $employe->formations()->get();
        $formationsTerminees = $formations->where('pivot.statut', 'terminee')->count();
        $formationsEnCours = $formations->where('pivot.statut', 'en_cours')->count();
        $certificats = $formations->where('pivot.certificat_obtenu', true)->count();

        // Plus de formations = plus engagé = moins de risque
        $engagement = $formationsTerminees + ($formationsEnCours * 0.5) + ($certificats * 0.5);
        $score = max(0, 70 - ($engagement * 10)); // Inverse

        return [
            'score' => $score,
            'formations_terminees' => $formationsTerminees,
            'formations_en_cours' => $formationsEnCours,
            'certificats_obtenus' => $certificats,
            'analyse' => $engagement >= 3 
                ? 'Fort engagement dans la formation'
                : ($engagement >= 1 
                    ? 'Engagement modéré'
                    : 'Peu de formations - vérifier les opportunités'),
        ];
    }

    /**
     * Évaluer l'évolution de carrière
     */
    private function evaluerEvolutionCarriere(Employe $employe): array
    {
        $historiquePostes = $employe->historiquePostes()
            ->orderBy('date_changement', 'desc')
            ->get();

        $nbChangements = $historiquePostes->count();
        $dernierChangement = $historiquePostes->first()?->date_changement;
        
        $moisDepuisDernierChangement = $dernierChangement 
            ? Carbon::parse($dernierChangement)->diffInMonths(now())
            : ($employe->date_embauche ? Carbon::parse($employe->date_embauche)->diffInMonths(now()) : 0);

        // Pas d'évolution depuis longtemps = risque
        if ($moisDepuisDernierChangement > 36 && $nbChangements < 2) {
            $score = 70; // Stagnation
        } elseif ($moisDepuisDernierChangement > 24) {
            $score = 50;
        } else {
            $score = 20;
        }

        return [
            'score' => $score,
            'nb_changements_poste' => $nbChangements,
            'mois_depuis_dernier' => $moisDepuisDernierChangement,
            'analyse' => $moisDepuisDernierChangement > 36 
                ? 'Stagnation - risque de départ pour évolution ailleurs'
                : ($nbChangements > 0 
                    ? 'Évolution récente - bon signe'
                    : 'Surveiller les opportunités d\'évolution'),
        ];
    }

    /**
     * Évaluer la compétitivité salariale
     */
    private function evaluerSalaire(Employe $employe): array
    {
        $contrat = Contrat::where('employe_id', $employe->id)
            ->where('statut', 'actif')
            ->first();

        if (!$contrat) {
            return [
                'score' => 50,
                'valeur' => 'Non défini',
                'analyse' => 'Pas de contrat actif trouvé',
            ];
        }

        // Comparer au salaire moyen du même poste
        $salaireMoyenPoste = Contrat::whereHas('employe', function($q) use ($employe) {
            $q->where('poste_id', $employe->poste_id);
        })
        ->where('statut', 'actif')
        ->avg('salaire_base');

        if (!$salaireMoyenPoste || $salaireMoyenPoste == 0) {
            return [
                'score' => 50,
                'salaire' => number_format($contrat->salaire_base, 0, ',', ' ') . ' Ar',
                'analyse' => 'Pas assez de données pour comparer',
            ];
        }

        $ecartPourcentage = (($contrat->salaire_base - $salaireMoyenPoste) / $salaireMoyenPoste) * 100;

        // En dessous de la moyenne = risque
        if ($ecartPourcentage < -10) {
            $score = 80;
        } elseif ($ecartPourcentage < 0) {
            $score = 60;
        } else {
            $score = 30;
        }

        return [
            'score' => $score,
            'salaire' => number_format($contrat->salaire_base, 0, ',', ' ') . ' Ar',
            'moyenne_poste' => number_format($salaireMoyenPoste, 0, ',', ' ') . ' Ar',
            'ecart' => round($ecartPourcentage, 1) . '%',
            'analyse' => $ecartPourcentage < -10 
                ? 'Salaire inférieur à la moyenne du poste'
                : ($ecartPourcentage > 10 
                    ? 'Salaire supérieur à la moyenne'
                    : 'Salaire dans la moyenne'),
        ];
    }

    /**
     * Calculer la tendance d'une série
     */
    private function calculerTendance(array $valeurs): float
    {
        if (count($valeurs) < 2) {
            return 0;
        }

        $n = count($valeurs);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumX += $i;
            $sumY += $valeurs[$i];
            $sumXY += $i * $valeurs[$i];
            $sumX2 += $i * $i;
        }

        $denominator = ($n * $sumX2 - $sumX * $sumX);
        if ($denominator == 0) {
            return 0;
        }

        return ($n * $sumXY - $sumX * $sumY) / $denominator;
    }

    /**
     * Déterminer le niveau de risque
     */
    private function determinerNiveauRisque(float $score): array
    {
        if ($score >= 70) {
            return [
                'niveau' => 'critique',
                'label' => 'Risque Critique',
                'couleur' => '#dc2626',
                'action' => 'Action urgente requise',
            ];
        } elseif ($score >= 50) {
            return [
                'niveau' => 'eleve',
                'label' => 'Risque Élevé',
                'couleur' => '#f97316',
                'action' => 'Surveillance renforcée recommandée',
            ];
        } elseif ($score >= 30) {
            return [
                'niveau' => 'modere',
                'label' => 'Risque Modéré',
                'couleur' => '#eab308',
                'action' => 'Maintenir le suivi habituel',
            ];
        } else {
            return [
                'niveau' => 'faible',
                'label' => 'Risque Faible',
                'couleur' => '#22c55e',
                'action' => 'Situation favorable',
            ];
        }
    }

    /**
     * Générer des recommandations
     */
    private function genererRecommandations(array $scores, array $details): array
    {
        $recommandations = [];

        if ($scores['satisfaction'] >= 60) {
            $recommandations[] = [
                'priorite' => 'haute',
                'type' => 'entretien',
                'action' => 'Planifier un entretien de suivi pour comprendre les préoccupations',
            ];
        }

        if ($scores['promotion'] >= 50) {
            $recommandations[] = [
                'priorite' => 'moyenne',
                'type' => 'evolution',
                'action' => 'Discuter des perspectives d\'évolution de carrière',
            ];
        }

        if ($scores['formations'] >= 50) {
            $recommandations[] = [
                'priorite' => 'moyenne',
                'type' => 'formation',
                'action' => 'Proposer des formations pour renforcer l\'engagement',
            ];
        }

        if ($scores['salaire'] >= 60) {
            $recommandations[] = [
                'priorite' => 'haute',
                'type' => 'remuneration',
                'action' => 'Réévaluer le positionnement salarial',
            ];
        }

        if ($scores['absences'] >= 50 || $scores['retards'] >= 50) {
            $recommandations[] = [
                'priorite' => 'moyenne',
                'type' => 'suivi',
                'action' => 'Vérifier les conditions de travail et le bien-être',
            ];
        }

        return $recommandations;
    }

    /**
     * Analyser tous les employés
     */
    public function analyserTousEmployes(): array
    {
        $employes = Employe::with(['poste', 'departement', 'contrats'])->get();
        
        $resultats = [];
        $statsParDepartement = [];
        $statsParNiveau = [
            'critique' => 0,
            'eleve' => 0,
            'modere' => 0,
            'faible' => 0,
        ];

        foreach ($employes as $employe) {
            $analyse = $this->calculerRisqueEmploye($employe);
            $resultats[] = $analyse;
            
            $niveau = $analyse['niveau_risque']['niveau'];
            $statsParNiveau[$niveau]++;
            
            $deptNom = $employe->departement?->nom ?? 'Non assigné';
            if (!isset($statsParDepartement[$deptNom])) {
                $statsParDepartement[$deptNom] = [
                    'total' => 0,
                    'score_moyen' => 0,
                    'critique' => 0,
                    'eleve' => 0,
                ];
            }
            $statsParDepartement[$deptNom]['total']++;
            $statsParDepartement[$deptNom]['score_moyen'] += $analyse['score_global'];
            if ($niveau === 'critique') $statsParDepartement[$deptNom]['critique']++;
            if ($niveau === 'eleve') $statsParDepartement[$deptNom]['eleve']++;
        }

        // Calculer les moyennes par département
        foreach ($statsParDepartement as $dept => &$stats) {
            $stats['score_moyen'] = round($stats['score_moyen'] / $stats['total'], 1);
        }

        // Trier par score décroissant
        usort($resultats, fn($a, $b) => $b['score_global'] <=> $a['score_global']);

        return [
            'employes' => $resultats,
            'statistiques' => [
                'total_employes' => count($employes),
                'par_niveau' => $statsParNiveau,
                'par_departement' => $statsParDepartement,
                'taux_risque_eleve' => round(
                    (($statsParNiveau['critique'] + $statsParNiveau['eleve']) / max(1, count($employes))) * 100, 
                    1
                ),
            ],
            'alertes' => array_filter($resultats, fn($r) => $r['niveau_risque']['niveau'] === 'critique'),
        ];
    }

    /**
     * Obtenir le top des employés à risque
     */
    public function getTopRisques(int $limit = 10): Collection
    {
        $analyse = $this->analyserTousEmployes();
        return collect(array_slice($analyse['employes'], 0, $limit));
    }
}
