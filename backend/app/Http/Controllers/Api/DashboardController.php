<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employe;
use App\Models\Contrat;
use App\Models\Departement;
use App\Models\DemandeConge;
use App\Models\Pointage;
use App\Models\Evaluation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Statistiques RH principales avec filtres de période
     */
    public function statistiques(Request $request)
    {
        $filtre = $request->get('filtre', 'annee'); // mois, trimestre, annee
        $date = $request->get('date', now()->format('Y-m-d'));
        
        $periode = $this->getPeriode($filtre, $date);
        
        return response()->json([
            'effectifs' => $this->getStatistiquesEffectifs($periode),
            'indicateurs' => $this->getIndicateursRH($periode),
            'repartitions' => $this->getRepartitions($periode),
            'tendances' => $this->getTendances($periode),
            'periode' => [
                'filtre' => $filtre,
                'debut' => $periode['debut']->format('Y-m-d'),
                'fin' => $periode['fin']->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Calcule les dates de début et fin selon le filtre
     */
    private function getPeriode(string $filtre, string $date): array
    {
        $dateRef = Carbon::parse($date);
        
        switch ($filtre) {
            case 'mois':
                return [
                    'debut' => $dateRef->copy()->startOfMonth(),
                    'fin' => $dateRef->copy()->endOfMonth(),
                ];
            case 'trimestre':
                return [
                    'debut' => $dateRef->copy()->startOfQuarter(),
                    'fin' => $dateRef->copy()->endOfQuarter(),
                ];
            case 'annee':
            default:
                return [
                    'debut' => $dateRef->copy()->startOfYear(),
                    'fin' => $dateRef->copy()->endOfYear(),
                ];
        }
    }

    /**
     * Statistiques sur les effectifs
     */
    private function getStatistiquesEffectifs(array $periode): array
    {
        $now = now()->toDateString();
        
        // Employés actifs (avec contrat en cours)
        $employesActifs = Employe::whereHas('contrats', function ($q) use ($now) {
            $q->whereDate('date_debut', '<=', $now)
              ->where(function ($q2) use ($now) {
                  $q2->whereNull('date_fin')->orWhereDate('date_fin', '>=', $now);
              });
        })->count();

        // Total employés
        $totalEmployes = Employe::count();

        // Nouveaux employés sur la période
        $nouveauxEmployes = Employe::whereBetween('date_embauche', [$periode['debut'], $periode['fin']])
            ->count();

        // Départs sur la période (contrats terminés)
        $departs = Contrat::whereBetween('date_fin', [$periode['debut'], $periode['fin']])
            ->whereDoesntHave('employe.contrats', function ($q) use ($now) {
                $q->whereDate('date_debut', '<=', $now)
                  ->where(function ($q2) use ($now) {
                      $q2->whereNull('date_fin')->orWhereDate('date_fin', '>=', $now);
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

        // Taux d'absentéisme
        $joursOuvres = $this->calculerJoursOuvres($periode['debut'], $periode['fin']);
        $employesActifs = max(1, $effectifMoyen);
        $joursTheoriques = $joursOuvres * $employesActifs;

        $joursAbsences = DemandeConge::where('statut', 'rh_valide')
            ->where(function ($q) use ($periode) {
                $q->whereBetween('date_debut', [$periode['debut'], $periode['fin']])
                  ->orWhereBetween('date_fin', [$periode['debut'], $periode['fin']]);
            })
            ->get()
            ->sum(function ($demande) use ($periode) {
                $debut = Carbon::parse($demande->date_debut)->max($periode['debut']);
                $fin = Carbon::parse($demande->date_fin)->min($periode['fin']);
                return max(0, $debut->diffInWeekdays($fin) + 1);
            });

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
        $now = now()->toDateString();

        // Par département
        $parDepartement = Departement::select('departements.id', 'departements.nom')
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
            ->get()
            ->map(fn($d) => ['label' => $d->nom, 'value' => $d->employes_actifs_count]);

        // Par type de contrat
        $parTypeContrat = Contrat::whereDate('date_debut', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('date_fin')->orWhereDate('date_fin', '>=', $now);
            })
            ->select('type_contrat', DB::raw('count(*) as total'))
            ->groupBy('type_contrat')
            ->get()
            ->map(fn($c) => ['label' => $c->type_contrat ?? 'Non défini', 'value' => $c->total]);

        // Par genre (à partir de la date de naissance - approximation basée sur le prénom)
        // Note: Si vous avez un champ genre, utilisez-le à la place
        $parGenre = $this->getGenreDistribution();

        // Par tranche d'âge
        $parAge = $this->getAgeDistribution();

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
    private function getGenreDistribution(): array
    {
        // Si vous avez un champ genre dans la table employes, utilisez-le
        // Sinon, cette méthode retourne un placeholder
        return [
            ['label' => 'Non renseigné', 'value' => Employe::count()],
        ];
    }

    /**
     * Distribution par tranche d'âge
     */
    private function getAgeDistribution(): array
    {
        $now = now();
        
        $tranches = [
            ['label' => '< 25 ans', 'min' => 0, 'max' => 24, 'value' => 0],
            ['label' => '25-34 ans', 'min' => 25, 'max' => 34, 'value' => 0],
            ['label' => '35-44 ans', 'min' => 35, 'max' => 44, 'value' => 0],
            ['label' => '45-54 ans', 'min' => 45, 'max' => 54, 'value' => 0],
            ['label' => '55+ ans', 'min' => 55, 'max' => 100, 'value' => 0],
        ];

        $employes = Employe::whereNotNull('date_naissance')
            ->whereHas('contrats', function ($q) {
                $now = now()->toDateString();
                $q->whereDate('date_debut', '<=', $now)
                  ->where(function ($q2) use ($now) {
                      $q2->whereNull('date_fin')->orWhereDate('date_fin', '>=', $now);
                  });
            })
            ->get();

        foreach ($employes as $emp) {
            $age = $now->diffInYears($emp->date_naissance);
            foreach ($tranches as &$tranche) {
                if ($age >= $tranche['min'] && $age <= $tranche['max']) {
                    $tranche['value']++;
                    break;
                }
            }
        }

        return array_map(fn($t) => ['label' => $t['label'], 'value' => $t['value']], $tranches);
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

            // Entrées du mois
            $entrees = Employe::whereBetween('date_embauche', [$current, $moisFin])->count();

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
