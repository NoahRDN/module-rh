<?php

namespace App\Services;

use App\Models\Employe;
use App\Models\Poste;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIMatchingService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';
    private string $model = 'gpt-4o-mini';
    private CompetenceMatchingService $matchingService;

    public function __construct(CompetenceMatchingService $matchingService)
    {
        $this->apiKey = env('API_KEY', '');
        $this->matchingService = $matchingService;
    }

    /**
     * Analyse IA d'un profil candidat pour un poste
     */
    public function analyserProfilPoste(Employe $employe, Poste $poste): array
    {
        // D'abord, calculer le matching classique
        $matchingClassique = $this->matchingService->calculerCompatibilite($employe, $poste);

        // Si aucune clé API, on retourne simplement le matching classique pour éviter les erreurs
        // if (empty($this->apiKey)) {
        //     return [
        //         'matching_classique' => $matchingClassique,
        //         'analyse_ia' => null,
        //         'score_combine' => $matchingClassique['score_global'],
        //         'message' => 'Analyse IA non disponible (clé API manquante)',
        //     ];
        // }

        // Préparer les données pour l'IA
        $profilEmploye = $this->buildEmployeProfile($employe);
        $profilPoste = $this->buildPosteProfile($poste);

        try {
            $analyseIA = $this->callAIAnalysis($profilEmploye, $profilPoste, $matchingClassique);

            return [
                'matching_classique' => $matchingClassique,
                'analyse_ia' => $analyseIA,
                'score_combine' => $this->combinerScores($matchingClassique['score_global'], $analyseIA['score_ia'] ?? 0),
            ];
        } catch (\Exception $e) {
            Log::error('AI Matching error: ' . $e->getMessage());
            return [
                'matching_classique' => $matchingClassique,
                'analyse_ia' => null,
                'score_combine' => $matchingClassique['score_global'],
                'error' => 'Analyse IA non disponible',
            ];
        }
    }

    /**
     * Construire le profil d'un employé pour l'analyse
     */
    private function buildEmployeProfile(Employe $employe): array
    {
        $competences = $employe->competences()->with('categorie')->get();
        $formations = $employe->formations()->get();

        return [
            'nom' => $employe->nom . ' ' . $employe->prenom,
            'poste_actuel' => $employe->poste?->nom,
            'departement' => $employe->departement?->nom,
            'anciennete' => $employe->date_embauche
                ? now()->diffInMonths($employe->date_embauche) . ' mois'
                : 'N/A',
            'competences' => $competences->map(fn($c) => [
                'nom' => $c->nom,
                'categorie' => $c->categorie?->nom,
                'niveau' => $c->pivot->niveau . '/5',
            ])->toArray(),
            'formations_suivies' => $formations->map(fn($f) => [
                'nom' => $f->nom,
                'statut' => $f->pivot->statut,
                'certificat' => $f->pivot->certificat_obtenu,
            ])->toArray(),
            'nb_formations_terminees' => $formations->where('pivot.statut', 'terminee')->count(),
        ];
    }

    /**
     * Construire le profil d'un poste pour l'analyse
     */
    private function buildPosteProfile(Poste $poste): array
    {
        $competencesRequises = $poste->competences()->get();

        return [
            'titre' => $poste->nom,
            'departement' => $poste->departement?->nom,
            'categorie' => $poste->categorie ?? 'Non définie',
            'description' => $poste->description ?? '',
            'competences_requises' => $competencesRequises->map(fn($c) => [
                'nom' => $c->nom,
                'niveau_requis' => $c->pivot->niveau_requis . '/5',
                'obligatoire' => $c->pivot->obligatoire ? 'Oui' : 'Non',
                'poids' => $c->pivot->poids,
            ])->toArray(),
        ];
    }

    /**
     * Appeler l'API pour l'analyse IA
     */
    private function callAIAnalysis(array $profilEmploye, array $profilPoste, array $matchingClassique): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Clé API non configurée');
        }

        $systemPrompt = <<<PROMPT
Tu es un expert en ressources humaines et en évaluation de profils. Tu dois analyser la compatibilité entre un candidat/employé et un poste.

Tu dois fournir:
1. Un score IA de 0 à 100 basé sur ton analyse qualitative
2. Une analyse des points forts du candidat pour ce poste
3. Une analyse des points faibles/lacunes
4. Des recommandations concrètes (formations, accompagnement, etc.)
5. Une évaluation du potentiel d'évolution

Réponds en JSON avec cette structure:
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
            "Analyse ce matching:\n\nProfil Employé:\n%s\n\nPoste Cible:\n%s\n\nScore algorithme classique: %d%%\n\nCompétences manquantes: %s\nCompétences insuffisantes: %s",
            json_encode($profilEmploye, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            json_encode($profilPoste, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            $matchingClassique['score_global'],
            json_encode(array_column($matchingClassique['competences_manquantes'], 'nom')),
            json_encode(array_column($matchingClassique['competences_insuffisantes'], 'nom'))
        );

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->withoutVerifying()->post($this->apiUrl, [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => 0.7,
            'max_tokens' => 1000,
            'response_format' => ['type' => 'json_object'],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Erreur API: ' . $response->status());
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? '{}';

        return json_decode($content, true) ?? [];
    }

    /**
     * Combiner les scores classique et IA
     */
    private function combinerScores(float $scoreClassique, float $scoreIA): float
    {
        // Pondération: 60% classique, 40% IA
        if ($scoreIA === 0) {
            return $scoreClassique;
        }
        return round(($scoreClassique * 0.6) + ($scoreIA * 0.4), 1);
    }

    /**
     * Analyser un CV texte pour extraction de compétences
     */
    public function analyserCV(string $cvTexte, Poste $poste): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'Clé API non configurée',
                // 'success' => true,
                // 'poste' => [
                //     'id' => $poste->id,
                //     'titre' => $poste->nom,
                // ],
                // 'score' => 0,
                // 'analyse' => 'Analyse IA non disponible (clé API manquante)',
                // 'points_forts' => [],
                // 'points_faibles' => [],
            ];
        }

        $competencesPoste = $poste->competences()->get();
        $listeCompetences = $competencesPoste->pluck('nom')->toArray();

        $systemPrompt = <<<PROMPT
