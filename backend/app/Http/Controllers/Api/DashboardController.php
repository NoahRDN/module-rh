<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateDashboardSnapshotJob;
use App\Models\Employe;
use App\Models\Contrat;
use App\Models\Departement;
use App\Models\DemandeConge;
use App\Models\DashboardStat;
use App\Models\Devise;
use App\Models\EntrepriseSetting;
use App\Models\Pointage;
use App\Models\Evaluation;
use App\Models\JourFerie;
use App\Models\WorktimeSetting;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const SNAPSHOT_STALE_MINUTES = 30;

    public function bootstrap(Request $request)
    {
        $filtre = $request->get('filtre', 'annee');
        $date = $request->get('date', now()->format('Y-m-d'));
        $periode = $this->getPeriode(
            $filtre,
            $date,
            $request->query('date_debut'),
            $request->query('date_fin')
        );

        $entreprise = $this->cachedEntrepriseSetting();
        $devises = $this->cachedDevisesActives();
        $dashboard = $this->dashboardStatPayload($filtre, $periode);

        return response()->json([
            'utilisateur' => $this->userPayload($request),
            'entreprise' => $entreprise,
            'devise' => $this->resolveDevise($entreprise['devise'] ?? null, $devises),
            'devises' => $devises,
            'statistiques' => $dashboard['statistiques'],
            'alertes_recentes' => $dashboard['alertes_recentes'],
            'donnees_rapides' => $dashboard['donnees_rapides'],
            'snapshot' => $dashboard['snapshot'],
        ]);
    }

    /**
     * Statistiques RH principales avec filtres de période
     */
    public function statistiques(Request $request)
    {
        $filtre = $request->get('filtre', 'annee'); // mois, annee, periode
        $date = $request->get('date', now()->format('Y-m-d'));

        $periode = $this->getPeriode(
            $filtre,
            $date,
            $request->query('date_debut'),
            $request->query('date_fin')
        );

        $dashboard = $this->dashboardStatPayload($filtre, $periode);

        return response()->json([
            'status' => $dashboard['snapshot']['status'],
            'message' => $dashboard['snapshot']['message'],
            'data' => $dashboard['statistiques'],
            'meta' => $dashboard['snapshot']['meta'],
        ]);
    }

    public function refreshSnapshot(string $filtre = 'annee', ?string $date = null, ?string $dateDebut = null, ?string $dateFin = null): DashboardStat
    {
        $periode = $this->getPeriode($filtre, $date ?: now()->format('Y-m-d'), $dateDebut, $dateFin);

        return $this->storeDashboardSnapshot($filtre, $periode);
    }

    private function dashboardStatPayload(string $filtre, array $periode): array
    {
        $snapshot = $this->findDashboardSnapshot($filtre, $periode);
        $state = $this->snapshotState($snapshot, $filtre, $periode);
        $hasData = in_array($state['status'], ['available', 'stale'], true);

        return [
            'statistiques' => $hasData ? $snapshot?->statistiques : null,
            'alertes_recentes' => $hasData ? ($snapshot?->alertes_recentes ?: []) : [],
            'donnees_rapides' => $hasData ? ($snapshot?->donnees_rapides ?: []) : [],
            'snapshot' => $state,
        ];
    }

    private function findDashboardSnapshot(string $filtre, array $periode): ?DashboardStat
    {
        return DashboardStat::query()
            ->where('filtre', $filtre)
            ->whereDate('date_debut', $periode['debut']->toDateString())
            ->whereDate('date_fin', $periode['fin']->toDateString())
            ->first();
    }

    private function snapshotState(?DashboardStat $snapshot, string $filtre, array $periode): array
    {
        $meta = $this->snapshotMeta($snapshot, $filtre, $periode);

        if (!$snapshot) {
            $this->queueSnapshotGeneration($filtre, $periode);

            return [
                'status' => 'generating',
                'message' => 'Les statistiques pour cette période sont en cours de génération.',
                'meta' => $meta,
            ];
        }

        if ($snapshot->status === 'generating') {
            return [
                'status' => 'generating',
                'message' => 'Les statistiques pour cette période sont en cours de génération.',
                'meta' => $meta,
            ];
        }

        if ($snapshot->status === 'stale') {
            return [
                'status' => 'stale',
                'message' => 'Les statistiques sont affichées, mais une mise à jour est en cours.',
                'meta' => array_merge($meta, [
                    'is_stale' => true,
                    'is_refreshing' => !$snapshot->error_message,
                ]),
            ];
        }

        if ($snapshot->status === 'failed') {
            $this->queueSnapshotGeneration($filtre, $periode, $snapshot);

            return [
                'status' => 'missing_snapshot',
                'message' => 'Les statistiques pour cette période ne sont pas encore disponibles.',
                'meta' => $meta,
            ];
        }

        if ($this->isSnapshotStale($snapshot)) {
            $this->queueSnapshotGeneration($filtre, $periode, $snapshot);

            return [
                'status' => 'stale',
                'message' => 'Les statistiques sont affichées, mais une mise à jour est en cours.',
                'meta' => array_merge($meta, ['is_stale' => true, 'is_refreshing' => true]),
            ];
        }

        return [
            'status' => 'available',
            'message' => 'Les statistiques sont disponibles.',
            'meta' => $meta,
        ];
    }

    private function snapshotMeta(?DashboardStat $snapshot, string $filtre, array $periode): array
    {
        return [
            'period_type' => $filtre,
            'date_debut' => $periode['debut']->toDateString(),
            'date_fin' => $periode['fin']->toDateString(),
            'generated_at' => $snapshot?->generated_at?->toIso8601String(),
            'updated_at' => $snapshot?->updated_at?->toIso8601String(),
            'is_stale' => $snapshot ? $this->isSnapshotStale($snapshot) : false,
            'is_refreshing' => false,
            'error_message' => $snapshot?->error_message,
        ];
    }

    private function isSnapshotStale(DashboardStat $snapshot): bool
    {
        $generatedAt = $snapshot->generated_at ?: $snapshot->updated_at;

        return !$generatedAt || $generatedAt->lt(now()->subMinutes($this->snapshotStaleMinutes()));
    }

    private function snapshotStaleMinutes(): int
    {
        return max(1, (int) env('DASHBOARD_SNAPSHOT_STALE_MINUTES', self::SNAPSHOT_STALE_MINUTES));
    }

    private function queueSnapshotGeneration(string $filtre, array $periode, ?DashboardStat $snapshot = null): void
    {
        if ($snapshot?->status === 'generating') {
            return;
        }

        $nextStatus = $snapshot && ($snapshot->generated_at || $snapshot->statistiques) ? 'stale' : 'generating';

        $row = DashboardStat::query()->updateOrCreate(
            [
                'filtre' => $filtre,
                'date_debut' => $periode['debut']->toDateString(),
                'date_fin' => $periode['fin']->toDateString(),
            ],
            [
                'period_type' => $filtre,
                'statistiques' => $snapshot?->statistiques ?: [],
                'donnees_rapides' => $snapshot?->donnees_rapides ?: [],
                'alertes_recentes' => $snapshot?->alertes_recentes ?: [],
                'status' => $nextStatus,
                'error_message' => null,
            ],
        );

        if ($row->wasRecentlyCreated || $row->wasChanged('status')) {
            GenerateDashboardSnapshotJob::dispatch(
                $filtre,
                $periode['debut']->toDateString(),
                $periode['fin']->toDateString(),
            )->afterResponse();
        }
    }

    private function storeDashboardSnapshot(string $filtre, array $periode): DashboardStat
    {
        $payload = [
            'period_type' => $filtre,
            'statistiques' => $this->getStatistiquesPayload($filtre, $periode),
            'donnees_rapides' => $this->getDonneesRapides($periode),
            'alertes_recentes' => app(AlerteController::class)->recent(6),
            'generated_at' => now(),
            'refreshed_by' => app()->runningInConsole() ? 'command' : 'http',
            'status' => 'available',
            'error_message' => null,
        ];

        return DashboardStat::query()->updateOrCreate(
            [
                'filtre' => $filtre,
                'date_debut' => $periode['debut']->toDateString(),
                'date_fin' => $periode['fin']->toDateString(),
            ],
            $payload
        );
    }

    private function getStatistiquesPayload(string $filtre, array $periode): array
    {
        return [
            'effectifs' => $this->getStatistiquesEffectifs($periode),
            'indicateurs' => $this->getIndicateursRH($periode),
            'repartitions' => $this->getRepartitions($periode),
            'tendances' => $this->getTendances($periode),
            'periode' => [
                'filtre' => $filtre,
                'debut' => $periode['debut']->format('Y-m-d'),
                'fin' => $periode['fin']->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Calcule les dates de début et fin selon le filtre
     */
    private function getPeriode(string $filtre, string $date, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $dateRef = Carbon::parse($date);

        switch ($filtre) {
            case 'mois':
                return [
                    'debut' => $dateRef->copy()->startOfMonth(),
                    'fin' => $dateRef->copy()->endOfMonth(),
                ];
            case 'periode':
                $debut = $dateDebut ? Carbon::parse($dateDebut)->startOfDay() : $dateRef->copy()->startOfMonth();
                $fin = $dateFin ? Carbon::parse($dateFin)->endOfDay() : $dateRef->copy()->endOfMonth();

                if ($fin->lt($debut)) {
                    [$debut, $fin] = [$fin->copy()->startOfDay(), $debut->copy()->endOfDay()];
                }

                return [
                    'debut' => $debut,
                    'fin' => $fin,
                ];
            case 'annee':
            default:
                return [
                    'debut' => $dateRef->copy()->startOfYear(),
                    'fin' => $dateRef->copy()->endOfYear(),
                ];
        }
    }

    private function userPayload(Request $request): array
    {
        $user = $request->user();
        $user?->loadMissing('employe:id,matricule,nom,prenom,email,poste_id,departement_id');

        return [
            'id' => $user?->id,
            'name' => $user?->name,
            'email' => $user?->email,
            'role' => $user?->role,
            'employe_id' => $user?->employe_id,
            'employe' => $user?->employe ? [
                'id' => $user->employe->id,
                'matricule' => $user->employe->matricule,
                'nom' => $user->employe->nom,
                'prenom' => $user->employe->prenom,
                'email' => $user->employe->email,
            ] : null,
        ];
    }

    private function cachedEntrepriseSetting(): array
    {
        return Cache::remember('settings:entreprise', now()->addMinutes(30), function () {
            $setting = EntrepriseSetting::firstOrCreate(
                [],
                [
                    'nom' => config('app.name', 'Module RH'),
                    'devise' => 'MGA',
                ]
            );

            if (!$setting->devise) {
                $setting->devise = 'MGA';
                $setting->save();
            }

            return [
                'id' => $setting->id,
                'nom' => $setting->nom,
                'devise' => $setting->devise,
                'logo_path' => $setting->logo_path,
                'logo_url' => $setting->logo_url,
                'created_at' => $setting->created_at,
                'updated_at' => $setting->updated_at,
            ];
        });
    }

    private function cachedDevisesActives(): array
    {
        return Cache::remember('settings:devises:active', now()->addMinutes(30), function () {
            return Devise::query()
                ->select(['id', 'code', 'libelle', 'symbole', 'active'])
                ->where('active', true)
                ->orderBy('code')
                ->get()
                ->toArray();
        });
    }

    private function resolveDevise(?string $code, array $devises): ?array
    {
        if (!$code) {
            return null;
        }

        return collect($devises)->firstWhere('code', $code)
            ?: Devise::query()
                ->select(['id', 'code', 'libelle', 'symbole', 'active'])
                ->where('code', $code)
                ->first()
                ?->toArray();
    }

    private function getDonneesRapides(array $periode): array
    {
        $now = now()->toDateString();

        $derniersEmployes = Employe::query()
            ->select(['id', 'matricule', 'nom', 'prenom', 'poste_id', 'departement_id', 'date_embauche', 'created_at'])
            ->with([
                'poste:id,nom,departement_id,categorie,categorie_level',
                'departement:id,nom',
            ])
            ->withExists(['contrats as actif' => function ($q) use ($now) {
                $q->whereDate('date_debut', '<=', $now)
                    ->where(function ($w) use ($now) {
                        $w->whereNull('date_fin')->orWhereDate('date_fin', '>=', $now);
                    });
            }])
            ->whereBetween('date_embauche', [$periode['debut'], $periode['fin']])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return [
            'derniers_employes' => $derniersEmployes,
            'demandes_conges_en_attente' => DemandeConge::where('statut', '!=', 'rh_valide')->count(),
            'contrats_expirant_30j' => Contrat::whereNotNull('date_fin')
                ->whereDate('date_fin', '>=', $now)
                ->whereDate('date_fin', '<=', now()->addDays(30)->toDateString())
                ->count(),
            'pointages_aujourdhui' => Pointage::whereBetween('pointe_a', [now()->startOfDay(), now()->endOfDay()])->count(),
        ];
    }

    /**
     * Statistiques sur les effectifs
     */
    private function getStatistiquesEffectifs(array $periode): array
    {
        $dateReference = $periode['fin']->toDateString();

        // Employés actifs à la fin de la période sélectionnée.
        $employesActifs = Employe::whereHas('contrats', function ($q) use ($dateReference) {
            $q->whereDate('date_debut', '<=', $dateReference)
              ->where(function ($q2) use ($dateReference) {
                  $q2->whereNull('date_fin')->orWhereDate('date_fin', '>=', $dateReference);
              });
        })->count();

        // Total employés
        $totalEmployes = Employe::count();

        // Nouvelles entrées sur la période : date d'embauche ou premier contrat démarré dans la période.
        $nouveauxEmployes = Employe::where(function ($q) use ($periode) {
            $q->whereBetween('date_embauche', [$periode['debut'], $periode['fin']])
              ->orWhereHas('contrats', function ($contrats) use ($periode) {
                  $contrats->whereBetween('date_debut', [$periode['debut'], $periode['fin']]);
              });
        })
            ->count();

        // Départs sur la période (contrats terminés)
        $departs = Contrat::whereBetween('date_fin', [$periode['debut'], $periode['fin']])
            ->whereDoesntHave('employe.contrats', function ($q) use ($dateReference) {
                $q->whereDate('date_debut', '<=', $dateReference)
                  ->where(function ($q2) use ($dateReference) {
                      $q2->whereNull('date_fin')->orWhereDate('date_fin', '>=', $dateReference);
                  });
            })
            ->distinct('employe_id')
            ->count('employe_id');

        return [
            'total' => $totalEmployes,
            'actifs' => $employesActifs,
            'inactifs' => $totalEmployes - $employesActifs,
            'nouveaux' => $nouveauxEmployes,
            'departs' => $departs,
        ];
    }

    /**
     * Indicateurs RH clés
     */
    private function getIndicateursRH(array $periode): array
    {
        $now = now()->toDateString();

        // Ancienneté moyenne (en années)
        // PostgreSQL compatible : age(now(), date_embauche) converti en années
        $anciennete = Employe::whereNotNull('date_embauche')
            ->selectRaw("AVG(EXTRACT(epoch FROM age(now(), date_embauche)) / 31557600) as moyenne")
            ->value('moyenne') ?? 0;

        // Taux de turnover = (Départs / Effectif moyen) * 100
        $effectifDebut = Employe::whereHas('contrats', function ($q) use ($periode) {
            $q->whereDate('date_debut', '<=', $periode['debut'])
              ->where(function ($q2) use ($periode) {
                  $q2->whereNull('date_fin')->orWhereDate('date_fin', '>=', $periode['debut']);
              });
        })->count();

        $effectifFin = Employe::whereHas('contrats', function ($q) use ($periode) {
            $q->whereDate('date_debut', '<=', $periode['fin'])
              ->where(function ($q2) use ($periode) {
                  $q2->whereNull('date_fin')->orWhereDate('date_fin', '>=', $periode['fin']);
              });
        })->count();

        $effectifMoyen = ($effectifDebut + $effectifFin) / 2;

        $departs = Contrat::whereBetween('date_fin', [$periode['debut'], $periode['fin']])
            ->distinct('employe_id')
            ->count('employe_id');

        $turnover = $effectifMoyen > 0 ? round(($departs / $effectifMoyen) * 100, 2) : 0;

        // Taux d'absentéisme sur la période réellement observable (pas les jours futurs).
        $periodeAbsenteisme = $this->getPeriodeObservable($periode);
        $settingsPresence = $this->loadWorktimeSettings();
        $employeIdsAbsenteisme = $this->getEmployeIdsPourAbsenteisme(
            $periodeAbsenteisme['debut'],
            $periodeAbsenteisme['fin']
        );
        $joursOuvres = $this->calculerJoursOuvresPresence(
            $periodeAbsenteisme['debut'],
            $periodeAbsenteisme['fin'],
            $settingsPresence
        );
        $joursTheoriques = $joursOuvres * max(1, $employeIdsAbsenteisme->count());

        $joursAbsencesConges = DemandeConge::select(['employe_id', 'date_debut', 'date_fin'])
            ->where('statut', 'rh_valide')
            ->whereIn('employe_id', $employeIdsAbsenteisme)
            ->where(function ($q) use ($periodeAbsenteisme) {
                $q->whereBetween('date_debut', [$periodeAbsenteisme['debut'], $periodeAbsenteisme['fin']])
                  ->orWhereBetween('date_fin', [$periodeAbsenteisme['debut'], $periodeAbsenteisme['fin']]);
            })
            ->get()
            ->sum(function ($demande) use ($periodeAbsenteisme, $settingsPresence) {
                $debut = Carbon::parse($demande->date_debut)->max($periodeAbsenteisme['debut'])->startOfDay();
                $fin = Carbon::parse($demande->date_fin)->min($periodeAbsenteisme['fin'])->startOfDay();

                if ($fin->lt($debut)) {
                    return 0;
                }

                return $this->calculerJoursOuvresPresence($debut, $fin, $settingsPresence);
            });
        $joursAbsencesPointage = $this->calculerJoursAbsencePointage(
            $periodeAbsenteisme,
            $employeIdsAbsenteisme,
            $settingsPresence
        );
        $joursAbsences = $joursAbsencesConges + $joursAbsencesPointage;

        $tauxAbsenteisme = $joursTheoriques > 0 ? round(($joursAbsences / $joursTheoriques) * 100, 2) : 0;

        // Score de performance moyen
        $scorePerformance = Evaluation::where('statut', 'valide')
            ->where('periode', 'like', Carbon::parse($periode['debut'])->format('Y') . '%')
            ->avg('score_global') ?? 0;

        return [
            'anciennete_moyenne' => round($anciennete, 1),
            'turnover' => $turnover,
            'absenteisme' => $tauxAbsenteisme,
            'performance_moyenne' => round($scorePerformance, 1),
        ];
    }

    /**
     * Répartitions pour les graphiques
     */
    private function getRepartitions(array $periode): array
    {
        $dateReference = $periode['fin']->toDateString();

        // Par département : employés actifs à la fin de la période sélectionnée.
        $parDepartement = Departement::select('departements.id', 'departements.nom')
            ->whereExists(function ($q) use ($dateReference) {
                $q->select(DB::raw(1))
                    ->from('employes')
                    ->whereColumn('employes.departement_id', 'departements.id')
                    ->whereExists(function ($q2) use ($dateReference) {
                        $q2->select(DB::raw(1))
                            ->from('contrats')
                            ->whereColumn('contrats.employe_id', 'employes.id')
                            ->whereDate('date_debut', '<=', $dateReference)
                            ->where(function ($q3) use ($dateReference) {
                                $q3->whereNull('date_fin')->orWhereDate('date_fin', '>=', $dateReference);
                            });
                    });
            })
            ->withCount(['employes as employes_actifs_count' => function ($q) use ($dateReference) {
                $q->whereHas('contrats', function ($q2) use ($dateReference) {
                    $q2->whereDate('date_debut', '<=', $dateReference)
                        ->where(function ($q3) use ($dateReference) {
                            $q3->whereNull('date_fin')->orWhereDate('date_fin', '>=', $dateReference);
                        });
                });
            }])
            ->get()
            ->map(fn($d) => ['label' => $d->nom, 'value' => $d->employes_actifs_count]);

        // Par type de contrat : contrats actifs à la fin de la période sélectionnée.
        $parTypeContrat = Contrat::whereDate('date_debut', '<=', $dateReference)
            ->where(function ($q) use ($dateReference) {
                $q->whereNull('date_fin')->orWhereDate('date_fin', '>=', $dateReference);
            })
            ->select('type_contrat', DB::raw('count(*) as total'))
            ->groupBy('type_contrat')
            ->get()
            ->map(fn($c) => ['label' => $c->type_contrat ?? 'Non défini', 'value' => $c->total]);

        // Par genre (à partir des employés actifs à la fin de la période)
        $parGenre = $this->getGenreDistribution($periode);

        // Par tranche d'âge des employés actifs à la fin de la période
        $parAge = $this->getAgeDistribution($periode);

        return [
            'departements' => $parDepartement,
            'types_contrat' => $parTypeContrat,
            'genres' => $parGenre,
            'tranches_age' => $parAge,
        ];
    }

    /**
     * Distribution par genre
     */
    private function getGenreDistribution(array $periode): array
    {
        // Si vous ajoutez un champ genre, groupez ici par genre.
        return [
            ['label' => 'Non renseigné', 'value' => $this->employesActifsALaDate($periode['fin'])->count()],
        ];
    }

    /**
     * Distribution par tranche d'âge
     */
    private function getAgeDistribution(array $periode): array
    {
        $dateReference = $periode['fin']->copy();

        $tranches = [
            ['label' => '< 25 ans', 'min' => 0, 'max' => 24, 'value' => 0],
            ['label' => '25-34 ans', 'min' => 25, 'max' => 34, 'value' => 0],
            ['label' => '35-44 ans', 'min' => 35, 'max' => 44, 'value' => 0],
            ['label' => '45-54 ans', 'min' => 45, 'max' => 54, 'value' => 0],
            ['label' => '55+ ans', 'min' => 55, 'max' => 100, 'value' => 0],
        ];

        $employes = $this->employesActifsALaDate($dateReference)
            ->select(['id', 'date_naissance'])
            ->whereNotNull('date_naissance')
            ->get();

        foreach ($employes as $emp) {
            $age = $emp->date_naissance->diffInYears($dateReference);
            foreach ($tranches as &$tranche) {
                if ($age >= $tranche['min'] && $age <= $tranche['max']) {
                    $tranche['value']++;
                    break;
                }
            }
        }

        return array_map(fn($t) => ['label' => $t['label'], 'value' => $t['value']], $tranches);
    }

    private function employesActifsALaDate(Carbon $date)
    {
        $dateReference = $date->toDateString();

        return Employe::whereHas('contrats', function ($q) use ($dateReference) {
            $q->whereDate('date_debut', '<=', $dateReference)
              ->where(function ($q2) use ($dateReference) {
                  $q2->whereNull('date_fin')->orWhereDate('date_fin', '>=', $dateReference);
              });
        });
    }

    /**
     * Tendances mensuelles pour l'année en cours
     */
    private function getTendances(array $periode): array
    {
        $tendances = [];
        $current = $periode['debut']->copy()->startOfMonth();
        $end = $periode['fin']->copy()->endOfMonth();

        while ($current <= $end) {
            $moisFin = $current->copy()->endOfMonth();

            // Effectif à la fin du mois
            $effectif = Employe::whereHas('contrats', function ($q) use ($moisFin) {
                $q->whereDate('date_debut', '<=', $moisFin)
                  ->where(function ($q2) use ($moisFin) {
                      $q2->whereNull('date_fin')->orWhereDate('date_fin', '>=', $moisFin);
                  });
            })->count();

            // Entrées du mois : date d'embauche ou contrat démarré sur le mois.
            $entrees = Employe::where(function ($q) use ($current, $moisFin) {
                $q->whereBetween('date_embauche', [$current, $moisFin])
                  ->orWhereHas('contrats', function ($contrats) use ($current, $moisFin) {
                      $contrats->whereBetween('date_debut', [$current, $moisFin]);
                  });
            })->count();

            // Sorties du mois
            $sorties = Contrat::whereBetween('date_fin', [$current, $moisFin])
                ->distinct('employe_id')
                ->count('employe_id');

            $tendances[] = [
                'mois' => $current->format('Y-m'),
                'label' => $current->translatedFormat('M Y'),
                'effectif' => $effectif,
                'entrees' => $entrees,
                'sorties' => $sorties,
            ];

            $current->addMonth();
        }

        return $tendances;
    }

    /**
     * Calcule le nombre de jours ouvrés entre deux dates
     */
    private function calculerJoursOuvres(Carbon $debut, Carbon $fin): int
    {
        return $debut->diffInWeekdays($fin) + 1;
    }

    private function getPeriodeObservable(array $periode): array
    {
        return [
            'debut' => $periode['debut']->copy()->startOfDay(),
            'fin' => $periode['fin']->copy()->min(now())->startOfDay(),
        ];
    }

    private function getEmployeIdsPourAbsenteisme(Carbon $debut, Carbon $fin)
    {
        if ($fin->lt($debut)) {
            return collect();
        }

        $idsAvecContrat = Employe::whereHas('contrats', function ($q) use ($debut, $fin) {
            $q->whereDate('date_debut', '<=', $fin)
              ->where(function ($q2) use ($debut) {
                  $q2->whereNull('date_fin')->orWhereDate('date_fin', '>=', $debut);
              });
        })->pluck('id');

        return $idsAvecContrat->isNotEmpty()
            ? $idsAvecContrat
            : Employe::pluck('id');
    }

    private function calculerJoursOuvresPresence(Carbon $debut, Carbon $fin, array $settings, ?array $holidayDates = null): int
    {
        if ($fin->lt($debut)) {
            return 0;
        }

        $jours = 0;
        $holidayDates ??= $this->holidayDatesBetween($debut, $fin);
        $period = new DatePeriod($debut, new DateInterval('P1D'), $fin->copy()->addDay());

        foreach ($period as $day) {
            if ($this->isJourTravaille(Carbon::instance($day), $settings, $holidayDates)) {
                $jours++;
            }
        }

        return $jours;
    }

    private function calculerJoursAbsencePointage(array $periode, $employeIds, array $settings): int
    {
        $debut = $periode['debut']->copy()->startOfDay();
        $fin = $periode['fin']->copy()->startOfDay();

        if ($fin->lt($debut) || $employeIds->isEmpty()) {
            return 0;
        }

        $holidayDates = $this->holidayDatesBetween($debut, $fin);
        $congesValides = $this->approvedLeaveDaysByEmployee($employeIds, $debut, $fin);

        $pointages = Pointage::select(['id', 'employe_id', 'type', 'pointe_a'])
            ->whereIn('employe_id', $employeIds)
            ->between($debut->toDateString(), $fin->toDateString())
            ->orderBy('pointe_a')
            ->get()
            ->groupBy(fn ($pointage) => $pointage->employe_id . '|' . $pointage->pointe_a->format('Y-m-d'));

        $absences = 0;

        foreach ($employeIds as $employeId) {
            $period = new DatePeriod($debut, new DateInterval('P1D'), $fin->copy()->addDay());

            foreach ($period as $day) {
                $jour = Carbon::instance($day)->toDateString();

                if (isset($congesValides[(int) $employeId][$jour])) {
                    continue;
                }

                $resume = $this->calculerJourneePointage(
                    $pointages->get($employeId . '|' . $jour, collect()),
                    $jour,
                    $settings,
                    $holidayDates
                );

                if ($resume['absent']) {
                    $absences++;
                }
            }
        }

        return $absences;
    }

    private function calculerJourneePointage($pointages, string $jour, array $settings, ?array $holidayDates = null): array
    {
        $hoursPerDay = (float) ($settings['hours_per_day'] ?? 8);
        $retardThresholdHours = (float) ($settings['retard_threshold_hours'] ?? 2);

        $dateObj = Carbon::parse($jour);

        if (!$this->isJourTravaille($dateObj, $settings, $holidayDates)) {
            return ['absent' => false];
        }

        if ($pointages->isEmpty()) {
            return ['absent' => true];
        }

        $premiereEntree = $pointages->firstWhere('type', 'entree');
        $derniereSortie = $pointages->where('type', 'sortie')->last();

        if (!$premiereEntree || !$derniereSortie) {
            return ['absent' => true];
        }

        $minutesBrut = $premiereEntree->pointe_a->diffInMinutes($derniereSortie->pointe_a);
        $minutesTravail = max(0, $minutesBrut - $this->calculerDureePauses($pointages));
        $heuresTravaillees = round($minutesTravail / 60, 2);
        $heuresManquantes = max(0, round($hoursPerDay - $heuresTravaillees, 2));

        return [
            'absent' => $heuresTravaillees <= 0 || $heuresManquantes > $retardThresholdHours,
        ];
    }

    private function isJourTravaille(Carbon $date, array $settings, ?array $holidayDates = null): bool
    {
        $workingDays = $settings['working_days'] ?? ['mon', 'tue', 'wed', 'thu', 'fri'];
        $saturdayMode = $settings['saturday_mode'] ?? 'normal';
        $dayCode = strtolower(substr($date->format('D'), 0, 3));
        $isSaturday = $dayCode === 'sat';
        $isWorkingDay = in_array($dayCode, $workingDays, true) || ($isSaturday && $saturdayMode === 'normal');
        $isHoliday = $holidayDates !== null
            ? isset($holidayDates[$date->toDateString()])
            : $this->isHoliday($date);

        return $isWorkingDay && !$isHoliday;
    }

    private function calculerDureePauses($pointages): int
    {
        $pauses = $pointages->filter(
            fn ($pointage) => in_array($pointage->type, ['pause_debut', 'pause_fin'], true)
        )->sortBy('pointe_a')->values();

        $total = 0;
        for ($i = 0; $i < $pauses->count(); $i += 2) {
            $debut = $pauses[$i] ?? null;
            $fin = $pauses[$i + 1] ?? null;

            if ($debut && $fin) {
                $total += $debut->pointe_a->diffInMinutes($fin->pointe_a);
            }
        }

        return $total;
    }

    private function isCongeValide(int $employeId, string $jour): bool
    {
        return DemandeConge::where('employe_id', $employeId)
            ->where('statut', 'rh_valide')
            ->whereDate('date_debut', '<=', $jour)
            ->whereDate('date_fin', '>=', $jour)
            ->exists();
    }

    private function isHoliday(Carbon $date): bool
    {
        $dayMonth = $date->format('m-d');

        return JourFerie::where(function ($q) use ($date) {
                $q->whereDate('date', $date->toDateString())
                  ->where('recurrent', false);
            })
            ->orWhere(function ($q) use ($dayMonth) {
                $q->whereRaw("to_char(date, 'MM-DD') = ?", [$dayMonth])
                  ->where('recurrent', true);
            })
            ->exists();
    }

    private function holidayDatesBetween(Carbon $debut, Carbon $fin): array
    {
        $dates = [];
        $feries = Cache::remember('settings:jours_feries', now()->addMinutes(30), function () {
            return JourFerie::select(['id', 'date', 'recurrent'])->get();
        });

        foreach ($feries as $ferie) {
            if (!$ferie->recurrent) {
                $date = $ferie->date->toDateString();
                if ($date >= $debut->toDateString() && $date <= $fin->toDateString()) {
                    $dates[$date] = true;
                }
                continue;
            }

            for ($year = $debut->year; $year <= $fin->year; $year++) {
                try {
                    $date = Carbon::createFromDate($year, (int) $ferie->date->format('m'), (int) $ferie->date->format('d'))->toDateString();
                } catch (\Throwable $e) {
                    continue;
                }

                if ($date >= $debut->toDateString() && $date <= $fin->toDateString()) {
                    $dates[$date] = true;
                }
            }
        }

        return $dates;
    }

    private function approvedLeaveDaysByEmployee(Collection $employeIds, Carbon $debut, Carbon $fin): array
    {
        $days = [];

        DemandeConge::select(['employe_id', 'date_debut', 'date_fin'])
            ->where('statut', 'rh_valide')
            ->whereIn('employe_id', $employeIds)
            ->whereDate('date_debut', '<=', $fin->toDateString())
            ->whereDate('date_fin', '>=', $debut->toDateString())
            ->get()
            ->each(function ($demande) use (&$days, $debut, $fin) {
                $start = Carbon::parse($demande->date_debut)->max($debut)->startOfDay();
                $end = Carbon::parse($demande->date_fin)->min($fin)->startOfDay();

                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                    $days[(int) $demande->employe_id][$date->toDateString()] = true;
                }
            });

        return $days;
    }

    private function loadWorktimeSettings(): array
    {
        return Cache::remember('settings:worktime', now()->addMinutes(30), function () {
            $setting = WorktimeSetting::first();

            if ($setting) {
                return [
                    'working_days' => $setting->working_days ?: config('worktime.working_days'),
                    'saturday_mode' => $setting->saturday_mode ?: config('worktime.saturday_mode'),
                    'hours_per_day' => $setting->hours_per_day ?? config('worktime.hours_per_day'),
                    'retard_threshold_hours' => $setting->retard_threshold_hours ?? config('worktime.retard_threshold_hours', 2),
                ];
            }

            return config('worktime');
        });
    }

    /**
     * Alertes RH pour le tableau de bord
     */
    public function alertes(Request $request)
    {
        $alerteController = new AlerteController();
        return $alerteController->index();
    }

    /**
     * Top performers de la période
     */
    public function topPerformers(Request $request)
    {
        $limite = $request->get('limite', 5);
        $periode = $request->get('periode', now()->format('Y-m'));

        $topPerformers = Evaluation::with('employe.poste', 'employe.departement')
            ->where('statut', 'valide')
            ->where('periode', $periode)
            ->orderByDesc('score_global')
            ->limit($limite)
            ->get()
            ->map(fn($eval) => [
                'employe' => [
                    'id' => $eval->employe->id,
                    'nom' => $eval->employe->nom,
                    'prenom' => $eval->employe->prenom,
                    'poste' => $eval->employe->poste?->nom,
                    'departement' => $eval->employe->departement?->nom,
                ],
                'score' => $eval->score_global,
                'niveau' => $eval->niveau_performance,
            ]);

        return response()->json(['data' => $topPerformers]);
    }
}
