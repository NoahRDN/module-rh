<?php

namespace App\Console\Commands;

use App\Models\AcquisConge;
use App\Models\ConsommationConge;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CongeHistoriqueCommand extends Command
{
    protected $signature = 'conges:historique {employe_id} {from?} {to?}';
    protected $description = 'Affiche les acquis et consommations de congés pour un employé entre deux dates';

    public function handle(): int
    {
        $employeId = (int) $this->argument('employe_id');
        $from = $this->argument('from') ? Carbon::parse($this->argument('from')) : null;
        $to = $this->argument('to') ? Carbon::parse($this->argument('to')) : null;

        $acquis = AcquisConge::with('typeConge')
            ->where('employe_id', $employeId)
            ->when($from, fn($q) => $q->whereDate('acquis_le', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('acquis_le', '<=', $to))
            ->orderBy('acquis_le')
            ->get();

        $consos = ConsommationConge::with('demande', 'acquis.typeConge')
            ->whereHas('acquis', fn($q) => $q->where('employe_id', $employeId))
            ->when($from, fn($q) => $q->whereHas('acquis', fn($a) => $a->whereDate('acquis_le', '>=', $from)))
            ->when($to, fn($q) => $q->whereHas('acquis', fn($a) => $a->whereDate('acquis_le', '<=', $to)))
            ->orderBy('created_at')
            ->get();

        $this->info('--- Acquis ---');
        foreach ($acquis as $a) {
            $this->line(sprintf(
                '%s | %s | +%s j (expire %s)',
                $a->acquis_le,
                $a->typeConge?->code,
                $a->jours_acquis,
                $a->expire_le
            ));
        }

        $this->info('--- Consommations ---');
        foreach ($consos as $c) {
            $this->line(sprintf(
                '%s | %s | -%s j (demande #%s)',
                optional($c->acquis)->acquis_le,
                optional(optional($c->acquis)->typeConge)->code,
                $c->jours_utilises,
                $c->demande_conge_id
            ));
        }

        return self::SUCCESS;
    }
}