Tu es un expert en analyse de CV. Tu dois extraire les informations clés d'un CV et évaluer la compatibilité avec un poste.

Compétences recherchées pour le poste: %s

Réponds en JSON:
{
    "informations_personnelles": {
        "nom": "...",
        "experience_annees": number
    },
    "competences_detectees": [
        {"nom": "...", "niveau_estime": 1-5, "justification": "..."}
    ],
    "formations": ["..."],
    "experiences": [
        {"poste": "...", "duree": "...", "pertinence": "faible|moyenne|forte"}
    ],
    "score_compatibilite": number (0-100),
    "points_forts": ["..."],
    "points_faibles": ["..."],
    "recommandation": "recommandé|à considérer|non recommandé"
}
PROMPT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => sprintf($systemPrompt, implode(', ', $listeCompetences))],
                    ['role' => 'user', 'content' => "Analyse ce CV:\n\n" . $cvTexte],
                ],
                'temperature' => 0.5,
                'max_tokens' => 1500,
                'response_format' => ['type' => 'json_object'],
            ]);

            if (!$response->successful()) {
                throw new \Exception('Erreur API: ' . $response->status());
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '{}';
            $analyse = json_decode($content, true) ?? [];

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
            ];
        }
    }

    /**
     * Suggérer des formations basées sur l'analyse IA
     */
    public function suggererFormationsIA(Employe $employe, ?Poste $posteCible = null): array
    {
        $profil = $this->buildEmployeProfile($employe);
        $posteProfile = $posteCible ? $this->buildPosteProfile($posteCible) : null;

        if (empty($this->apiKey)) {
            // Fallback vers le service classique
            return $this->matchingService->suggererFormations($employe, $posteCible)->toArray();
        }

        $systemPrompt = <<<PROMPT
Tu es un conseiller en développement professionnel. Analyse le profil d'un employé et suggère des formations pertinentes pour son évolution.

Réponds en JSON:
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

        $userPrompt = "Profil employé:\n" . json_encode($profil, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($posteProfile) {
            $userPrompt .= "\n\nPoste cible:\n" . json_encode($posteProfile, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 1200,
                'response_format' => ['type' => 'json_object'],
            ]);

            if (!$response->successful()) {
                throw new \Exception('Erreur API');
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '{}';

            return [
                'success' => true,
                'suggestions_ia' => json_decode($content, true),
                'suggestions_classiques' => $this->matchingService->suggererFormations($employe, $posteCible)->toArray(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'suggestions_classiques' => $this->matchingService->suggererFormations($employe, $posteCible)->toArray(),
            ];
        }
    }

    /**
     * Générer un plan de carrière personnalisé
     */
    public function genererPlanCarriere(Employe $employe): array
    {
        $profil = $this->buildEmployeProfile($employe);
        $historiquePostes = $employe->historiquePostes()->with('poste')->orderBy('date_changement')->get();

        if (empty($this->apiKey)) {
            // Fallback local (sans IA) pour éviter un écran vide
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
                'success' => true,
                'employe' => [
                    'id' => $employe->id,
                    'nom' => $employe->nom . ' ' . $employe->prenom,
                    'poste' => $employe->poste?->nom,
                ],
                'plan_carriere' => [
                    'analyse_parcours' => 'Plan généré sans IA (clé API manquante)',
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
                    'conseil_personnalise' => 'Ajoutez une clé API pour obtenir un plan détaillé généré par l’IA.',
                ],
            ];
        }

        $systemPrompt = <<<PROMPT
Tu es un conseiller en carrière expert. Analyse le profil et l'historique d'un employé pour proposer un plan de carrière personnalisé.

Réponds en JSON:
{
    "analyse_parcours": "...",
    "points_forts_carriere": ["..."],
    "axes_amelioration": ["..."],
    "postes_cibles_court_terme": [
        {"poste": "...", "probabilite": "haute|moyenne|basse", "competences_a_acquerir": ["..."]}
    ],
    "postes_cibles_moyen_terme": [
        {"poste": "...", "horizon": "...", "prerequis": ["..."]}
    ],
    "plan_action": [
        {"action": "...", "delai": "...", "objectif": "..."}
    ],
    "conseil_personnalise": "..."
}
PROMPT;

        $userPrompt = sprintf(
            "Profil:\n%s\n\nHistorique des postes:\n%s",
            json_encode($profil, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            json_encode($historiquePostes->map(fn($h) => [
                'poste' => $h->poste?->nom,
                'date' => $h->date_changement,
                'motif' => $h->motif,
            ])->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 1500,
                'response_format' => ['type' => 'json_object'],
            ]);

            if (!$response->successful()) {
                throw new \Exception('Erreur API');
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '{}';

            return [
                'success' => true,
                'employe' => [
                    'id' => $employe->id,
                    'nom' => $employe->nom . ' ' . $employe->prenom,
                ],
                'plan_carriere' => json_decode($content, true),
            ];
        } catch (\Exception $e) {
            Log::error('Career plan error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
