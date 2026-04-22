<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlerteSetting;
use App\Models\CalendrierEvenement;
use App\Models\Contrat;
use App\Models\DemandeConge;
use App\Models\JourFerie;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AlerteController extends Controller
{
    public function index()
    {
        try {
            $alerts = [];
            $now = Carbon::now();
            $today = $now->copy()->startOfDay();

            // Charger tous les paramètres pour permettre un fallback par défaut si certains codes manquent.
            $settings = AlerteSetting::query()->get()->keyBy('code');

            // 1. Alertes de fin de contrat
            if ($setting = $this->resolveSetting($settings, 'fin_contrat', [
                'seuil_jours' => 30,
                'niveau' => 'warning',
            ])) {
                $seuilJours = $setting->seuil_jours ?? 30;
                
                $contratsExpirants = Contrat::with('employe')
                    ->whereNotNull('date_fin')
                    ->whereDate('date_fin', '<=', $today->copy()->addDays($seuilJours)->toDateString())
                    ->whereDate('date_fin', '>=', $today->toDateString())
                    ->get();
                
                foreach ($contratsExpirants as $contrat) {
                    $joursRestants = $today->diffInDays($contrat->date_fin->copy()->startOfDay());
                    $alerts[] = [
                        'type' => 'fin_contrat',
                        'level' => $joursRestants <= 7 ? 'danger' : $setting->niveau,
                        'message' => "Contrat de {$contrat->employe?->nom} {$contrat->employe?->prenom} expire dans {$joursRestants} jours",
                        'contrat_id' => $contrat->id,
                        'employe_id' => $contrat->employe_id,
                        'date_fin' => $contrat->date_fin->format('Y-m-d'),
                    ];
                }
            }

            // 2. Congés non pris
            if ($setting = $this->resolveSetting($settings, 'conges_non_pris', [
                'seuil_nombre' => 15,
                'niveau' => 'info',
            ])) {
                $seuilJours = $setting->seuil_nombre ?? 15;
                
                // Utiliser la vue solde_conges si elle existe
                try {
                    $soldesEleves = DB::table('view_solde_conges')
                        ->where('solde', '>=', $seuilJours)
                        ->get();
                    
                    foreach ($soldesEleves as $solde) {
                        $alerts[] = [
                            'type' => 'conges_non_pris',
                            'level' => $setting->niveau,
                            'message' => "Employé ID {$solde->employe_id} a {$solde->solde} jours de congés non pris",
                            'employe_id' => $solde->employe_id,
                            'solde' => $solde->solde,
                        ];
                    }
                } catch (\Exception $e) {
                    // Vue non disponible, ignorer cette alerte
                }
            }

            // 3. Demandes en attente depuis X heures
            if ($setting = $this->resolveSetting($settings, 'conge_en_attente', [
                'seuil_jours' => 2,
                'niveau' => 'warning',
            ])) {
                $seuilHeures = ($setting->seuil_jours ?? 2) * 24;
                
                $pending = DemandeConge::with('employe')
                    ->whereNot('statut', 'rh_valide')
                    ->where('created_at', '<', $now->copy()->subHours($seuilHeures))
                    ->get();
                
                foreach ($pending as $d) {
                    $alerts[] = [
                        'type' => 'conge_en_attente',
                        'level' => $setting->niveau,
                        'message' => "Demande en attente >{$seuilHeures}h pour {$d->employe?->nom} {$d->employe?->prenom}",
                        'demande_id' => $d->id,
                        'employe' => $this->employePayload($d->employe),
                    ];
                }
            }

            // 4. Congés qui commencent bientôt mais non validés
            if ($setting = $this->resolveSetting($settings, 'conge_proche', [
                'seuil_jours' => 2,
                'niveau' => 'danger',
            ])) {
                $seuilJours = $setting->seuil_jours ?? 2;
                
                $startingSoon = DemandeConge::with('employe')
                    ->whereNot('statut', 'rh_valide')
                    ->whereDate('date_debut', '<=', $today->copy()->addDays($seuilJours)->toDateString())
                    ->whereDate('date_debut', '>=', $today->toDateString())
                    ->get();
                
                foreach ($startingSoon as $d) {
                    $alerts[] = [
                        'type' => 'conge_proche',
                        'level' => $setting->niveau,
                        'message' => "Le congé de {$d->employe?->nom} {$d->employe?->prenom} débute bientôt et n'est pas validé",
                        'demande_id' => $d->id,
                        'employe' => $this->employePayload($d->employe),
                    ];
                }
            }

            // 5. Absences maladie fréquentes
            if ($setting = $this->resolveSetting($settings, 'absences_maladie', [
                'seuil_nombre' => 4,
                'periode_jours' => 60,
                'niveau' => 'warning',
            ])) {
                $seuilNombre = $setting->seuil_nombre ?? 4;
                $periodeJours = $setting->periode_jours ?? 60;
                
                $maladieCounts = DemandeConge::with(['employe', 'typeConge'])
                    ->whereHas('typeConge', function ($q) {
                        $q->where('libelle', 'like', '%malad%')
                          ->orWhere('code', 'like', '%malad%');
                    })
                    ->whereDate('date_debut', '>=', $now->copy()->subDays($periodeJours)->toDateString())
                    ->get()
                    ->groupBy('employe_id');
                
                foreach ($maladieCounts as $empId => $list) {
                    if ($list->count() >= $seuilNombre) {
                        $emp = $list->first()->employe;
                        $alerts[] = [
                            'type' => 'absences_maladie',
                            'level' => $setting->niveau,
                            'message' => "{$emp?->nom} {$emp?->prenom} a {$list->count()} congés maladie sur {$periodeJours} jours",
                            'employe_id' => $empId,
                            'employe' => $this->employePayload($emp),
                        ];
                    }
                }
            }

            // 6. Congés exceptionnels fréquents
            if ($setting = $this->resolveSetting($settings, 'absences_exceptionnelles', [
                'seuil_nombre' => 3,
                'periode_jours' => 90,
                'niveau' => 'warning',
            ])) {
                $seuilNombre = $setting->seuil_nombre ?? 3;
                $periodeJours = $setting->periode_jours ?? 90;
                
                $exceptionCounts = DemandeConge::with(['employe', 'typeConge'])
                    ->whereHas('typeConge', function ($q) {
                        $q->where('libelle', 'like', '%exception%')
                          ->orWhere('code', 'like', '%exception%');
                    })
                    ->whereDate('date_debut', '>=', $now->copy()->subDays($periodeJours)->toDateString())
                    ->get()
                    ->groupBy('employe_id');
                
                foreach ($exceptionCounts as $empId => $list) {
                    if ($list->count() >= $seuilNombre) {
                        $emp = $list->first()->employe;
                        $alerts[] = [
                            'type' => 'absences_exceptionnelles',
                            'level' => $setting->niveau,
                            'message' => "{$emp?->nom} {$emp?->prenom} a {$list->count()} congés exceptionnels sur {$periodeJours} jours",
                            'employe_id' => $empId,
                            'employe' => $this->employePayload($emp),
                        ];
                    }
                }
            }

            // 7. Jours fériés proches
            if ($setting = $this->resolveSetting($settings, 'ferie_proche', [
                'seuil_jours' => 7,
                'niveau' => 'info',
            ])) {
                $seuilJours = $setting->seuil_jours ?? 7;
                $until = $today->copy()->addDays($seuilJours);

                $feriesPonctuels = JourFerie::query()
                    ->where('recurrent', false)
                    ->whereDate('date', '>=', $today->toDateString())
                    ->whereDate('date', '<=', $until->toDateString())
                    ->get();

                foreach ($feriesPonctuels as $ferie) {
                    $joursRestants = $today->diffInDays($ferie->date->copy()->startOfDay());
                    $alerts[] = [
                        'type' => 'ferie_proche',
                        'level' => $this->proximityLevel($setting->niveau, $joursRestants),
                        'message' => "Jour férié \"{$ferie->nom}\" dans {$joursRestants} jour(s)",
                        'date_debut' => $ferie->date->format('Y-m-d'),
                        'description' => $ferie->nom,
                    ];
                }

                $feriesRecurrents = JourFerie::query()
                    ->where('recurrent', true)
                    ->get();

                foreach ($feriesRecurrents as $ferie) {
                    $occurrence = $this->nextRecurringOccurrence($ferie->date, $today);
                    if (!$occurrence || $occurrence->gt($until)) {
                        continue;
                    }

                    $joursRestants = $today->diffInDays($occurrence->copy()->startOfDay());
                    $alerts[] = [
                        'type' => 'ferie_proche',
                        'level' => $this->proximityLevel($setting->niveau, $joursRestants),
                        'message' => "Jour férié \"{$ferie->nom}\" dans {$joursRestants} jour(s)",
                        'date_debut' => $occurrence->format('Y-m-d'),
                        'description' => $ferie->nom,
                    ];
                }
            }

            // 8. Événements RH proches
            if ($setting = $this->resolveSetting($settings, 'evenement_rh_proche', [
                'seuil_jours' => 7,
                'niveau' => 'warning',
            ])) {
                $seuilJours = $setting->seuil_jours ?? 7;

                $rhEvents = CalendrierEvenement::with('employe')
                    ->where('type', 'rh')
                    ->whereDate('date_debut', '>=', $today->toDateString())
                    ->whereDate('date_debut', '<=', $today->copy()->addDays($seuilJours)->toDateString())
                    ->orderBy('date_debut')
                    ->get();

                foreach ($rhEvents as $event) {
                    $joursRestants = $today->diffInDays($event->date_debut->copy()->startOfDay());
                    $eventLabel = $event->description ?: 'Événement RH';
                    $targetLabel = $event->employe
                        ? " pour {$event->employe->nom} {$event->employe->prenom}"
                        : '';

                    $alerts[] = [
                        'type' => 'evenement_rh_proche',
                        'level' => $this->proximityLevel($setting->niveau, $joursRestants),
                        'message' => "Événement RH \"{$eventLabel}\" prévu{$targetLabel} dans {$joursRestants} jour(s)",
                        'evenement_id' => $event->id,
                        'date_debut' => $event->date_debut->format('Y-m-d'),
                        'description' => $eventLabel,
                        'employe_id' => $event->employe_id,
                        'employe' => $this->employePayload($event->employe),
                    ];
                }
            }

            // Trier par niveau de criticité
            usort($alerts, function ($a, $b) {
                $priority = ['danger' => 0, 'warning' => 1, 'info' => 2];
                return ($priority[$a['level']] ?? 3) <=> ($priority[$b['level']] ?? 3);
            });

            return response()->json(['data' => $alerts]);
        } catch (\Throwable $e) {
            Log::error('Erreur generation alertes', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    private function resolveSetting($settings, string $code, array $defaults): ?object
    {
        if ($settings->has($code)) {
            $setting = $settings->get($code);
            return $setting->actif ? $setting : null;
        }

        return (object) array_merge([
            'code' => $code,
            'seuil_jours' => null,
            'seuil_nombre' => null,
            'periode_jours' => null,
            'niveau' => 'warning',
            'actif' => true,
        ], $defaults);
    }

    private function employePayload($employe): ?array
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

    private function proximityLevel(?string $baseLevel, int $days): string
    {
        if ($days <= 1) {
            return 'danger';
        }

        if ($days <= 3 && $baseLevel === 'info') {
            return 'warning';
        }

        return $baseLevel ?: 'warning';
    }

    private function nextRecurringOccurrence(Carbon $template, Carbon $reference): ?Carbon
    {
        $referenceStart = $reference->copy()->startOfDay();

        foreach ([$referenceStart->year, $referenceStart->year + 1] as $year) {
            try {
                $candidate = Carbon::createFromDate($year, (int) $template->format('m'), (int) $template->format('d'));
            } catch (\Throwable $e) {
                continue;
            }

            if ($candidate->lt($referenceStart)) {
                continue;
            }

            return $candidate->startOfDay();
        }

        return null;
    }
}
