<?php

namespace App\Services;

use App\Models\Contrat;
use App\Models\Employe;
use App\Models\RemunerationItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RemunerationItemService
{
    public function resolveForEmploye(Employe $employe, string $mois, array $context = []): Collection
    {
        $start = Carbon::createFromFormat('Y-m', $mois)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $posteId = $employe->posteActifPourDate($end)?->id ?? $employe->poste_id;
        $contrat = Contrat::where('employe_id', $employe->id)
            ->whereDate('date_debut', '<=', $end->toDateString())
            ->where(function ($query) use ($start) {
                $query->whereNull('date_fin')->orWhereDate('date_fin', '>=', $start->toDateString());
            })
            ->orderByDesc('date_debut')
            ->first();

        $anciennete = $employe->date_embauche
            ? Carbon::parse($employe->date_embauche)->startOfDay()->diffInYears($end)
            : 0;

        $items = RemunerationItem::query()
            ->with(['poste:id,nom', 'employe:id,matricule,nom,prenom', 'contrat:id,numero'])
            ->where('actif', true)
            ->where(function ($query) use ($employe, $posteId, $contrat) {
                $query->where('scope_type', 'global');

                if ($posteId) {
                    $query->orWhere(function ($sub) use ($posteId) {
                        $sub->where('scope_type', 'poste')->where('poste_id', $posteId);
                    });
                }

                $query->orWhere(function ($sub) use ($employe) {
                    $sub->where('scope_type', 'employe')->where('employe_id', $employe->id);
                });

                if ($contrat?->id) {
                    $query->orWhere(function ($sub) use ($contrat) {
                        $sub->where('scope_type', 'contrat')->where('contrat_id', $contrat->id);
                    });
                }
            })
            ->get()
            ->filter(fn (RemunerationItem $item) => $this->matchesRecurrence($item, $mois))
            ->filter(fn (RemunerationItem $item) => $this->matchesCondition($item, $anciennete))
            ->values();

        $presenceContext = $this->buildPresenceContext($context);

        return $items
            ->map(function (RemunerationItem $item) use ($presenceContext) {
                $appliedAmount = $this->computeAppliedAmount($item, $presenceContext);
                $item->setAttribute('montant_applique', round($appliedAmount, 2));
                return $item;
            })
            ->filter(fn (RemunerationItem $item) => (float) ($item->montant_applique ?? 0) > 0)
            ->values();
    }

    public function totalForEmploye(Employe $employe, string $mois): float
    {
        return (float) $this->resolveForEmploye($employe, $mois)->sum(
            fn (RemunerationItem $item) => (float) ($item->montant_applique ?? $item->montant)
        );
    }

    protected function buildPresenceContext(array $context): array
    {
        $details = collect($context['details'] ?? []);
        $hoursPerDay = (float) ($context['hours_per_day'] ?? 8);
        $workedHours = (float) ($context['heures_travaillees'] ?? $details->sum('heures_travaillees'));

        $workingDays = (int) $details
            ->filter(fn ($row) => !($row['weekend'] ?? false) && !($row['ferie'] ?? false))
            ->count();
        $workedDays = (int) $details
            ->filter(fn ($row) => (float) ($row['heures_travaillees'] ?? 0) > 0)
            ->count();

        $presenceRatio = 1.0;
        if ($workingDays > 0) {
            $presenceRatio = max(0, min(1, $workedDays / $workingDays));
        } elseif ($hoursPerDay > 0) {
            $presenceRatio = max(0, min(1, $workedHours / $hoursPerDay));
        }

        return [
            'worked_hours' => max(0, $workedHours),
            'worked_days' => max(0, $workedDays),
            'presence_ratio' => $presenceRatio,
            'is_absent' => $workedDays <= 0 && $workedHours <= 0,
        ];
    }

    protected function computeAppliedAmount(RemunerationItem $item, array $context): float
    {
        $baseAmount = (float) $item->montant;
        if ($baseAmount <= 0) {
            return 0;
        }

        if ((bool) $item->depends_on_presence && ($context['is_absent'] ?? false)) {
            return 0;
        }

        $type = strtolower((string) ($item->calculation_type ?? 'fixe'));

        return match ($type) {
            'jour' => $baseAmount * (float) ($context['worked_days'] ?? 0),
            'heure' => $baseAmount * (float) ($context['worked_hours'] ?? 0),
            default => (bool) $item->prorata
                ? $baseAmount * (float) ($context['presence_ratio'] ?? 1)
                : $baseAmount,
        };
    }

    protected function matchesRecurrence(RemunerationItem $item, string $mois): bool
    {
        if ($item->recurrence_type === 'ponctuel') {
            return $item->mois_application === $mois;
        }

        return true;
    }

    protected function matchesCondition(RemunerationItem $item, float $anciennete): bool
    {
        if (!$item->condition_type) {
            return true;
        }

        if ($item->condition_type !== 'anciennete') {
            return true;
        }

        $value = (float) ($item->condition_value ?? 0);

        return match ($item->condition_operator) {
            '>' => $anciennete > $value,
            '<' => $anciennete < $value,
            '=' => $anciennete == $value,
            '>=' => $anciennete >= $value,
            '<=' => $anciennete <= $value,
            default => true,
        };
    }
}
