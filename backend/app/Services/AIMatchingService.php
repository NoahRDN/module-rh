<?php

namespace App\Services;

use App\Models\Employe;
use App\Models\Poste;
use Illuminate\Support\Facades\Log;

class AIMatchingService
{
    public function __construct(
        private CompetenceMatchingService $matchingService,
        private AIProviderService $aiProvider
    ) {}

    /**
     * Analyse simple de compatibilité entre un employé et un poste.
     */
    public function analyserCompatibiliteEmployePoste(Employe $employe, Poste $poste): array
    {
        return $this->analyserProfilPoste($employe, $poste);
    }

    /**
     * Analyse IA d'un profil candidat pour un poste.
     */
    public function analyserProfilPoste(Employe $employe, Poste $poste): array
    {
        $matchingClassique = $this->matchingService->calculerCompatibilite($employe, $poste);

        $profilEmploye = $this->buildEmployeProfile($employe);
        $profilPoste = $this->buildPosteProfile($poste);

        try {
            $analyseIA = $this->callAIAnalysis(
                $profilEmploye,
                $profilPoste,
                $matchingClassique
            );

            return [
                'matching_classique' => $matchingClassique,
                'analyse_ia' => $analyseIA,
                'score_combine' => $this->combinerScores(
                    $matchingClassique['score_global'] ?? 0,
                    $analyseIA['score_ia'] ?? 0
                ),
            ];
        } catch (\Exception $e) {
            Log::error('AI Matching error: ' . $e->getMessage());

            return [
                'matching_classique' => $matchingClassique,
                'analyse_ia' => null,
                'score_combine' => $matchingClassique['score_global'] ?? 0,
                'error' => 'Analyse IA non disponible : ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Construire le profil d'un employé pour l'analyse.
     */
    private function buildEmployeProfile(Employe $employe): array
    {
        $competences = $employe->competences()->with('categorie')->get();
        $formations = $employe->formations()->get();

        return [
            'id' => $employe->id,
            'nom' => trim($employe->nom . ' ' . $employe->prenom),
            'poste_actuel' => $employe->poste?->nom,
            'departement' => $employe->departement?->nom,
            'anciennete' => $employe->date_embauche
                ? now()->diffInMonths($employe->date_embauche) . ' mois'
                : 'N/A',
            'competences' => $competences->map(fn ($c) => [
                'nom' => $c->nom,
                'categorie' => $c->categorie?->nom,
                'niveau' => ($c->pivot->niveau ?? 0) . '/5',
            ])->toArray(),
            'formations_suivies' => $formations->map(fn ($f) => [
                'nom' => $f->nom,
                'statut' => $f->pivot->statut ?? null,
                'certificat' => $f->pivot->certificat_obtenu ?? false,
            ])->toArray(),
            'nb_formations_terminees' => $formations
                ->filter(fn ($f) => ($f->pivot->statut ?? null) === 'terminee')
                ->count(),
        ];
    }

    /**
     * Construire le profil d'un poste pour l'analyse.
     */
    private function buildPosteProfile(Poste $poste): array
    {
        $competencesRequises = $poste->competences()->get();

        return [
            'id' => $poste->id,
            'titre' => $poste->nom,
            'departement' => $poste->departement?->nom,
            'categorie' => $poste->categorie ?? 'Non définie',
            'description' => $poste->description ?? '',
            'competences_requises' => $competencesRequises->map(fn ($c) => [
                'nom' => $c->nom,
                'niveau_requis' => ($c->pivot->niveau_requis ?? 0) . '/5',
                'obligatoire' => ($c->pivot->obligatoire ?? false) ? 'Oui' : 'Non',
                'poids' => $c->pivot->poids ?? 1,
            ])->toArray(),
        ];
    }

    /**
     * Appeler le provider IA pour l'analyse de matching.
     */
    private function callAIAnalysis(
        array $profilEmploye,
        array $profilPoste,
        array $matchingClassique
    ): array {
        $systemPrompt = <<<PROMPT
Tu es un expert en ressources humaines et en évaluation de profils.

Tu dois analyser la compatibilité entre un employé et un poste.

Réponds uniquement en JSON valide avec cette structure exacte :

{
    "score_ia": number,
    "points_forts": ["..."],
    "points_faibles": ["..."],
    "recommandations": ["..."],
    "potentiel_evolution": "faible|moyen|fort|excellent",
    "commentaire_global": "..."
}
PROMPT;

        $userPrompt = sprintf(
            "Analyse ce matching :\n\nProfil Employé :\n%s\n\nPoste Cible :\n%s\n\nScore algorithme classique : %s%%\n\nCompétences manquantes : %s\nCompétences insuffisantes : %s",
            json_encode($profilEmploye, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            json_encode($profilPoste, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            $matchingClassique['score_global'] ?? 0,
            json_encode(
                array_column($matchingClassique['competences_manquantes'] ?? [], 'nom'),
                JSON_UNESCAPED_UNICODE
            ),
            json_encode(
                array_column($matchingClassique['competences_insuffisantes'] ?? [], 'nom'),
                JSON_UNESCAPED_UNICODE
            )
        );

        return $this->aiProvider->chatJson($systemPrompt, $userPrompt, [
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ]);
    }

    /**
     * Combiner les scores classique et IA.
     */
    private function combinerScores(float $scoreClassique, float $scoreIA): float
    {
        if ($scoreIA <= 0) {
            return round($scoreClassique, 1);
        }

        return round(($scoreClassique * 0.6) + ($scoreIA * 0.4), 1);
    }

    /**
     * Analyser un CV texte pour extraction de compétences.
     */
    public function analyserCV(string $cvTexte, Poste $poste): array
    {
        $competencesPoste = $poste->competences()->get();
        $listeCompetences = $competencesPoste->pluck('nom')->toArray();

        $systemPrompt = sprintf(
            <<<PROMPT
Tu es un expert en analyse de CV.

Tu dois extraire les informations clés d'un CV et évaluer la compatibilité avec un poste.

Compétences recherchées pour le poste : %s

Réponds uniquement en JSON valide avec cette structure exacte :

{
    "informations_personnelles": {
        "nom": "...",
        "experience_annees": number
    },
    "competences_detectees": [
        {
            "nom": "...",
            "niveau_estime": number,
            "justification": "..."
        }
    ],
    "formations": ["..."],
    "experiences": [
        {
            "poste": "...",
            "duree": "...",
            "pertinence": "faible|moyenne|forte"
        }
    ],
    "score_compatibilite": number,
    "points_forts": ["..."],
    "points_faibles": ["..."],
    "recommandation": "recommandé|à considérer|non recommandé"
}
PROMPT,
            implode(', ', $listeCompetences)
        );

        $userPrompt = "Analyse ce CV :\n\n" . $cvTexte;

        try {
            $analyse = $this->aiProvider->chatJson($systemPrompt, $userPrompt, [
                'temperature' => 0.5,
                'max_tokens' => 1500,
            ]);

            return [
                'success' => true,
                'analyse' => $analyse,
                'poste' => [
                    'id' => $poste->id,
                    'titre' => $poste->nom,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('CV Analysis error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'poste' => [
                    'id' => $poste->id,
                    'titre' => $poste->nom,
                ],
            ];
        }
    }

    /**
     * Suggérer des formations basées sur l'analyse IA.
     */
    public function suggererFormationsIA(Employe $employe, ?Poste $posteCible = null): array
    {
        $profil = $this->buildEmployeProfile($employe);
        $posteProfile = $posteCible ? $this->buildPosteProfile($posteCible) : null;

        $systemPrompt = <<<PROMPT
Tu es un conseiller en développement professionnel.

Analyse le profil d'un employé et suggère des formations pertinentes pour son évolution.

Réponds uniquement en JSON valide avec cette structure exacte :

{
    "analyse_profil": "...",
    "formations_recommandees": [
        {
            "titre": "...",
            "objectif": "...",
            "priorite": "haute|moyenne|basse",
            "duree_estimee": "...",
            "competences_developpees": ["..."]
        }
    ],
    "parcours_suggere": "...",
    "objectifs_a_6_mois": ["..."],
    "objectifs_a_12_mois": ["..."]
}
PROMPT;

        $userPrompt = "Profil employé :\n" .
            json_encode($profil, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($posteProfile) {
            $userPrompt .= "\n\nPoste cible :\n" .
                json_encode($posteProfile, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        try {
            $suggestionsIA = $this->aiProvider->chatJson($systemPrompt, $userPrompt, [
                'temperature' => 0.7,
                'max_tokens' => 1200,
            ]);

            return [
                'success' => true,
                'suggestions_ia' => $suggestionsIA,
                'suggestions_classiques' => $this->matchingService
                    ->suggererFormations($employe, $posteCible)
                    ->toArray(),
            ];
        } catch (\Exception $e) {
            Log::error('Training suggestion AI error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'suggestions_classiques' => $this->matchingService
                    ->suggererFormations($employe, $posteCible)
                    ->toArray(),
            ];
        }
    }

    /**
     * Générer un plan de carrière personnalisé.
     */
    public function genererPlanCarriere(Employe $employe): array
    {
        $profil = $this->buildEmployeProfile($employe);

        $historiquePostes = $employe
            ->historiquePostes()
            ->with('poste')
            ->orderBy('date_changement')
            ->get();

        $systemPrompt = <<<PROMPT
Tu es un conseiller en carrière expert.

Analyse le profil et l'historique d'un employé pour proposer un plan de carrière personnalisé.

Réponds uniquement en JSON valide avec cette structure exacte :

{
    "analyse_parcours": "...",
    "points_forts_carriere": ["..."],
    "axes_amelioration": ["..."],
    "postes_cibles_court_terme": [
        {
            "poste": "...",
            "probabilite": "haute|moyenne|basse",
            "competences_a_acquerir": ["..."]
        }
    ],
    "postes_cibles_moyen_terme": [
        {
            "poste": "...",
            "horizon": "...",
            "prerequis": ["..."]
        }
    ],
    "plan_action": [
        {
            "action": "...",
            "delai": "...",
            "objectif": "..."
        }
    ],
    "conseil_personnalise": "..."
}
PROMPT;

        $userPrompt = sprintf(
            "Profil :\n%s\n\nHistorique des postes :\n%s",
            json_encode($profil, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            json_encode(
                $historiquePostes->map(fn ($h) => [
                    'poste' => $h->poste?->nom,
                    'date' => $h->date_changement,
                    'motif' => $h->motif,
                ])->toArray(),
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            )
        );

        try {
            $planCarriere = $this->aiProvider->chatJson($systemPrompt, $userPrompt, [
                'temperature' => 0.7,
                'max_tokens' => 1500,
            ]);

            return [
                'success' => true,
                'employe' => [
                    'id' => $employe->id,
                    'nom' => trim($employe->nom . ' ' . $employe->prenom),
                    'poste' => $employe->poste?->nom,
                ],
                'plan_carriere' => $planCarriere,
            ];
        } catch (\Exception $e) {
            Log::error('Career plan error: ' . $e->getMessage());

            $postesCompatibles = $this->matchingService
                ->trouverPostesCompatibles($employe, 3)
                ->map(function ($p) {
                    return [
                        'poste' => $p['poste']['nom'] ?? 'Poste',
                        'poste_id' => $p['poste']['id'] ?? null,
                        'score' => $p['compatibilite']['score_global'] ?? null,
                    ];
                })
                ->toArray();

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'employe' => [
                    'id' => $employe->id,
                    'nom' => trim($employe->nom . ' ' . $employe->prenom),
                    'poste' => $employe->poste?->nom,
                ],
                'plan_carriere' => [
                    'analyse_parcours' => 'Plan généré sans IA complète.',
                    'points_forts_carriere' => [],
                    'axes_amelioration' => [],
                    'postes_cibles_court_terme' => $postesCompatibles,
                    'postes_compatibles' => $postesCompatibles,
                    'postes_cibles_moyen_terme' => [],
                    'plan_action' => [
                        [
                            'action' => 'Identifier les compétences manquantes pour le poste cible',
                            'delai' => '3 mois',
                            'objectif' => 'Réduire l’écart de compétences',
                        ],
                        [
                            'action' => 'Suivre 1-2 formations ciblées',
                            'delai' => '6 mois',
                            'objectif' => 'Atteindre le niveau requis',
                        ],
                    ],
                    'conseil_personnalise' => 'L’analyse IA complète est indisponible pour le moment.',
                ],
            ];
        }
    }
}