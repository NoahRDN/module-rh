<?php

namespace App\Services;

use App\Models\Employe;
use App\Models\Pointage;
use App\Models\Paie;
use App\Models\DemandeConge;
use App\Models\Contrat;
use App\Models\WorktimeSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnomalyDetectionService
{
    private ?WorktimeSetting $worktimeSetting = null;

    public function __construct()
    {
        $this->worktimeSetting = WorktimeSetting::first();
    }

    /**
     * Détecter toutes les anomalies
     */
    public function detecterToutesAnomalies(array $options = []): array
    {
        $dateDebut = $options['date_debut'] ?? Carbon::now()->subMonth();
        $dateFin = $options['date_fin'] ?? Carbon::now();
        $employeId = $options['employe_id'] ?? null;

        $anomalies = [];

        // 1. Anomalies de pointage
        $anomalies['pointage'] = $this->detecterAnomaliesPointage($dateDebut, $dateFin, $employeId);

        // 2. Anomalies de paie
        $anomalies['paie'] = $this->detecterAnomaliesPaie($dateDebut, $dateFin, $employeId);

        // 3. Anomalies de congés
        $anomalies['conges'] = $this->detecterAnomaliesConges($dateDebut, $dateFin, $employeId);

        // 4. Anomalies de contrat
        $anomalies['contrats'] = $this->detecterAnomaliesContrats($employeId);

        // 5. Anomalies d'heures de travail
        $anomalies['heures'] = $this->detecterAnomaliesHeures($dateDebut, $dateFin, $employeId);

        // Statistiques globales
        $totalAnomalies = 0;
        $parGravite = ['critique' => 0, 'haute' => 0, 'moyenne' => 0, 'basse' => 0];

        foreach ($anomalies as $type => $liste) {
            $totalAnomalies += count($liste);
            foreach ($liste as $anomalie) {
                $parGravite[$anomalie['gravite']]++;
            }
        }

        return [
            'anomalies' => $anomalies,
            'statistiques' => [
                'total' => $totalAnomalies,
                'par_gravite' => $parGravite,
                'par_type' => [
                    'pointage' => count($anomalies['pointage']),
                    'paie' => count($anomalies['paie']),
                    'conges' => count($anomalies['conges']),
                    'contrats' => count($anomalies['contrats']),
                    'heures' => count($anomalies['heures']),
                ],
            ],
            'periode' => [
                'debut' => Carbon::parse($dateDebut)->format('Y-m-d'),
                'fin' => Carbon::parse($dateFin)->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Détecter les anomalies de pointage
     */
    public function detecterAnomaliesPointage($dateDebut, $dateFin, $employeId = null): array
    {
        $anomalies = [];
        $heureDebut = $this->worktimeSetting?->start_time ?? '08:00';
        $heureFin = $this->worktimeSetting?->end_time ?? '17:00';

        // Récupérer les pointages
        $query = Pointage::whereBetween('pointe_a', [$dateDebut, $dateFin])
            ->with('employe');
        
        if ($employeId) {
            $query->where('employe_id', $employeId);
        }

        $pointages = $query->orderBy('employe_id')->orderBy('pointe_a')->get();
        $pointagesParEmployeParJour = $pointages->groupBy(function ($p) {
            return $p->employe_id . '_' . Carbon::parse($p->pointe_a)->format('Y-m-d');
        });

        foreach ($pointagesParEmployeParJour as $key => $pointagesJour) {
            [$employeIdKey, $date] = explode('_', $key);
            $employe = $pointagesJour->first()->employe;

            $entrees = $pointagesJour->where('type', 'entree');
            $sorties = $pointagesJour->where('type', 'sortie');

            // 1. Pointage manquant (entrée sans sortie ou vice versa)
            if ($entrees->count() !== $sorties->count()) {
                $anomalies[] = [
                    'type' => 'pointage_incomplet',
                    'gravite' => 'moyenne',
                    'employe' => $this->formatEmploye($employe),
                    'date' => $date,
                    'message' => 'Pointage incomplet: ' . $entrees->count() . ' entrée(s), ' . $sorties->count() . ' sortie(s)',
                    'details' => [
                        'entrees' => $entrees->count(),
                        'sorties' => $sorties->count(),
                    ],
                ];
            }

            // 2. Retard significatif (> 30 minutes)
            if ($entrees->isNotEmpty()) {
                $premiereEntree = Carbon::parse($entrees->first()->pointe_a);
                $heureNormale = Carbon::parse($date . ' ' . $heureDebut);
                $retardMinutes = $premiereEntree->diffInMinutes($heureNormale, false);

                if ($retardMinutes > 30) {
                    $anomalies[] = [
                        'type' => 'retard_significatif',
                        'gravite' => $retardMinutes > 60 ? 'haute' : 'moyenne',
                        'employe' => $this->formatEmploye($employe),
                        'date' => $date,
                        'message' => 'Retard de ' . $retardMinutes . ' minutes',
                        'details' => [
                            'heure_arrivee' => $premiereEntree->format('H:i'),
                            'heure_normale' => $heureDebut,
                            'retard_minutes' => $retardMinutes,
                        ],
                    ];
                }
            }

            // 3. Départ anticipé significatif (> 30 minutes avant)
            if ($sorties->isNotEmpty()) {
                $derniereSortie = Carbon::parse($sorties->last()->pointe_a);
                $heureNormaleFin = Carbon::parse($date . ' ' . $heureFin);
                $anticipationMinutes = $heureNormaleFin->diffInMinutes($derniereSortie, false);

                if ($anticipationMinutes > 30) {
                    $anomalies[] = [
                        'type' => 'depart_anticipe',
                        'gravite' => 'moyenne',
                        'employe' => $this->formatEmploye($employe),
                        'date' => $date,
                        'message' => 'Départ anticipé de ' . $anticipationMinutes . ' minutes',
                        'details' => [
                            'heure_depart' => $derniereSortie->format('H:i'),
                            'heure_normale' => $heureFin,
                            'anticipation_minutes' => $anticipationMinutes,
                        ],
                    ];
                }
            }

            // 4. Heures excessives (> 12h/jour)
            if ($entrees->isNotEmpty() && $sorties->isNotEmpty()) {
                $premiereEntree = Carbon::parse($entrees->first()->pointe_a);
                $derniereSortie = Carbon::parse($sorties->last()->pointe_a);
                $heuresTravaillees = $premiereEntree->diffInHours($derniereSortie);

                if ($heuresTravaillees > 12) {
                    $anomalies[] = [
                        'type' => 'heures_excessives',
                        'gravite' => 'haute',
                        'employe' => $this->formatEmploye($employe),
                        'date' => $date,
                        'message' => 'Plus de ' . $heuresTravaillees . ' heures travaillées',
                        'details' => [
                            'debut' => $premiereEntree->format('H:i'),
                            'fin' => $derniereSortie->format('H:i'),
                            'heures' => $heuresTravaillees,
                        ],
                    ];
                }
            }

            // 5. Pointage suspect (weekend ou jour férié)
            $carbonDate = Carbon::parse($date);
            if ($carbonDate->isWeekend()) {
                $anomalies[] = [
                    'type' => 'pointage_weekend',
                    'gravite' => 'basse',
                    'employe' => $this->formatEmploye($employe),
                    'date' => $date,
                    'message' => 'Pointage effectué un ' . $carbonDate->dayName,
                    'details' => [
                        'jour' => $carbonDate->dayName,
                    ],
                ];
            }
        }

        // 6. Absence non justifiée (aucun pointage sur une journée ouvrable)
        $this->detecterAbsencesNonJustifiees($dateDebut, $dateFin, $employeId, $anomalies);

        return $anomalies;
    }

    /**
     * Détecter les absences non justifiées
     */
    private function detecterAbsencesNonJustifiees($dateDebut, $dateFin, $employeId, &$anomalies): void
    {
        $employes = $employeId 
            ? Employe::where('id', $employeId)->get()
            : Employe::whereHas('contrats', fn($q) => $q->where('statut', 'actif'))->get();

        $joursOuvrables = [];
        $current = Carbon::parse($dateDebut);
        $fin = Carbon::parse($dateFin);

        while ($current <= $fin) {
            if (!$current->isWeekend()) {
                $joursOuvrables[] = $current->format('Y-m-d');
            }
            $current->addDay();
        }

        foreach ($employes as $employe) {
            $pointagesEmploye = Pointage::where('employe_id', $employe->id)
                ->whereBetween('pointe_a', [$dateDebut, $dateFin])
                ->get()
                ->groupBy(fn($p) => Carbon::parse($p->pointe_a)->format('Y-m-d'))
                ->keys()
                ->toArray();

            $congesApprouves = DemandeConge::where('employe_id', $employe->id)
                ->where('statut', 'approuvee')
                ->where(function ($q) use ($dateDebut, $dateFin) {
                    $q->whereBetween('date_debut', [$dateDebut, $dateFin])
                      ->orWhereBetween('date_fin', [$dateDebut, $dateFin]);
                })
                ->get();

            $joursConge = [];
            foreach ($congesApprouves as $conge) {
                $debut = Carbon::parse($conge->date_debut);
                $fin = Carbon::parse($conge->date_fin);
                while ($debut <= $fin) {
                    $joursConge[] = $debut->format('Y-m-d');
                    $debut->addDay();
                }
            }

            foreach ($joursOuvrables as $jour) {
                if (!in_array($jour, $pointagesEmploye) && !in_array($jour, $joursConge)) {
                    // Vérifier que l'employé était embauché ce jour-là
                    if ($employe->date_embauche && Carbon::parse($employe->date_embauche) <= Carbon::parse($jour)) {
                        $anomalies[] = [
                            'type' => 'absence_non_justifiee',
                            'gravite' => 'haute',
                            'employe' => $this->formatEmploye($employe),
                            'date' => $jour,
                            'message' => 'Absence non justifiée',
                            'details' => [
                                'pointage_trouve' => false,
                                'conge_trouve' => false,
                            ],
                        ];
                    }
                }
            }
        }
    }

    /**
     * Détecter les anomalies de paie
     */
    public function detecterAnomaliesPaie($dateDebut, $dateFin, $employeId = null): array
    {
        $anomalies = [];

        $query = Paie::with('employe')
            ->whereBetween('created_at', [$dateDebut, $dateFin]);
        
        if ($employeId) {
            $query->where('employe_id', $employeId);
        }

        $paies = $query->get();
        $paiesParEmploye = $paies->groupBy('employe_id');

        foreach ($paiesParEmploye as $empId => $paiesEmploye) {
            $employe = $paiesEmploye->first()->employe;
            
            if ($paiesEmploye->count() < 2) {
                continue;
            }

            $paiesTriees = $paiesEmploye->sortBy('mois')->values();

            for ($i = 1; $i < $paiesTriees->count(); $i++) {
                $paieActuelle = $paiesTriees[$i];
                $paiePrecedente = $paiesTriees[$i - 1];

                // 1. Variation de salaire significative (> 20%)
                if ($paiePrecedente->net_a_payer > 0) {
                    $variation = (($paieActuelle->net_a_payer - $paiePrecedente->net_a_payer) / $paiePrecedente->net_a_payer) * 100;

                    if (abs($variation) > 20) {
                        $anomalies[] = [
                            'type' => 'variation_salaire_importante',
                            'gravite' => abs($variation) > 50 ? 'critique' : 'haute',
                            'employe' => $this->formatEmploye($employe),
                            'date' => $paieActuelle->mois,
                            'message' => sprintf('Variation de %.1f%% du salaire net', $variation),
                            'details' => [
                                'mois_precedent' => $paiePrecedente->mois,
                                'net_precedent' => $paiePrecedente->net_a_payer,
                                'net_actuel' => $paieActuelle->net_a_payer,
                                'variation_pourcentage' => round($variation, 1),
                            ],
                        ];
                    }
                }

                // 2. Heures supplémentaires excessives (> 50h/mois)
                if ($paieActuelle->heures_supplementaires > 50) {
                    $anomalies[] = [
                        'type' => 'heures_sup_excessives',
                        'gravite' => $paieActuelle->heures_supplementaires > 80 ? 'critique' : 'haute',
                        'employe' => $this->formatEmploye($employe),
                        'date' => $paieActuelle->mois,
                        'message' => $paieActuelle->heures_supplementaires . ' heures supplémentaires',
                        'details' => [
                            'heures_sup' => $paieActuelle->heures_supplementaires,
                            'montant_hs' => $paieActuelle->montant_hs,
                        ],
                    ];
                }
            }

            // 3. Salaire net négatif ou zéro
            foreach ($paiesEmploye as $paie) {
                if ($paie->net_a_payer <= 0) {
                    $anomalies[] = [
                        'type' => 'salaire_negatif',
                        'gravite' => 'critique',
                        'employe' => $this->formatEmploye($employe),
                        'date' => $paie->mois,
                        'message' => 'Salaire net nul ou négatif',
                        'details' => [
                            'brut' => $paie->total_brut,
                            'retenues' => $paie->total_retenues,
                            'net' => $paie->net_a_payer,
                        ],
                    ];
                }

                // 4. Retenues supérieures au brut
                if ($paie->total_retenues > $paie->total_brut) {
                    $anomalies[] = [
                        'type' => 'retenues_excessives',
                        'gravite' => 'critique',
                        'employe' => $this->formatEmploye($employe),
                        'date' => $paie->mois,
                        'message' => 'Retenues supérieures au salaire brut',
                        'details' => [
                            'brut' => $paie->total_brut,
                            'retenues' => $paie->total_retenues,
                            'ecart' => $paie->total_retenues - $paie->total_brut,
                        ],
                    ];
                }
            }
        }

        // 5. Paie manquante pour employés actifs
        $moisCourant = Carbon::now()->format('Y-m');
        $employesActifs = Employe::whereHas('contrats', fn($q) => $q->where('statut', 'actif'))->get();

        foreach ($employesActifs as $employe) {
            $paieMois = Paie::where('employe_id', $employe->id)
                ->where('mois', $moisCourant)
                ->exists();

            if (!$paieMois && Carbon::now()->day > 25) {
                $anomalies[] = [
                    'type' => 'paie_manquante',
                    'gravite' => 'haute',
                    'employe' => $this->formatEmploye($employe),
                    'date' => $moisCourant,
                    'message' => 'Paie non générée pour le mois en cours',
                    'details' => [],
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Détecter les anomalies de congés
     */
    public function detecterAnomaliesConges($dateDebut, $dateFin, $employeId = null): array
    {
        $anomalies = [];

        $query = DemandeConge::with(['employe', 'typeConge'])
            ->whereBetween('created_at', [$dateDebut, $dateFin]);
        
        if ($employeId) {
            $query->where('employe_id', $employeId);
        }

        $demandes = $query->get();

        foreach ($demandes as $demande) {
            $employe = $demande->employe;

            // 1. Congé demandé dans le passé
            if ($demande->date_debut < Carbon::now()->startOfDay() && $demande->statut === 'en_attente') {
                $anomalies[] = [
                    'type' => 'conge_passe_non_traite',
                    'gravite' => 'haute',
                    'employe' => $this->formatEmploye($employe),
                    'date' => $demande->date_debut->format('Y-m-d'),
                    'message' => 'Demande de congé dans le passé non traitée',
                    'details' => [
                        'demande_id' => $demande->id,
                        'date_debut' => $demande->date_debut->format('Y-m-d'),
                        'date_fin' => $demande->date_fin->format('Y-m-d'),
                        'statut' => $demande->statut,
                    ],
                ];
            }

            // 2. Durée de congé exceptionnellement longue (> 20 jours consécutifs)
            if ($demande->jours_demandes > 20) {
                $anomalies[] = [
                    'type' => 'conge_tres_long',
                    'gravite' => 'moyenne',
                    'employe' => $this->formatEmploye($employe),
                    'date' => $demande->date_debut->format('Y-m-d'),
                    'message' => 'Congé de ' . $demande->jours_demandes . ' jours',
                    'details' => [
                        'demande_id' => $demande->id,
                        'jours' => $demande->jours_demandes,
                        'type_conge' => $demande->typeConge?->nom,
                    ],
                ];
            }

            // 3. Chevauchement de congés (même employé, dates qui se chevauchent)
            $chevauchements = DemandeConge::where('employe_id', $demande->employe_id)
                ->where('id', '!=', $demande->id)
                ->where('statut', 'approuvee')
                ->where(function ($q) use ($demande) {
                    $q->whereBetween('date_debut', [$demande->date_debut, $demande->date_fin])
                      ->orWhereBetween('date_fin', [$demande->date_debut, $demande->date_fin]);
                })
                ->count();

            if ($chevauchements > 0 && $demande->statut === 'approuvee') {
                $anomalies[] = [
                    'type' => 'chevauchement_conges',
                    'gravite' => 'critique',
                    'employe' => $this->formatEmploye($employe),
                    'date' => $demande->date_debut->format('Y-m-d'),
                    'message' => 'Chevauchement avec d\'autres congés approuvés',
                    'details' => [
                        'demande_id' => $demande->id,
                        'nb_chevauchements' => $chevauchements,
                    ],
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Détecter les anomalies de contrats
     */
    public function detecterAnomaliesContrats($employeId = null): array
    {
        $anomalies = [];

        $query = Contrat::with('employe');
        
        if ($employeId) {
            $query->where('employe_id', $employeId);
        }

        $contrats = $query->get();
        $contratsParEmploye = $contrats->groupBy('employe_id');

        foreach ($contratsParEmploye as $empId => $contratsEmploye) {
            $employe = $contratsEmploye->first()->employe;
            $contratsActifs = $contratsEmploye->where('statut', 'actif');

            // 1. Plusieurs contrats actifs
            if ($contratsActifs->count() > 1) {
                $anomalies[] = [
                    'type' => 'contrats_multiples_actifs',
                    'gravite' => 'critique',
                    'employe' => $this->formatEmploye($employe),
                    'date' => Carbon::now()->format('Y-m-d'),
                    'message' => $contratsActifs->count() . ' contrats actifs simultanément',
                    'details' => [
                        'contrats' => $contratsActifs->pluck('numero')->toArray(),
                    ],
                ];
            }

            // 2. Contrat expiré non renouvelé
            foreach ($contratsEmploye as $contrat) {
                if ($contrat->date_fin && $contrat->date_fin < now() && $contrat->statut === 'actif') {
                    $anomalies[] = [
                        'type' => 'contrat_expire',
                        'gravite' => 'haute',
                        'employe' => $this->formatEmploye($employe),
                        'date' => $contrat->date_fin->format('Y-m-d'),
                        'message' => 'Contrat expiré depuis ' . $contrat->date_fin->diffInDays(now()) . ' jours',
                        'details' => [
                            'contrat_id' => $contrat->id,
                            'numero' => $contrat->numero,
                            'date_fin' => $contrat->date_fin->format('Y-m-d'),
                        ],
                    ];
                }

                // 3. Période d'essai non validée
                if ($contrat->periode_essai_fin && $contrat->periode_essai_fin < now()) {
                    // On pourrait vérifier si la validation a été faite
                    $joursDepuisFin = Carbon::parse($contrat->periode_essai_fin)->diffInDays(now());
                    if ($joursDepuisFin > 7 && $joursDepuisFin < 30) {
                        $anomalies[] = [
                            'type' => 'periode_essai_non_validee',
                            'gravite' => 'moyenne',
                            'employe' => $this->formatEmploye($employe),
                            'date' => $contrat->periode_essai_fin->format('Y-m-d'),
                            'message' => 'Période d\'essai terminée depuis ' . $joursDepuisFin . ' jours',
                            'details' => [
                                'contrat_id' => $contrat->id,
                                'fin_essai' => $contrat->periode_essai_fin->format('Y-m-d'),
                            ],
                        ];
                    }
                }

                // 4. Contrat sans date de fin pour CDD
                if ($contrat->type_contrat === 'CDD' && !$contrat->date_fin) {
                    $anomalies[] = [
                        'type' => 'cdd_sans_date_fin',
                        'gravite' => 'haute',
                        'employe' => $this->formatEmploye($employe),
                        'date' => $contrat->date_debut->format('Y-m-d'),
                        'message' => 'CDD sans date de fin définie',
                        'details' => [
                            'contrat_id' => $contrat->id,
                            'numero' => $contrat->numero,
                        ],
                    ];
                }
            }

            // 5. Employé sans contrat actif
            if ($contratsActifs->isEmpty() && $contratsEmploye->isNotEmpty()) {
                $anomalies[] = [
                    'type' => 'employe_sans_contrat_actif',
                    'gravite' => 'critique',
                    'employe' => $this->formatEmploye($employe),
                    'date' => Carbon::now()->format('Y-m-d'),
                    'message' => 'Aucun contrat actif',
                    'details' => [
                        'nb_contrats_total' => $contratsEmploye->count(),
                    ],
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Détecter les anomalies d'heures de travail
     */
    public function detecterAnomaliesHeures($dateDebut, $dateFin, $employeId = null): array
    {
        $anomalies = [];
        $heuresMax = $this->worktimeSetting?->weekly_hours ?? 40;

        $query = Pointage::whereBetween('pointe_a', [$dateDebut, $dateFin])
            ->with('employe');
        
        if ($employeId) {
            $query->where('employe_id', $employeId);
        }

        $pointages = $query->get();
        $pointagesParEmploye = $pointages->groupBy('employe_id');

        foreach ($pointagesParEmploye as $empId => $pointagesEmploye) {
            $employe = $pointagesEmploye->first()->employe;

            // Calculer les heures par semaine
            $pointagesParSemaine = $pointagesEmploye->groupBy(function ($p) {
                return Carbon::parse($p->pointe_a)->startOfWeek()->format('Y-m-d');
            });

            foreach ($pointagesParSemaine as $debutSemaine => $pointagesSemaine) {
                $heuresSemaine = $this->calculerHeuresSemaine($pointagesSemaine);

                // 1. Heures hebdomadaires excessives (> limite légale + 25%)
                if ($heuresSemaine > $heuresMax * 1.25) {
                    $anomalies[] = [
                        'type' => 'heures_hebdo_excessives',
                        'gravite' => $heuresSemaine > $heuresMax * 1.5 ? 'critique' : 'haute',
                        'employe' => $this->formatEmploye($employe),
                        'date' => $debutSemaine,
                        'message' => sprintf('%.1f heures travaillées (max: %d)', $heuresSemaine, $heuresMax),
                        'details' => [
                            'heures_travaillees' => round($heuresSemaine, 1),
                            'heures_max' => $heuresMax,
                            'depassement' => round($heuresSemaine - $heuresMax, 1),
                        ],
                    ];
                }

                // 2. Heures insuffisantes (< 50% du temps normal)
                if ($heuresSemaine < $heuresMax * 0.5 && $heuresSemaine > 0) {
                    $anomalies[] = [
                        'type' => 'heures_insuffisantes',
                        'gravite' => 'moyenne',
                        'employe' => $this->formatEmploye($employe),
                        'date' => $debutSemaine,
                        'message' => sprintf('Seulement %.1f heures travaillées', $heuresSemaine),
                        'details' => [
                            'heures_travaillees' => round($heuresSemaine, 1),
                            'heures_attendues' => $heuresMax,
                        ],
                    ];
                }
            }
        }

        return $anomalies;
    }

    /**
     * Calculer les heures travaillées dans une semaine
     */
    private function calculerHeuresSemaine(Collection $pointages): float
    {
        $heuresTotal = 0;
        $pointagesParJour = $pointages->groupBy(fn($p) => Carbon::parse($p->pointe_a)->format('Y-m-d'));

        foreach ($pointagesParJour as $date => $pointagesJour) {
            $entrees = $pointagesJour->where('type', 'entree')->sortBy('pointe_a');
            $sorties = $pointagesJour->where('type', 'sortie')->sortBy('pointe_a');

            if ($entrees->isNotEmpty() && $sorties->isNotEmpty()) {
                $entree = Carbon::parse($entrees->first()->pointe_a);
                $sortie = Carbon::parse($sorties->last()->pointe_a);
                $heuresTotal += $entree->diffInMinutes($sortie) / 60;
            }
        }

        return $heuresTotal;
    }

    /**
     * Formater les données d'un employé
     */
    private function formatEmploye(?Employe $employe): ?array
    {
        if (!$employe) {
            return null;
        }

        return [
            'id' => $employe->id,
            'matricule' => $employe->matricule,
            'nom' => $employe->nom,
            'prenom' => $employe->prenom,
        ];
    }

    /**
     * Obtenir les statistiques d'anomalies par période
     */
    public function getStatistiquesAnomalies(string $periode = 'mois'): array
    {
        $dateDebut = match ($periode) {
            'semaine' => Carbon::now()->startOfWeek(),
            'mois' => Carbon::now()->startOfMonth(),
            'trimestre' => Carbon::now()->startOfQuarter(),
            'annee' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        $analyse = $this->detecterToutesAnomalies([
            'date_debut' => $dateDebut,
            'date_fin' => Carbon::now(),
        ]);

        return $analyse['statistiques'];
    }

    /**
     * Obtenir les alertes critiques
     */
    public function getAlertesCritiques(): array
    {
        $analyse = $this->detecterToutesAnomalies([
            'date_debut' => Carbon::now()->subMonth(),
            'date_fin' => Carbon::now(),
        ]);

        $critiques = [];
        foreach ($analyse['anomalies'] as $type => $anomalies) {
            foreach ($anomalies as $anomalie) {
                if (in_array($anomalie['gravite'], ['critique', 'haute'])) {
                    $anomalie['categorie'] = $type;
                    $critiques[] = $anomalie;
                }
            }
        }

        usort($critiques, function ($a, $b) {
            $ordre = ['critique' => 0, 'haute' => 1, 'moyenne' => 2, 'basse' => 3];
            return $ordre[$a['gravite']] <=> $ordre[$b['gravite']];
        });

        return array_slice($critiques, 0, 20);
    }
}
