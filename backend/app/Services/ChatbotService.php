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
use App\Models\Departement;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
    public function processQuestion(string $question, User $user): array
    {
        try {
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
            'effectif' => ['combien', 'effectif', 'employé', 'employés', 'personnel', 'équipe', 'nombre'],
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
    return $this->ai->chatText($systemPrompt, $question, [
        'temperature' => 0.7,
        'max_tokens' => 500,
    ]);
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
        $question = strtolower($question);
        $keywords = ['combien', 'effectif', 'employé', 'employés', 'personnel', 'équipe', 'nombre'];

        foreach ($keywords as $keyword) {
            if (str_contains($question, $keyword)) {
                // Éviter les faux positifs (ex: "combien de jours de congé")
                $excludeWords = ['congé', 'conge', 'vacances', 'solde', 'paie', 'salaire', 'pointage', 'heure'];
                foreach ($excludeWords as $exclude) {
                    if (str_contains($question, $exclude)) {
                        return false;
                    }
                }

                // Éviter les questions sur la répartition/comparaison (ex: "département qui a le plus")
                $repartitionWords = ['département', 'departement', 'service', 'plus', 'moins', 'par', 'selon', 'répartition', 'réparti'];
                foreach ($repartitionWords as $repartition) {
                    if (str_contains($question, $repartition)) {
                        return false;
                    }
                }

                return true;
            }
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
                'repartition' => $sorted->map(fn($d) => [
                    'departement' => $d->nom,
                    'employes_actifs' => $d->employes_actifs_count,
                ])->toArray(),
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
