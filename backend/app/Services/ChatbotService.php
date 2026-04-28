<?php

namespace App\Services;

use App\Models\Employe;
use App\Models\User;
use App\Models\DemandeConge;
use App\Models\SoldeConge;
use App\Models\Paie;
use App\Models\PaieParametre;
use App\Models\Pointage;
use App\Models\Contrat;
use App\Models\Formation;
use App\Models\TypeConge;
use App\Models\JourFerie;
use App\Models\WorktimeSetting;
use App\Models\Departement;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Caisse;
use App\Models\CaisseMouvement;
use App\Models\CalendrierEvenement;

class ChatbotService
{
    private AIProviderService $ai;

    public function __construct(AIProviderService $ai)
    {
        $this->ai = $ai;
    }

    /**
     * Traiter une question de l'utilisateur
     */
    public function processQuestion(string $question, User $user, $conversation = null): array
    {
        try {
            Log::info('ChatbotService: processing question', ['user_id' => $user->id ?? null, 'question_snippet' => mb_substr($question, 0, 200)]);

            // Vérifier la config du provider IA actif (OpenAI/Gemini)
            $provider = (string) config('services.ai_provider', 'openai');
            if ($provider === 'gemini') {
                $geminiKey = (string) (config('services.gemini.api_key') ?? '');
                $geminiUrl = (string) (config('services.gemini.api_url') ?? '');

                if ($geminiKey === '' || $geminiUrl === '') {
                    return [
                        'success' => false,
                        'response' => 'Le chatbot n\'est pas configuré. Veuillez contacter l\'administrateur.',
                        'error' => 'GEMINI_API_KEY ou GEMINI_API_URL non configuré(e) dans .env',
                    ];
                }
            } else {
                $openaiKey = (string) (config('services.openai.api_key') ?? '');
                $openaiUrl = (string) (config('services.openai.api_url') ?? '');

                if ($openaiKey === '' || $openaiUrl === '') {
                    return [
                        'success' => false,
                        'response' => 'Le chatbot n\'est pas configuré. Veuillez contacter l\'administrateur.',
                        'error' => 'OPENAI_API_KEY ou OPENAI_API_URL non configuré(e) dans .env',
                    ];
                }
            }

            // Vérifier si c'est une question sur l'effectif (traitement spécial)
            if ($this->isEffectifQuestion($question)) {
                return $this->handleEffectifQuestion($user);
            }

            // Vérifier si c'est une question sur la répartition/statistiques (traitement spécial)
            if ($this->isRepartitionQuestion($question)) {
                return $this->handleRepartitionQuestion($user, $question);
            }

            // Vérifier si c'est une question sur la caisse
            if ($this->isCaisseQuestion($question)) {
                return $this->handleCaisseQuestion($user, $question);
            }

            // Vérifier si c'est une demande de calcul de cotisations pour un salaire donné
            if ($this->isPaieCalculQuestion($question)) {
                return $this->handlePaieCalculQuestion($user, $question);
            }

            // Vérifier si c'est une question sur les taux / cotisations (CNAPS, OSTIE, etc.)
            if ($this->isPaieTauxQuestion($question)) {
                return $this->handlePaieTauxQuestion($user, $question);
            }

            // Vérifier si c'est une demande d'état des paies
            if ($this->isPaieEtatQuestion($question)) {
                return $this->handlePaieEtatQuestion($user, $question);
            }

            // Vérifier si c'est une demande de statistiques RH spécifiques
            if ($this->isStatsRHQuestion($question)) {
                return $this->handleStatsRHQuestion($user, $question);
            }

            // Vérifier si c'est une question sur le calendrier / congés
            if ($this->isCalendrierCongesQuestion($question)) {
                return $this->handleCalendrierCongesQuestion($user, $question);
            }

            // Récupérer le contexte de l'employé
            $context = $this->buildUserContext($user);

            // Inclure l'historique de conversation (5 derniers messages) si fourni
            if (!empty($conversation) && is_array($conversation)) {
                // Normalize recent messages structure
                $context['recent_messages'] = array_map(function ($m) {
                    // Accept objects or arrays with keys: isUser, text, role, message, timestamp
                    $sender = null;
                    if (is_array($m)) {
                        if (isset($m['isUser'])) $sender = $m['isUser'] ? 'user' : 'assistant';
                        if (isset($m['role'])) $sender = $m['role'];
                        $text = $m['text'] ?? ($m['message'] ?? '');
                        $time = $m['timestamp'] ?? null;
                    } else if (is_object($m)) {
                        $sender = isset($m->isUser) ? ($m->isUser ? 'user' : 'assistant') : ($m->role ?? null);
                        $text = $m->text ?? ($m->message ?? '');
                        $time = $m->timestamp ?? null;
                    } else {
                        $sender = 'user';
                        $text = (string) $m;
                        $time = null;
                    }

                    return [
                        'role' => $sender ?? 'user',
                        'text' => is_string($text) ? $text : json_encode($text, JSON_UNESCAPED_UNICODE),
                        'timestamp' => $time,
                    ];
                }, array_values($conversation));
            }
            
            // Détecter l'intention de la question
            $intent = $this->detectIntent($question);
            
            // Enrichir le contexte selon l'intention
            $enrichedContext = $this->enrichContextByIntent($intent, $user, $context);

            // Forcer l'inclusion du contexte calendrier (jours fériés + événements) afin
            // que l'IA puisse répondre aux questions sur les événements à venir.
            try {
                $calendarContext = $this->getCalendrierContext();
                if (is_array($calendarContext)) {
                    foreach ($calendarContext as $k => $v) {
                        if (!array_key_exists($k, $enrichedContext)) {
                            $enrichedContext[$k] = $v;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('ChatbotService: unable to include calendrier context', ['error' => $e->getMessage()]);
            }
            
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

        // Normaliser (supprimer accents pour la détection basique)
        $normalized = @iconv('UTF-8', 'ASCII//TRANSLIT', $question) ?: $question;
        
        $intents = [
            'conge' => ['congé', 'conge', 'vacances', 'absence', 'repos', 'solde', 'jours restants'],
            'paie' => ['paie', 'salaire', 'bulletin', 'rémunération', 'fiche de paie', 'net', 'brut', 'cnaps', 'cotisation', 'cotisations', 'charge', 'charges', 'pourcentage', 'taux', 'salarial', 'patronal'],
            'pointage' => ['pointage', 'présence', 'heure', 'arrivée', 'départ', 'retard'],
            'contrat' => ['contrat', 'cdd', 'cdi', 'embauche', 'période essai', 'fin de contrat'],
            'formation' => ['formation', 'compétence', 'apprentissage', 'certificat', 'stage'],
            'procedure' => ['procédure', 'comment', 'demander', 'faire', 'étapes'],
            'calendrier' => ['férié', 'fête', 'calendrier', 'jour off', 'weekend'],
            'horaire' => ['horaire', 'heure de travail', 'temps de travail', 'pause'],
            'evaluation' => ['évaluation', 'performance', 'objectif', 'entretien'],
            'document' => ['document', 'attestation', 'certificat', 'fiche'],
            'effectif' => ['combien', 'effectif', 'employé', 'employés', 'personnel', 'équipe', 'nombre'],
        ];

        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($question, $keyword) || str_contains($normalized, $keyword)) {
                    Log::info('ChatbotService: intent keyword matched', ['intent' => $intent, 'keyword' => $keyword, 'question' => mb_substr($question, 0, 300)]);
                    return $intent;
                }
            }
        }

        // Si l'utilisateur mentionne un mois, considérer comme question de calendrier
        $months = ['janvier','fevrier','mars','avril','mai','juin','juillet','aout','septembre','octobre','novembre','decembre'];
        foreach ($months as $m) {
            if (str_contains($question, $m) || str_contains($normalized, $m)) {
                Log::info('ChatbotService: month detected, classifying as calendrier', ['month' => $m, 'question' => mb_substr($question, 0, 200)]);
                return 'calendrier';
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

        // Prochains événements calendrier (événements RH, réunions, autres)
        $start = now();
        $end = now()->addDays(90);
        $evenements = CalendrierEvenement::where(function ($q) use ($start, $end) {
                $q->whereBetween('date_debut', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                  ->orWhereBetween('date_fin', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
            })
            ->orderBy('date_debut')
            ->limit(20)
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
            'prochains_evenements' => $evenements->map(fn($e) => [
                'type' => $e->type,
                'titre' => $e->description ?? ($e->meta['nom'] ?? 'Événement'),
                'date_debut' => $e->date_debut?->format('Y-m-d'),
                'date_fin' => $e->date_fin?->format('Y-m-d'),
                'description' => $e->description,
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
    9. Assure-toi que ta réponse se termine par une phrase complète : ne laisse jamais de mot ou de phrase inachevée. Si la réponse est longue, priorise la complétude des phrases plutôt que des exemples supplémentaires.
10. Si le contexte fourni inclut des clés `prochains_feries` ou `prochains_evenements`, UTILISE ces informations pour répondre aux questions sur le calendrier et liste les événements pertinents. Ne prétends pas ne pas avoir accès au calendrier si ces données sont présentes.

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
        // Première tentative avec plus de tokens pour éviter les coupures
        $response = $this->ai->chatText($systemPrompt, $question, [
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ]);

        // Normaliser
        $trimmed = trim((string) $response);
        $words = preg_split('/\s+/', $trimmed);
        $lastWord = is_array($words) && count($words) ? end($words) : '';

        // Détecter un éventuel tronquage: fin par mot très court sans ponctuation
        $maybeTruncated = false;
        if ($lastWord !== null && is_string($lastWord) && strlen($lastWord) > 0 && strlen($lastWord) <= 3) {
            // pas uniquement une ponctuation finale
            if (!str_ends_with($trimmed, '.') && !str_ends_with($trimmed, '!') && !str_ends_with($trimmed, '?')) {
                $maybeTruncated = true;
            }
        }

        if ($maybeTruncated) {
            Log::warning('ChatbotService: possible truncated response detected', [
                'question_snippet' => mb_substr($question, 0, 200),
                'response_preview' => mb_substr($trimmed, 0, 200),
            ]);

            // Ne relancer qu'une seule fois pour compléter la phrase
            try {
                $followUp = "Complète la phrase précédente si elle s'est arrêtée prématurément. Phrase incomplète: \"{$trimmed}\"\nDonne la suite et termine par une phrase complète en français.";
                $continued = $this->ai->chatText($systemPrompt, $followUp, [
                    'temperature' => 0.6,
                    'max_tokens' => 400,
                ]);

                $continuedTrim = trim((string) $continued);

                Log::info('ChatbotService: continuation attempt result', [
                    'continued_preview' => mb_substr($continuedTrim, 0, 300),
                ]);

                // Si la suite paraît plus longue ou différente, l'utiliser
                if (strlen($continuedTrim) > strlen($trimmed)) {
                    return $continuedTrim;
                }
            } catch (\Exception $e) {
                Log::error('ChatbotService: continuation failed', ['error' => $e->getMessage()]);
                // Ne pas faire échouer la requête principale pour un échec de complétion
                // On retourne la réponse initiale
                return $trimmed;
            }
        }

        return $trimmed;
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
            'effectif' => "Pour connaître l'effectif de l'entreprise, veuillez contacter le service RH.",
        ];

        foreach ($responses as $keyword => $response) {
            if (str_contains($question, $keyword)) {
                return $response;
            }
        }

        return "Je suis désolé, je ne peux pas répondre à votre question pour le moment. Veuillez contacter le service RH pour plus d'informations.";
    }

    /**
     * Vérifier si la question concerne l'effectif total
     */
    private function isEffectifQuestion(string $question): bool
    {
        $q = strtolower($question);
        $normalized = @iconv('UTF-8', 'ASCII//TRANSLIT', $q) ?: $q;

        // Mots indiquant clairement une question sur l'effectif
        $indicators = ['effectif', 'employé', 'employes', 'employés', 'personnel', 'équipe', 'nombre', 'headcount'];
        foreach ($indicators as $kw) {
            if (str_contains($q, $kw) || str_contains($normalized, $kw)) {
                Log::info('ChatbotService: isEffectifQuestion matched indicator', ['keyword' => $kw, 'question' => mb_substr($question, 0, 200)]);
                return true;
            }
        }

        // Si la question contient "combien", ne pas considérer comme effectif
        // à moins qu'un autre indicateur ne soit présent (éviter faux positifs)
        if (str_contains($q, 'combien') || str_contains($normalized, 'combien')) {
            // vérifier présence d'un indicateur supplémentaire
            foreach ($indicators as $kw) {
                if (str_contains($q, $kw) || str_contains($normalized, $kw)) {
                    Log::info('ChatbotService: isEffectifQuestion matched with "combien" plus indicator', ['keyword' => $kw, 'question' => mb_substr($question, 0, 200)]);
                    return true;
                }
            }
            return false;
        }

        return false;
    }

    /**
     * Traiter une question sur l'effectif
     */
    private function handleEffectifQuestion(User $user): array
    {
        // Vérifier les permissions - seuls admin/RH peuvent voir les stats détaillées
        if (!$user->isAdmin() && !$user->isRH()) {
            return [
                'success' => true,
                'response' => 'En tant qu\'assistant RH, je ne peux fournir des informations sur l\'effectif total de l\'entreprise qu\'aux membres du service RH. Veuillez contacter votre responsable RH pour ces informations.',
                'intent' => 'effectif',
            ];
        }

        // Calculer les effectifs
        $now = now()->toDateString();
        $totalEmployes = Employe::count();

        $employesActifs = Employe::whereHas('contrats', function ($q) use ($now) {
            $q->whereDate('date_debut', '<=', $now)
              ->where(function ($q2) use ($now) {
                  $q2->whereNull('date_fin')->orWhereDate('date_fin', '>=', $now);
              });
        })->count();

        $response = "L'entreprise compte actuellement {$totalEmployes} employé(s) enregistré(s), dont {$employesActifs} actif(s).";

        return [
            'success' => true,
            'response' => $response,
            'intent' => 'effectif',
            'data' => [
                'total' => $totalEmployes,
                'actifs' => $employesActifs,
            ],
        ];
    }

    /**
     * Vérifier si la question concerne la répartition/statistiques
     */
    private function isRepartitionQuestion(string $question): bool
    {
        $question = strtolower($question);
        $repartitionKeywords = ['département', 'departement', 'service', 'plus', 'moins', 'répartition', 'réparti', 'par'];

        foreach ($repartitionKeywords as $keyword) {
            if (str_contains($question, $keyword)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Traiter une question sur la répartition/statistiques
     */
    private function handleRepartitionQuestion(User $user, string $question): array
    {
        // Vérifier les permissions - seuls admin/RH peuvent voir les stats détaillées
        if (!$user->isAdmin() && !$user->isRH()) {
            return [
                'success' => true,
                'response' => 'En tant qu\'assistant RH, je ne peux fournir des informations sur la répartition des employés qu\'aux membres du service RH. Veuillez contacter votre responsable RH pour ces informations.',
                'intent' => 'repartition',
            ];
        }

        $question = strtolower($question);

        // Détecter le type de répartition demandé
        if (str_contains($question, 'département') || str_contains($question, 'departement')) {
            return $this->handleDepartementRepartition($question);
        }

        // Par défaut, retourner une réponse générique
        return [
            'success' => true,
            'response' => 'Je peux vous fournir des informations sur la répartition des employés par département. Pour d\'autres types de statistiques, veuillez consulter le tableau de bord RH.',
            'intent' => 'repartition',
        ];
    }

    /**
     * Gérer la répartition par département
     */
    private function handleDepartementRepartition(string $question): array
    {
        $question = strtolower($question);
        $now = now()->toDateString();

        // Récupérer la répartition par département (même logique que DashboardController)
        $repartition = Departement::select('departements.id', 'departements.nom')
            ->whereExists(function ($q) use ($now) {
                $q->select(DB::raw(1))
                    ->from('employes')
                    ->whereColumn('employes.departement_id', 'departements.id')
                    ->whereExists(function ($q2) use ($now) {
                        $q2->select(DB::raw(1))
                            ->from('contrats')
                            ->whereColumn('contrats.employe_id', 'employes.id')
                            ->whereDate('date_debut', '<=', $now)
                            ->where(function ($q3) use ($now) {
                                $q3->whereNull('date_fin')->orWhereDate('date_fin', '>=', $now);
                            });
                    });
            })
            ->withCount(['employes as employes_actifs_count' => function ($q) use ($now) {
                $q->whereHas('contrats', function ($q2) use ($now) {
                    $q2->whereDate('date_debut', '<=', $now)
                        ->where(function ($q3) use ($now) {
                            $q3->whereNull('date_fin')->orWhereDate('date_fin', '>=', $now);
                        });
                });
            }])
            ->get();

        if ($repartition->isEmpty()) {
            return [
                'success' => true,
                'response' => 'Aucun département avec des employés actifs trouvé.',
                'intent' => 'repartition',
            ];
        }

        // Trier par nombre d'employés décroissant et croissant
        $sortedDesc = $repartition->sortByDesc('employes_actifs_count');
        $sortedAsc = $repartition->sortBy('employes_actifs_count');

        // Détecter si l'utilisateur demande une liste complète
        $demandeComplete = $this->isDemandeRepartitionComplete($question);

        // Détecter demande de "moins" (le département avec le moins d'employés)
        $demandeMoins = false;
        $moinsKeywords = ['moins', 'le moins', 'moins d', 'le moins d'];
        foreach ($moinsKeywords as $kw) {
            if (str_contains($question, $kw)) {
                $demandeMoins = true;
                break;
            }
        }

        if ($demandeComplete) {
            // Réponse avec la liste complète (triée décroissant pour lisibilité)
            $response = "Voici la répartition des employés actifs par département :\n\n";
            foreach ($sortedDesc as $dept) {
                $response .= "- **{$dept->nom}** : {$dept->employes_actifs_count} employé(s)\n";
            }
        } else if ($demandeMoins) {
            // Réponse avec le département qui a le moins d'employés actifs
            $bottom = $sortedAsc->first();
            $response = "Le département qui compte le moins d'employés actifs est **{$bottom->nom}** avec {$bottom->employes_actifs_count} employé(s).";
        } else {
            // Réponse par défaut : top département
            $topDepartement = $sortedDesc->first();
            $response = "Le département qui compte le plus d'employés actifs est **{$topDepartement->nom}** avec {$topDepartement->employes_actifs_count} employé(s).";

            // Ajouter les autres départements s'il y en a peu
            if ($sortedDesc->count() <= 3) {
                $response .= "\n\nRépartition complète :\n";
                foreach ($sortedDesc as $dept) {
                    $response .= "- {$dept->nom} : {$dept->employes_actifs_count} employé(s)\n";
                }
            }
        }

        return [
            'success' => true,
            'response' => $response,
            'intent' => 'repartition',
            'data' => [
                'repartition_complete' => $demandeComplete,
                'repartition' => $sortedDesc->values()->map(fn($d) => [
                    'departement' => $d->nom,
                    'employes_actifs' => $d->employes_actifs_count,
                ])->toArray(),
            ],
        ];
    }

    /**
     * Détecter si la question concerne la caisse
     */
    private function isCaisseQuestion(string $question): bool
    {
        $q = strtolower($question);
        $keywords = ['caisse', 'solde caisse', 'état caisse', 'etat caisse', 'état de la caisse', 'etat de la caisse'];

        foreach ($keywords as $kw) {
            if (str_contains($q, $kw)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gérer les demandes liées à la caisse
     */
    private function handleCaisseQuestion(User $user, string $question): array
    {
        // Si l'utilisateur n'a pas la permission, proposer des alternatives
        if (!$user->isAdmin() && !$user->isRH()) {
            return [
                'success' => true,
                'response' => 'Je n\'ai pas accès à l\'état complet des caisses. Voici quelques actions que je peux vous proposer à la place :',
                'intent' => 'caisse',
                'suggestions' => [
                    'Afficher les derniers mouvements de ma caisse',
                    'Voir les caisses actives',
                    'Contacter le service RH pour l\'état complet',
                ],
            ];
        }

        // Utilisateur admin/RH : retourner un résumé simple (texte) au lieu d'une redirection
        $caisses = Caisse::with(['mouvements' => function ($q) {
            $q->orderBy('created_at', 'desc')->limit(5);
        }])->get();

        if ($caisses->isEmpty()) {
            return [
                'success' => true,
                'response' => 'Aucune caisse trouvée.',
                'intent' => 'caisse',
            ];
        }

        $response = "État des caisses :\n\n";
        $data = [];
        foreach ($caisses as $c) {
            $solde = number_format($c->solde, 0, ',', ' ') . ' Ar';
            $response .= "- {$c->nom} : {$solde}\n";

            $mouvements = $c->mouvements->map(fn($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'categorie' => $m->categorie,
                'montant' => number_format($m->montant, 0, ',', ' ') . ' Ar',
                'date' => $m->created_at?->format('d/m/Y H:i') ?? null,
                'description' => $m->description,
            ])->toArray();

            $data[] = [
                'caisse' => $c->nom,
                'solde' => $c->solde,
                'mouvements_recents' => $mouvements,
            ];
        }

        return [
            'success' => true,
            'response' => $response,
            'intent' => 'caisse',
            'data' => [
                'caisses' => $data,
            ],
        ];
    }

    /**
     * Détecter si la question concerne l'état des paies
     */
    private function isPaieEtatQuestion(string $question): bool
    {
        $q = strtolower($question);
        $keywords = ['paie en retard', 'paies en retard', 'état des paies', 'etat des paies', 'statut des paies', 'paie', 'paye en retard', 'paye en retard'];

        foreach ($keywords as $kw) {
            if (str_contains($q, $kw)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Détecter si la question demande un calcul de cotisations pour un salaire donné
     */
    private function isPaieCalculQuestion(string $question): bool
    {
        $q = strtolower($question);

        // Mots indiquant un calcul
        if (!preg_match('/calc|calcu|calcule|calcul|calculez|calculer/i', $q)) {
            return false;
        }

        // Vérifier la présence d'un terme lié aux cotisations ou d'un salaire
        if (preg_match('/cotis|cotisation|cotisations|contribut|contribution|cnaps|ostie/i', $q) || str_contains($q, 'salaire') || preg_match('/\d{3,}/', $q)) {
            Log::info('ChatbotService: isPaieCalculQuestion matched', ['question' => mb_substr($question, 0, 200)]);
            return true;
        }

        return false;
    }

    /**
     * Calculer les cotisations à partir d'un salaire brut en utilisant les paramètres configurés
     */
    private function handlePaieCalculQuestion(User $user, string $question): array
    {
        // Extraire le montant si fourni
        $found = null;
        if (preg_match('/([0-9]+(?:[ \.,][0-9]{3})*(?:[\.,][0-9]+)?)/', $question, $m)) {
            $found = $m[1];
        }

        if (!$found) {
            return [
                'success' => true,
                'response' => "Précisez le salaire brut pour effectuer le calcul (ex : 'Calcule les cotisations pour un salaire brut de 1 000 000 Ar').",
                'intent' => 'paie',
            ];
        }

        // Normaliser le montant (retirer espaces et séparateurs)
        $num = str_replace([' ', '\\.', ','], ['', '', '.'], $found);
        $salary = (int) round(floatval($num));

        try {
            $param = PaieParametre::first();
            if (!$param) {
                return [
                    'success' => true,
                    'response' => "Les paramètres de paie ne sont pas configurés dans le système. Impossible d'effectuer le calcul automatique.",
                    'intent' => 'paie',
                ];
            }

            $cnaps_emp_rate = $param->cnaps_taux_employeur ? floatval($param->cnaps_taux_employeur) : 0.0;
            $cnaps_sal_rate = $param->cnaps_taux_employe ? floatval($param->cnaps_taux_employe) : 0.0;
            $ostie_emp_rate = $param->ostie_taux_employeur ? floatval($param->ostie_taux_employeur) : 0.0;
            $ostie_sal_rate = $param->ostie_taux_employe ? floatval($param->ostie_taux_employe) : 0.0;

            $cnaps_emp_amt = (int) round($salary * $cnaps_emp_rate / 100);
            $cnaps_sal_amt = (int) round($salary * $cnaps_sal_rate / 100);
            $ostie_emp_amt = (int) round($salary * $ostie_emp_rate / 100);
            $ostie_sal_amt = (int) round($salary * $ostie_sal_rate / 100);

            $total_emp = $cnaps_emp_amt + $ostie_emp_amt;
            $total_sal = $cnaps_sal_amt + $ostie_sal_amt;
            $net_estime = $salary - $total_sal;

            $response = "Calcul indicatif des cotisations pour un salaire brut de " . number_format($salary, 0, ',', ' ') . " Ar :\n";
            $response .= "- CNAPS (part employeur @ {$cnaps_emp_rate}%): " . number_format($cnaps_emp_amt, 0, ',', ' ') . " Ar\n";
            $response .= "- CNAPS (part employé @ {$cnaps_sal_rate}%): " . number_format($cnaps_sal_amt, 0, ',', ' ') . " Ar\n";
            $response .= "- OSTIE (part employeur @ {$ostie_emp_rate}%): " . number_format($ostie_emp_amt, 0, ',', ' ') . " Ar\n";
            $response .= "- OSTIE (part employé @ {$ostie_sal_rate}%): " . number_format($ostie_sal_amt, 0, ',', ' ') . " Ar\n\n";
            $response .= "Total cotisations employeur : " . number_format($total_emp, 0, ',', ' ') . " Ar\n";
            $response .= "Total cotisations salarié : " . number_format($total_sal, 0, ',', ' ') . " Ar\n";
            $response .= "Salaire net estimé après cotisations salariales : " . number_format($net_estime, 0, ',', ' ') . " Ar\n\n";
            $response .= "Remarque : il s'agit d'un calcul indicatif basé sur les paramètres configurés dans le système. Pour un bulletin officiel, contactez le service paie/ RH.";

            return [
                'success' => true,
                'response' => $response,
                'intent' => 'paie',
                'data' => [
                    'salary' => $salary,
                    'cnaps_emp_rate' => $cnaps_emp_rate,
                    'cnaps_sal_rate' => $cnaps_sal_rate,
                    'ostie_emp_rate' => $ostie_emp_rate,
                    'ostie_sal_rate' => $ostie_sal_rate,
                    'totals' => [
                        'employeur' => $total_emp,
                        'salarie' => $total_sal,
                        'net_estime' => $net_estime,
                    ],
                ],
            ];
        } catch (\Exception $e) {
            Log::error('ChatbotService: failed to calculate contributions', ['error' => $e->getMessage()]);
            return [
                'success' => true,
                'response' => 'Une erreur est survenue lors du calcul des cotisations. Veuillez réessayer plus tard.',
                'intent' => 'paie',
            ];
        }
    }

    /**
     * Détecter si la question concerne les taux ou cotisations (CNAPS, OSTIE...)
     */
    private function isPaieTauxQuestion(string $question): bool
    {
        $q = strtolower($question);
        $normalized = @iconv('UTF-8', 'ASCII//TRANSLIT', $q) ?: $q;

        $keywords = ['taux', 'cotisation', 'cotisations', 'patronal', 'patronale', 'employeur', 'plafond', 'cnaps', 'ostie', 'taux patronal', 'taux employeur', 'plafond cnaps'];
        foreach ($keywords as $kw) {
            if (str_contains($q, $kw) || str_contains($normalized, $kw)) {
                // Exiger qu'il y ait une notion de salaire/part pour éviter les faux positifs
                if (str_contains($q, 'salaire') || str_contains($q, 'brut') || str_contains($q, 'part') || str_contains($q, 'employeur') || str_contains($q, 'patronal') || str_contains($q, 'cnaps') || str_contains($q, 'ostie')) {
                    Log::info('ChatbotService: isPaieTauxQuestion matched', ['keyword' => $kw, 'question' => mb_substr($question, 0, 200)]);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Répondre aux questions sur les taux de cotisation en utilisant les paramètres configurés
     */
    private function handlePaieTauxQuestion(User $user, string $question): array
    {
        try {
            $param = PaieParametre::first();
            if (!$param) {
                return [
                    'success' => true,
                    'response' => "Les paramètres de paie (CNAPS/OSTIE/taux) ne sont pas configurés dans le système. Veuillez contacter le service RH ou la comptabilité.",
                    'intent' => 'paie',
                ];
            }

            $parts = [];
            if ($param->cnaps_taux_employeur !== null) {
                $parts[] = "Taux CNAPS - part employeur : {$param->cnaps_taux_employeur}%";
            }
            if ($param->cnaps_taux_employe !== null) {
                $parts[] = "Taux CNAPS - part employé : {$param->cnaps_taux_employe}%";
            }
            if ($param->ostie_taux_employeur !== null) {
                $parts[] = "Taux OSTIE - part employeur : {$param->ostie_taux_employeur}%";
            }
            if ($param->ostie_taux_employe !== null) {
                $parts[] = "Taux OSTIE - part employé : {$param->ostie_taux_employe}%";
            }
            if ($param->cnaps_plafond !== null) {
                $parts[] = "Plafond CNAPS : " . number_format($param->cnaps_plafond, 0, ',', ' ') . ' Ar';
            }

            if (empty($parts)) {
                return [
                    'success' => true,
                    'response' => "Les paramètres de paie existent mais aucune valeur de taux n'a été configurée. Veuillez contacter le service RH.",
                    'intent' => 'paie',
                ];
            }

            $response = "Voici les paramètres de paie configurés dans le système :\n" . implode("\n", $parts) . "\n\nSi vous souhaitez un calcul pour un salaire brut donné, demandez par exemple : 'Calcule les cotisations patronales pour un salaire brut de 1 000 000 Ar'.";

            return [
                'success' => true,
                'response' => $response,
                'intent' => 'paie',
                'data' => ['paie_parametres' => $param->toArray()],
            ];
        } catch (\Exception $e) {
            Log::error('ChatbotService: failed to fetch paie parametres', ['error' => $e->getMessage()]);
            return [
                'success' => true,
                'response' => 'Impossible de récupérer les paramètres de paie actuellement. Veuillez contacter le service RH.',
                'intent' => 'paie',
            ];
        }
    }

    /**
     * Gérer l'état des paies
     */
    private function handlePaieEtatQuestion(User $user, string $question): array
    {
        if (!$user->isAdmin() && !$user->isRH()) {
            return [
                'success' => true,
                'response' => 'Seuls les membres du service RH ou les administrateurs peuvent consulter l\'état des paies.',
                'intent' => 'paie',
            ];
        }

        $total = Paie::count();
        $nonPayees = Paie::whereNull('paye_le')->count();
        $enAttenteValidation = Paie::whereNull('valide_le')->count();

        $lastNonPayees = Paie::whereNull('paye_le')
            ->with('employe')
            ->orderBy('mois', 'asc')
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'employe' => $p->employe?->nom . ' ' . $p->employe?->prenom,
                'mois' => $p->mois,
                'net' => number_format($p->net_a_payer, 0, ',', ' ') . ' Ar',
            ])->toArray();

        $response = "État des paies : total {$total}, non payées : {$nonPayees}, en attente de validation : {$enAttenteValidation}.";

        return [
            'success' => true,
            'response' => $response,
            'intent' => 'paie',
            'data' => [
                'total' => $total,
                'non_payees' => $nonPayees,
                'en_attente_validation' => $enAttenteValidation,
                'exemples_non_payees' => $lastNonPayees,
            ],
        ];
    }

    /**
     * Détecter si la question concerne des statistiques RH spécifiques
     */
    private function isStatsRHQuestion(string $question): bool
    {
        $q = strtolower($question);
        $keywords = ['statistique', 'statistiques', 'top', 'les plus', 'le plus', 'ancien', 'ancienneté', 'employés avec le plus', 'employes avec le plus', 'employés les plus', 'employes les plus'];

        foreach ($keywords as $kw) {
            if (str_contains($q, $kw)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gérer les statistiques RH (top anciens, top congés...)
     */
    private function handleStatsRHQuestion(User $user, string $question): array
    {
        if (!$user->isAdmin() && !$user->isRH()) {
            return [
                'success' => true,
                'response' => 'Seuls les membres du service RH ou les administrateurs peuvent consulter ces statistiques.',
                'intent' => 'statistiques',
            ];
        }

        // Top anciens (par date d'embauche la plus ancienne)
        $topAnciens = Employe::whereNotNull('date_embauche')
            ->orderBy('date_embauche', 'asc')
            ->limit(5)
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'nom' => $e->nom . ' ' . $e->prenom,
                'date_embauche' => $e->date_embauche?->format('d/m/Y'),
                'anciennete_annees' => $e->date_embauche ? Carbon::parse($e->date_embauche)->diffInYears(now()) : null,
            ])->toArray();

        // Top congés pris (regroupé par employe)
        $topConges = DB::table('demandes_conges')
            ->select('employe_id', DB::raw('SUM(jours_demandes) as total_jours'))
            ->where('statut', 'rh_valide')
            ->groupBy('employe_id')
            ->orderByDesc('total_jours')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'employe_id' => $row->employe_id,
                'total_jours' => (int) $row->total_jours,
                'nom' => optional(Employe::find($row->employe_id))->nom . ' ' . optional(Employe::find($row->employe_id))->prenom,
            ])->toArray();

        $response = "Statistiques RH : top anciens et top congés fournis.";

        return [
            'success' => true,
            'response' => $response,
            'intent' => 'statistiques',
            'data' => [
                'top_anciens' => $topAnciens,
                'top_conges' => $topConges,
            ],
        ];
    }

    /**
     * Détecter si la question concerne le calendrier des congés/événements
     */
    private function isCalendrierCongesQuestion(string $question): bool
    {
        $q = strtolower($question);
        $keywords = ['calendrier', 'calendrier des congés', 'événement', 'événements', 'congé', 'conges', 'agenda'];

        foreach ($keywords as $kw) {
            if (str_contains($q, $kw)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gérer les événements calendrier (jours fériés + congés approuvés)
     */
    private function handleCalendrierCongesQuestion(User $user, string $question): array
    {
        // Normaliser la question pour détection de mois (sans accents)
        $qNorm = strtolower(@iconv('UTF-8', 'ASCII//TRANSLIT', $question) ?: $question);

        // Mapper noms de mois en numéro
        $months = [
            'janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
            'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8,
            'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12,
        ];

        $start = now();
        $end = now()->addDays(30);
        $labelPeriod = 'les 30 prochains jours';

        // Si l'utilisateur mentionne un mois, cibler ce mois entier
        $foundMonth = null;
        foreach ($months as $name => $num) {
            if (str_contains($qNorm, $name)) {
                $foundMonth = $num;
                break;
            }
        }

        if ($foundMonth) {
            $year = now()->year;
            // Construire le premier jour du mois (prochaine occurrence si le mois est écoulé cette année)
            $candidate = Carbon::create($year, $foundMonth, 1)->startOfDay();
            if ($candidate->lt(now())) {
                // user likely refers to the next occurrence
                $candidate = $candidate->addYear();
            }
            $start = $candidate->copy();
            $end = $candidate->copy()->endOfMonth();
            $labelPeriod = 'le mois de ' . array_search($foundMonth, $months);
        }

        // Jours fériés (inclus les récurrents pour le mois demandé)
        $joursFeries = JourFerie::whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->orderBy('date')
            ->get()
            ->map(fn($j) => [
                'type' => 'jour_ferie',
                'titre' => $j->nom,
                'date_debut' => Carbon::parse($j->date)->format('Y-m-d'),
                'date_fin' => Carbon::parse($j->date)->format('Y-m-d'),
            ])->toArray();

        // Ajouter les jours fériés récurrents correspondant au mois (si absent)
        if ($foundMonth) {
            $recurrent = JourFerie::where('recurrent', true)->get();
            foreach ($recurrent as $r) {
                $rMonth = Carbon::parse($r->date)->month;
                if ($rMonth === $foundMonth) {
                    // Construire la date pour l'année cible
                    $yearTarget = $start->year;
                    $day = Carbon::parse($r->date)->day;
                    $computedDate = Carbon::create($yearTarget, $foundMonth, $day)->format('Y-m-d');
                    // Vérifier s'il n'est pas déjà présent
                    $exists = collect($joursFeries)->contains(fn($it) => ($it['date_debut'] ?? '') === $computedDate);
                    if (!$exists) {
                        $joursFeries[] = [
                            'type' => 'jour_ferie',
                            'titre' => $r->nom,
                            'date_debut' => $computedDate,
                            'date_fin' => $computedDate,
                        ];
                    }
                }
            }
            // trier les jours fériés par date
            usort($joursFeries, fn($a, $b) => strcmp($a['date_debut'], $b['date_debut']));
        }

        // Congés approuvés
        $demandeQuery = DemandeConge::where('statut', 'rh_valide')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('date_debut', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                  ->orWhereBetween('date_fin', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->whereDate('date_debut', '<=', $start->format('Y-m-d'))
                         ->whereDate('date_fin', '>=', $end->format('Y-m-d'));
                  });
            });

        if (!$user->isAdmin() && !$user->isRH() && $user->employe) {
            $demandeQuery->where('employe_id', $user->employe->id);
        }

        $conges = $demandeQuery->with('employe')->get()->map(fn($d) => [
            'type' => 'conge',
            'titre' => ($d->employe?->nom ?? 'Employé') . ' - congé',
            'employe_id' => $d->employe_id,
            'date_debut' => Carbon::parse($d->date_debut)->format('Y-m-d'),
            'date_fin' => Carbon::parse($d->date_fin)->format('Y-m-d'),
            'jours' => $d->jours_demandes,
        ])->toArray();

        // Autres événements calendrier
        $evenements = CalendrierEvenement::where(function ($q) use ($start, $end) {
                $q->whereBetween('date_debut', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                  ->orWhereBetween('date_fin', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
            })
            ->orderBy('date_debut')
            ->get()
            ->map(fn($e) => [
                'type' => $e->type,
                'titre' => $e->description,
                'date_debut' => $e->date_debut?->format('Y-m-d'),
                'date_fin' => $e->date_fin?->format('Y-m-d'),
            ])->toArray();

        $all = array_merge($joursFeries, $conges, $evenements);

        $response = "Événements du calendrier pour {$labelPeriod} : " . count($all) . " événement(s) trouvés.";

        return [
            'success' => true,
            'response' => $response,
            'intent' => 'calendrier',
            'data' => [
                'evenements' => $all,
                'periode' => [
                    'start' => $start->format('Y-m-d'),
                    'end' => $end->format('Y-m-d'),
                ],
            ],
        ];
    }

    /**
     * Détecter si la question demande une répartition complète
     */
    private function isDemandeRepartitionComplete(string $question): bool
    {
        $question = strtolower($question);
        $completeKeywords = ['chaque', 'tous', 'toutes', 'liste', 'toute', 'complet', 'complète', 'dans chaque', 'nombre', 'combien'];

        foreach ($completeKeywords as $keyword) {
            if (str_contains($question, $keyword)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Obtenir des suggestions de questions
     */
    public function getSuggestions(User $user): array
    {
        $role = strtolower(trim((string) ($user->role ?? '')));

        // Suggestions basées sur le rôle de l'utilisateur
        if (in_array($role, ['admin', 'rh'])) {
            $suggestions = [
      
            ];
        } elseif ($role === 'manager') {
            $suggestions = [
                'Valider une demande de congé',
                'Consulter mes équipes',
                'Quand est le prochain jour férié ?',
                'Comment demander une attestation de travail ?',
            ];
        } else {
            $suggestions = [
                'Quel est mon solde de congés ?',
                'Quand est mon prochain jour férié ?',
                'Comment demander une attestation de travail ?',
                'Quelles formations sont disponibles ?',
            ];
        }

        // Forcer trois questions fréquentes en tête de liste et limiter à 6
        $topQuestions = [
            "Quel taux de cotisation patronale s'applique au salaire brut ?",
            "Quel est l'état de caisse actuel ?",
            "Combien d'employés y a‑t‑il dans l'entreprise ?",
        ];

        // Préserver l'ordre : les éléments de $topQuestions doivent apparaître en tête
        foreach (array_reverse($topQuestions) as $q) {
            if (!in_array($q, $suggestions, true)) {
                array_unshift($suggestions, $q);
            }
        }

        // Dédupliquer et limiter à 6 suggestions affichées
        $suggestions = array_values(array_unique($suggestions));
        if (count($suggestions) > 6) {
            $suggestions = array_slice($suggestions, 0, 6);
        }

        // Conserver la logique d'alerte sur solde faible pour les employés
        if ($user->employe) {
            $solde = SoldeConge::where('employe_id', $user->employe->id)->first();
            if ($solde && $solde->solde_actuel < 5) {
                // ajouter en tête si ce n'est pas déjà présent
                if (!in_array('Comment fonctionne l\'acquisition de congés ?', $suggestions)) {
                    array_unshift($suggestions, 'Comment fonctionne l\'acquisition de congés ?');
                }
            }
        }

        return $suggestions;
    }
}
