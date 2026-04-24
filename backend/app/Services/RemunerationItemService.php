<?php

namespace App\Services;

use App\Models\Contrat;
use App\Models\Employe;
use App\Models\RemunerationItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RemunerationItemService
{
    public function resolveForEmploye(Employe $employe, string $mois): Collection
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

        return RemunerationItem::query()
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
    }

    public function totalForEmploye(Employe $employe, string $mois): float
    {
        return (float) $this->resolveForEmploye($employe, $mois)->sum(fn (RemunerationItem $item) => (float) $item->montant);
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
