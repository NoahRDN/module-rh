<?php

namespace App\Services;

use App\Models\Employe;
use App\Models\User;
use App\Models\DemandeConge;
use App\Models\SoldeConge;
use App\Models\Paie;
use App\Models\Pointage;
use App\Models\Contrat;
use App\Models\Formation;
use App\Models\TypeConge;
use App\Models\JourFerie;
use App\Models\WorktimeSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ChatbotService
{
    private string $apiKey;
    private string $apiUrl;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
        $this->apiUrl = config('services.gemini.api_url', 'https://generativelanguage.googleapis.com/v1beta/models/');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
    }

    /**
     * Traiter une question de l'utilisateur
     */
    public function processQuestion(string $question, User $user): array
    {
        try {
            // Vérifier la clé API
            if (empty($this->apiKey)) {
                return [
                    'success' => false,
                    'response' => 'Le chatbot n\'est pas configuré. Veuillez contacter l\'administrateur.',
                    'error' => 'API_KEY non configurée dans .env',
                ];
            }

            // Récupérer le contexte de l'employé
            $context = $this->buildUserContext($user);
            
            // Détecter l'intention de la question
            $intent = $this->detectIntent($question);
            
            // Enrichir le contexte selon l'intention
            $enrichedContext = $this->enrichContextByIntent($intent, $user, $context);
            
            // Construire le prompt système
            $systemPrompt = $this->buildSystemPrompt($enrichedContext);
            
            // Appeler l'API
            $response = $this->callAI($systemPrompt, $question);
            
            return [
                'success' => true,
                'response' => $response,
                'intent' => $intent,
                'context_used' => array_keys($enrichedContext),
            ];
        } catch (\Exception $e) {
            Log::error('Chatbot error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'question' => $question,
                'user_id' => $user->id,
            ]);
            return [
                'success' => false,
                'response' => $this->getFallbackResponse($question),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Construire le contexte de l'utilisateur
     */
    private function buildUserContext(User $user): array
    {
        $employe = $user->employe;
        
        if (!$employe) {
            return [
                'role' => $user->role,
                'nom' => $user->name,
            ];
        }

        return [
            'role' => $user->role,
            'nom' => $employe->nom . ' ' . $employe->prenom,
            'matricule' => $employe->matricule,
            'poste' => $employe->poste?->nom ?? 'Non défini',
            'departement' => $employe->departement?->nom ?? 'Non défini',
            'date_embauche' => $employe->date_embauche?->format('d/m/Y'),
            'anciennete' => $employe->date_embauche 
                ? Carbon::parse($employe->date_embauche)->diffForHumans(now(), true) 
                : 'Non défini',
        ];
    }

    /**
     * Détecter l'intention de la question
     */
    private function detectIntent(string $question): string
    {
        $question = strtolower($question);
        
        $intents = [
            'conge' => ['congé', 'conge', 'vacances', 'absence', 'repos', 'solde', 'jours restants'],
            'paie' => ['paie', 'salaire', 'bulletin', 'rémunération', 'fiche de paie', 'net', 'brut'],
            'pointage' => ['pointage', 'présence', 'heure', 'arrivée', 'départ', 'retard'],
            'contrat' => ['contrat', 'cdd', 'cdi', 'embauche', 'période essai', 'fin de contrat'],
            'formation' => ['formation', 'compétence', 'apprentissage', 'certificat', 'stage'],
            'procedure' => ['procédure', 'comment', 'demander', 'faire', 'étapes'],
            'calendrier' => ['férié', 'fête', 'calendrier', 'jour off', 'weekend'],
            'horaire' => ['horaire', 'heure de travail', 'temps de travail', 'pause'],
            'evaluation' => ['évaluation', 'performance', 'objectif', 'entretien'],
            'document' => ['document', 'attestation', 'certificat', 'fiche'],
        ];

        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($question, $keyword)) {
                    return $intent;
                }
            }
        }

        return 'general';
    }

    /**
     * Enrichir le contexte selon l'intention détectée
     */
    private function enrichContextByIntent(string $intent, User $user, array $baseContext): array
    {
        $employe = $user->employe;
        
        if (!$employe) {
            return $baseContext;
        }

        switch ($intent) {
            case 'conge':
                return array_merge($baseContext, $this->getCongeContext($employe));
            case 'paie':
                return array_merge($baseContext, $this->getPaieContext($employe));
            case 'pointage':
                return array_merge($baseContext, $this->getPointageContext($employe));
            case 'contrat':
                return array_merge($baseContext, $this->getContratContext($employe));
            case 'formation':
                return array_merge($baseContext, $this->getFormationContext($employe));
            case 'calendrier':
                return array_merge($baseContext, $this->getCalendrierContext());
            case 'horaire':
                return array_merge($baseContext, $this->getHoraireContext());
            default:
                return $baseContext;
        }
    }

    /**
     * Contexte des congés
     */
    private function getCongeContext(Employe $employe): array
    {
        $soldes = SoldeConge::where('employe_id', $employe->id)
            ->with('typeConge')
            ->get();

        $demandesRecentes = DemandeConge::where('employe_id', $employe->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $typesConges = TypeConge::where('actif', true)->get(['nom', 'description']);

        return [
            'soldes_conges' => $soldes->map(fn($s) => [
                'type' => $s->typeConge?->nom ?? 'Inconnu',
                'acquis' => $s->jours_acquis,
                'pris' => $s->jours_pris,
                'solde' => $s->solde_actuel,
            ])->toArray(),
            'demandes_recentes' => $demandesRecentes->map(fn($d) => [
                'type' => $d->typeConge?->nom ?? 'Inconnu',
                'debut' => $d->date_debut->format('d/m/Y'),
                'fin' => $d->date_fin->format('d/m/Y'),
                'jours' => $d->jours_demandes,
                'statut' => $d->statut,
            ])->toArray(),
            'types_conges_disponibles' => $typesConges->toArray(),
        ];
    }

    /**
     * Contexte de la paie
     */
    private function getPaieContext(Employe $employe): array
    {
        $dernieresPaies = Paie::where('employe_id', $employe->id)
            ->orderBy('mois', 'desc')
            ->limit(3)
            ->get();

        $contratActuel = Contrat::where('employe_id', $employe->id)
            ->where('statut', 'actif')
            ->first();

        return [
            'salaire_base' => $contratActuel?->salaire_base ?? 'Non défini',
            'dernieres_paies' => $dernieresPaies->map(fn($p) => [
                'mois' => $p->mois,
                'brut' => number_format($p->total_brut, 0, ',', ' ') . ' Ar',
                'net' => number_format($p->net_a_payer, 0, ',', ' ') . ' Ar',
                'heures_sup' => $p->heures_supplementaires,
            ])->toArray(),
        ];
    }

    /**
     * Contexte du pointage
     */
    private function getPointageContext(Employe $employe): array
    {
        $pointagesRecents = Pointage::where('employe_id', $employe->id)
            ->orderBy('pointe_a', 'desc')
            ->limit(10)
            ->get();

        $pointageAujourdhui = Pointage::where('employe_id', $employe->id)
            ->whereDate('pointe_a', today())
            ->get();

        return [
            'pointages_aujourd_hui' => $pointageAujourdhui->map(fn($p) => [
                'type' => $p->type,
                'heure' => Carbon::parse($p->pointe_a)->format('H:i'),
            ])->toArray(),
            'pointages_recents' => $pointagesRecents->map(fn($p) => [
                'date' => Carbon::parse($p->pointe_a)->format('d/m/Y'),
                'type' => $p->type,
                'heure' => Carbon::parse($p->pointe_a)->format('H:i'),
            ])->toArray(),
        ];
    }

    /**
     * Contexte du contrat
     */
    private function getContratContext(Employe $employe): array
    {
        $contrat = Contrat::where('employe_id', $employe->id)
            ->where('statut', 'actif')
            ->first();

        if (!$contrat) {
            return ['contrat' => 'Aucun contrat actif'];
        }

        return [
            'contrat' => [
                'type' => $contrat->type_contrat,
                'date_debut' => $contrat->date_debut->format('d/m/Y'),
                'date_fin' => $contrat->date_fin?->format('d/m/Y') ?? 'Indéterminée',
                'salaire_base' => number_format($contrat->salaire_base, 0, ',', ' ') . ' Ar',
                'periode_essai_fin' => $contrat->periode_essai_fin?->format('d/m/Y'),
                'renouvelable' => $contrat->renouvelable ? 'Oui' : 'Non',
            ],
        ];
    }

    /**
     * Contexte des formations
     */
    private function getFormationContext(Employe $employe): array
    {
        $formationsEnCours = $employe->formationsEnCours()->get();
        $formationsTerminees = $employe->formationsTerminees()->limit(5)->get();
        $competences = $employe->competences()->get();

        return [
            'formations_en_cours' => $formationsEnCours->map(fn($f) => [
                'nom' => $f->nom,
                'date_debut' => $f->pivot->date_debut,
            ])->toArray(),
            'formations_terminees' => $formationsTerminees->map(fn($f) => [
                'nom' => $f->nom,
                'certificat' => $f->pivot->certificat_obtenu ? 'Oui' : 'Non',
            ])->toArray(),
            'competences' => $competences->map(fn($c) => [
                'nom' => $c->nom,
                'niveau' => $c->pivot->niveau . '/5',
            ])->toArray(),
        ];
    }

    /**
     * Contexte du calendrier (jours fériés)
     */
    private function getCalendrierContext(): array
    {
        $joursFeries = JourFerie::whereYear('date', now()->year)
            ->orderBy('date')
            ->get();

        $prochainsFeries = JourFerie::where('date', '>=', now())
            ->orderBy('date')
            ->limit(5)
            ->get();

        return [
            'jours_feries_annee' => $joursFeries->map(fn($j) => [
                'nom' => $j->nom,
                'date' => Carbon::parse($j->date)->format('d/m/Y'),
            ])->toArray(),
            'prochains_feries' => $prochainsFeries->map(fn($j) => [
                'nom' => $j->nom,
                'date' => Carbon::parse($j->date)->format('d/m/Y'),
                'dans' => Carbon::parse($j->date)->diffForHumans(),
            ])->toArray(),
        ];
    }

    /**
     * Contexte des horaires
     */
    private function getHoraireContext(): array
    {
        $worktime = WorktimeSetting::first();

        if (!$worktime) {
            return [
                'horaires' => 'Non configurés',
            ];
        }

        return [
            'horaires' => [
                'debut_journee' => $worktime->start_time,
                'fin_journee' => $worktime->end_time,
                'pause_debut' => $worktime->break_start,
                'pause_fin' => $worktime->break_end,
                'heures_semaine' => $worktime->weekly_hours,
                'jours_travailles' => $worktime->working_days,
            ],
        ];
    }

    /**
     * Construire le prompt système
     */
    private function buildSystemPrompt(array $context): string
    {
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Tu es un assistant RH virtuel intelligent pour une entreprise. Tu dois répondre aux questions des employés de manière professionnelle, claire et concise en français.

RÈGLES IMPORTANTES:
1. Tu as accès aux données personnelles de l'employé qui te pose la question (voir contexte ci-dessous)
2. Réponds toujours en français
3. Sois précis et donne des informations chiffrées quand tu les as
4. Si tu n'as pas l'information demandée, indique-le clairement
5. Pour les procédures, explique les étapes de manière claire
6. Sois empathique et professionnel
7. Ne divulgue jamais d'informations sensibles sur d'autres employés
8. Si la question nécessite une action humaine, oriente vers le service RH

CONTEXTE DE L'EMPLOYÉ:
{$contextJson}

INFORMATIONS GÉNÉRALES SUR L'ENTREPRISE:
- Les demandes de congés doivent être soumises via le portail self-service
- Les bulletins de paie sont disponibles dans la section "Mes bulletins"
- Pour les attestations, il faut faire une demande RH
- Les pointages se font à l'entrée et à la sortie

Réponds de manière naturelle et utile à la question de l'employé.
PROMPT;
    }

    /**
     * Appeler l'API Gemini
     */
    private function callAI(string $systemPrompt, string $question): string
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Clé API non configurée');
        }

        // Combiner le prompt système et la question pour Gemini
        $combinedPrompt = $systemPrompt . "\n\nQuestion de l'employé: " . $question;

        // URL complète pour Gemini (sans la clé dans l'URL)
        $url = $this->apiUrl . $this->model . ':generateContent';

        Log::info('Calling Gemini API', [
            'url' => $url,
            'prompt_length' => strlen($combinedPrompt),
        ]);

        $response = Http::timeout(30)->withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $this->apiKey,
        ])->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $combinedPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1000,
            ],
        ]);

        if (!$response->successful()) {
            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $url,
            ]);
            throw new \Exception('Erreur API Gemini: ' . $response->status() . ' - ' . $response->body());
        }

        $data = $response->json();
        
        Log::info('Gemini API response received', [
            'has_candidates' => isset($data['candidates']),
            'data_keys' => array_keys($data),
        ]);
        
        // Extraire la réponse de Gemini
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }
        
        Log::error('Gemini response format unexpected', ['data' => $data]);
        return 'Je n\'ai pas pu générer de réponse.';
    }

    /**
     * Réponse de secours si l'API échoue
     */
    private function getFallbackResponse(string $question): string
    {
        $question = strtolower($question);
        
        $responses = [
            'congé' => "Pour consulter votre solde de congés, rendez-vous dans la section 'Mes congés' du portail self-service. Pour faire une demande de congé, cliquez sur 'Nouvelle demande' et remplissez le formulaire.",
            'paie' => "Vos bulletins de paie sont disponibles dans la section 'Mes bulletins' du portail self-service. Pour toute question sur votre rémunération, contactez le service RH.",
            'pointage' => "Vous pouvez consulter vos pointages dans la section 'Mes pointages'. N'oubliez pas de pointer à l'arrivée et au départ chaque jour.",
            'contrat' => "Les informations sur votre contrat sont disponibles dans votre profil. Pour toute modification ou question, contactez le service RH.",
            'formation' => "Consultez le catalogue des formations dans la section 'Formations'. Vous pouvez vous inscrire aux formations disponibles ou demander une formation spécifique via une demande RH.",
        ];

        foreach ($responses as $keyword => $response) {
            if (str_contains($question, $keyword)) {
                return $response;
            }
        }

        return "Je suis désolé, je ne peux pas répondre à votre question pour le moment. Veuillez contacter le service RH pour plus d'informations.";
    }

    /**
     * Obtenir des suggestions de questions
     */
    public function getSuggestions(User $user): array
    {
        $suggestions = [
            'Quel est mon solde de congés ?',
            'Quand est mon prochain jour férié ?',
            'Comment demander une attestation de travail ?',
            'Quel est mon salaire net ?',
            'Quelles formations sont disponibles ?',
        ];

        if ($user->employe) {
            $solde = SoldeConge::where('employe_id', $user->employe->id)->first();
            if ($solde && $solde->solde_actuel < 5) {
                array_unshift($suggestions, 'Comment fonctionne l\'acquisition de congés ?');
            }
        }

        return $suggestions;
    }
}
