<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlerteSetting;
use App\Models\CalendrierEvenement;
use App\Models\Contrat;
use App\Models\DemandeConge;
use App\Models\Employe;
use App\Models\JourFerie;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AlerteController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = min(100, max(1, (int) $request->query('per_page', 25)));
            $page = max(1, (int) $request->query('page', 1));
            $scanLimit = min(250, max(50, $perPage * $page));
            $alerts = $this->buildAlerts($scanLimit);
            $items = $alerts->slice(($page - 1) * $perPage, $perPage)->values();

            return response()->json(new LengthAwarePaginator(
                $items,
                $alerts->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            ));
        } catch (\Throwable $e) {
            Log::error('Erreur generation alertes', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function recent(int $limit = 6): array
    {
        return $this->buildAlerts(min(50, max(1, $limit * 3)))
            ->take($limit)
            ->values()
            ->all();
    }

    private function buildAlerts(int $limit = 50): Collection
    {
        $alerts = collect();
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $sourceLimit = min(100, max(10, $limit * 2));
        $settings = $this->alertSettings();

        if ($setting = $this->resolveSetting($settings, 'fin_contrat', [
            'seuil_jours' => 30,
            'niveau' => 'warning',
        ])) {
            $seuilJours = $setting->seuil_jours ?? 30;

            $contratsExpirants = Contrat::query()
                ->select(['id', 'employe_id', 'date_fin'])
                ->with('employe:id,matricule,nom,prenom')
                ->whereNotNull('date_fin')
                ->whereDate('date_fin', '<=', $today->copy()->addDays($seuilJours)->toDateString())
                ->whereDate('date_fin', '>=', $today->toDateString())
                ->orderBy('date_fin')
                ->limit($sourceLimit)
                ->get();

            foreach ($contratsExpirants as $contrat) {
                $joursRestants = $today->diffInDays($contrat->date_fin->copy()->startOfDay());
                $employeLabel = $this->employeLabel($contrat->employe);
                $alerts->push([
                    'type' => 'fin_contrat',
                    'level' => $joursRestants <= 7 ? 'danger' : $setting->niveau,
                    'message' => "Contrat de {$employeLabel} expire {$this->delayLabel($joursRestants)}",
                    'contrat_id' => $contrat->id,
                    'employe_id' => $contrat->employe_id,
                    'employe' => $this->employePayload($contrat->employe),
                    'date_fin' => $contrat->date_fin->format('Y-m-d'),
                ]);
            }
        }

        if ($setting = $this->resolveSetting($settings, 'conges_non_pris', [
            'seuil_nombre' => 15,
            'niveau' => 'info',
        ])) {
            try {
                $soldes = DB::table('view_solde_conges')
                    ->select(['employe_id', DB::raw('solde_actuel as solde')])
                    ->where('solde_actuel', '>=', $setting->seuil_nombre ?? 15)
                    ->orderByDesc('solde_actuel')
                    ->limit($sourceLimit)
                    ->get();

                $employes = Employe::query()
                    ->select(['id', 'matricule', 'nom', 'prenom'])
                    ->whereIn('id', $soldes->pluck('employe_id'))
                    ->get()
                    ->keyBy('id');

                $soldes->each(function ($solde) use ($alerts, $setting, $employes) {
                    $emp = $employes->get($solde->employe_id);
                    $alerts->push([
                        'type' => 'conges_non_pris',
                        'level' => $setting->niveau,
                        'message' => "{$this->employeLabel($emp)} a {$solde->solde} jours de congés non pris",
                        'employe_id' => $solde->employe_id,
                        'employe' => $this->employePayload($emp),
                        'solde' => $solde->solde,
                    ]);
                });
            } catch (\Throwable $e) {
                // La vue peut ne pas exister sur certains environnements de test.
            }
        }

        if ($setting = $this->resolveSetting($settings, 'conge_en_attente', [
            'seuil_jours' => 2,
            'niveau' => 'warning',
        ])) {
            $seuilHeures = ($setting->seuil_jours ?? 2) * 24;

            DemandeConge::query()
                ->select(['id', 'employe_id', 'created_at'])
                ->with('employe:id,matricule,nom,prenom')
                ->where('statut', '!=', 'rh_valide')
                ->where('created_at', '<', $now->copy()->subHours($seuilHeures))
                ->orderBy('created_at')
                ->limit($sourceLimit)
                ->get()
                ->each(function ($d) use ($alerts, $setting, $seuilHeures) {
                    $employeLabel = $this->employeLabel($d->employe);
                    $alerts->push([
                        'type' => 'conge_en_attente',
                        'level' => $setting->niveau,
                        'message' => "Demande en attente >{$seuilHeures}h pour {$employeLabel}",
                        'demande_id' => $d->id,
                        'employe' => $this->employePayload($d->employe),
                    ]);
                });
        }

        if ($setting = $this->resolveSetting($settings, 'conge_proche', [
            'seuil_jours' => 2,
            'niveau' => 'danger',
        ])) {
            $seuilJours = $setting->seuil_jours ?? 2;

            DemandeConge::query()
                ->select(['id', 'employe_id', 'date_debut'])
                ->with('employe:id,matricule,nom,prenom')
                ->where('statut', '!=', 'rh_valide')
                ->whereDate('date_debut', '<=', $today->copy()->addDays($seuilJours)->toDateString())
                ->whereDate('date_debut', '>=', $today->toDateString())
                ->orderBy('date_debut')
                ->limit($sourceLimit)
                ->get()
                ->each(function ($d) use ($alerts, $setting, $today) {
                    $joursRestants = $today->diffInDays($d->date_debut->copy()->startOfDay());
                    $employeLabel = $this->employeLabel($d->employe);
                    $alerts->push([
                        'type' => 'conge_proche',
                        'level' => $setting->niveau,
                        'message' => "Le congé de {$employeLabel} débute {$this->delayLabel($joursRestants)} et n'est pas validé",
                        'demande_id' => $d->id,
                        'date_debut' => $d->date_debut->format('Y-m-d'),
                        'employe' => $this->employePayload($d->employe),
                    ]);
                });
        }

        $this->appendFrequentAbsenceAlerts($alerts, $settings, 'absences_maladie', '%malad%', [
            'seuil_nombre' => 4,
            'periode_jours' => 60,
            'niveau' => 'warning',
        ], $now, $sourceLimit);

        $this->appendFrequentAbsenceAlerts($alerts, $settings, 'absences_exceptionnelles', '%exception%', [
            'seuil_nombre' => 3,
            'periode_jours' => 90,
            'niveau' => 'warning',
        ], $now, $sourceLimit);

        if ($setting = $this->resolveSetting($settings, 'ferie_proche', [
            'seuil_jours' => 7,
            'niveau' => 'info',
        ])) {
            $seuilJours = $setting->seuil_jours ?? 7;
            $until = $today->copy()->addDays($seuilJours);

            JourFerie::query()
                ->select(['id', 'nom', 'date', 'recurrent'])
                ->where('recurrent', false)
                ->whereDate('date', '>=', $today->toDateString())
                ->whereDate('date', '<=', $until->toDateString())
                ->orderBy('date')
                ->limit($sourceLimit)
                ->get()
                ->each(function ($ferie) use ($alerts, $setting, $today) {
                    $joursRestants = $today->diffInDays($ferie->date->copy()->startOfDay());
                    $alerts->push([
                        'type' => 'ferie_proche',
                        'level' => $this->proximityLevel($setting->niveau, $joursRestants),
                        'message' => "Jour férié \"{$ferie->nom}\" {$this->delayLabel($joursRestants)}",
                        'date_debut' => $ferie->date->format('Y-m-d'),
                        'description' => $ferie->nom,
                    ]);
                });

            JourFerie::query()
                ->select(['id', 'nom', 'date', 'recurrent'])
                ->where('recurrent', true)
                ->limit($sourceLimit)
                ->get()
                ->each(function ($ferie) use ($alerts, $setting, $today, $until) {
                    $occurrence = $this->nextRecurringOccurrence($ferie->date, $today);
                    if (!$occurrence || $occurrence->gt($until)) {
                        return;
                    }

                    $joursRestants = $today->diffInDays($occurrence->copy()->startOfDay());
                    $alerts->push([
                        'type' => 'ferie_proche',
                        'level' => $this->proximityLevel($setting->niveau, $joursRestants),
                        'message' => "Jour férié \"{$ferie->nom}\" {$this->delayLabel($joursRestants)}",
                        'date_debut' => $occurrence->format('Y-m-d'),
                        'description' => $ferie->nom,
                    ]);
                });
        }

        if ($setting = $this->resolveSetting($settings, 'evenement_rh_proche', [
            'seuil_jours' => 7,
            'niveau' => 'warning',
        ])) {
            $seuilJours = $setting->seuil_jours ?? 7;

            CalendrierEvenement::query()
                ->select(['id', 'type', 'employe_id', 'date_debut', 'description'])
                ->with('employe:id,matricule,nom,prenom')
                ->where('type', 'rh')
                ->whereDate('date_debut', '>=', $today->toDateString())
                ->whereDate('date_debut', '<=', $today->copy()->addDays($seuilJours)->toDateString())
                ->orderBy('date_debut')
                ->limit($sourceLimit)
                ->get()
                ->each(function ($event) use ($alerts, $setting, $today) {
                    $joursRestants = $today->diffInDays($event->date_debut->copy()->startOfDay());
                    $eventLabel = $event->description ?: 'Événement RH';
                    $targetLabel = $event->employe
                        ? " pour {$this->employeLabel($event->employe)}"
                        : '';

                    $alerts->push([
                        'type' => 'evenement_rh_proche',
                        'level' => $this->proximityLevel($setting->niveau, $joursRestants),
                        'message' => "Événement RH \"{$eventLabel}\" prévu{$targetLabel} {$this->delayLabel($joursRestants)}",
                        'evenement_id' => $event->id,
                        'date_debut' => $event->date_debut->format('Y-m-d'),
                        'description' => $eventLabel,
                        'employe_id' => $event->employe_id,
                        'employe' => $this->employePayload($event->employe),
                    ]);
                });
        }

        $priority = ['danger' => 0, 'warning' => 1, 'info' => 2];

        return $alerts
            ->sortBy(fn ($alert) => [
                $priority[$alert['level']] ?? 3,
                $alert['date_debut'] ?? $alert['date_fin'] ?? '9999-12-31',
            ])
            ->take($limit)
            ->values();
    }

    private function appendFrequentAbsenceAlerts(
        Collection $alerts,
        Collection $settings,
        string $code,
        string $searchPattern,
        array $defaults,
        Carbon $now,
        int $sourceLimit
    ): void {
        $setting = $this->resolveSetting($settings, $code, $defaults);
        if (!$setting) {
            return;
        }

        $seuilNombre = $setting->seuil_nombre ?? $defaults['seuil_nombre'];
        $periodeJours = $setting->periode_jours ?? $defaults['periode_jours'];
        $counts = DemandeConge::query()
            ->select('employe_id', DB::raw('count(*) as total'))
            ->whereHas('typeConge', function ($q) use ($searchPattern) {
                $q->where('libelle', 'like', $searchPattern)
                    ->orWhere('code', 'like', $searchPattern);
            })
            ->whereDate('date_debut', '>=', $now->copy()->subDays($periodeJours)->toDateString())
            ->groupBy('employe_id')
            ->havingRaw('count(*) >= ?', [$seuilNombre])
            ->orderByDesc(DB::raw('count(*)'))
            ->limit($sourceLimit)
            ->get();

        if ($counts->isEmpty()) {
            return;
        }

        $employes = Employe::query()
            ->select(['id', 'matricule', 'nom', 'prenom'])
            ->whereIn('id', $counts->pluck('employe_id'))
            ->get()
            ->keyBy('id');

        foreach ($counts as $row) {
            $emp = $employes->get($row->employe_id);
            $label = $code === 'absences_maladie' ? 'congés maladie' : 'congés exceptionnels';
            $alerts->push([
                'type' => $code,
                'level' => $setting->niveau,
                'message' => "{$this->employeLabel($emp)} a {$row->total} {$label} sur {$periodeJours} jours",
                'employe_id' => $row->employe_id,
                'employe' => $this->employePayload($emp),
            ]);
        }
    }

    private function alertSettings(): Collection
    {
        return Cache::remember('settings:alertes', now()->addMinutes(30), function () {
            return AlerteSetting::query()->get()->keyBy('code');
        });
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

    private function employeLabel($employe): string
    {
        if (!$employe) {
            return 'Employé non renseigné';
        }

        $name = trim("{$employe->nom} {$employe->prenom}");
        $matricule = trim((string) $employe->matricule);

        if ($matricule && $name) {
            return "{$matricule} - {$name}";
        }

        return $matricule ?: ($name ?: 'Employé non renseigné');
    }

    private function delayLabel(int $joursRestants): string
    {
        return $joursRestants === 0
            ? "aujourd'hui"
            : "dans {$joursRestants} jour(s)";
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
