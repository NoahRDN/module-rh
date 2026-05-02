<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\PaieController;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PayrollRefresh extends Command
{
    protected $signature = 'payroll:refresh
        {--mois= : Mois de paie a rafraichir au format YYYY-MM}
        {--annee= : Annee complete a rafraichir}';

    protected $description = 'Recalcule et met a jour la synthese mensuelle de paie';

    public function handle(PaieController $paie): int
    {
        $months = $this->monthsToRefresh(
            $this->option('mois'),
            $this->option('annee'),
        );

        foreach ($months as $month) {
            try {
                $count = $paie->refreshPaieSyntheseMonth($month);
                $this->info("paie_synthese_mensuelle {$month}: {$count} ligne(s).");
            } catch (\Throwable $e) {
                $this->error("Echec payroll:refresh {$month}: {$e->getMessage()}");
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    private function monthsToRefresh(?string $mois, ?string $annee): array
    {
        if ($mois) {
            Carbon::createFromFormat('Y-m', $mois);
            return [$mois];
        }

        if ($annee) {
            $start = Carbon::createFromFormat('Y-m', "{$annee}-01")->startOfMonth();

            return collect(range(0, 11))
                ->map(fn (int $offset) => $start->copy()->addMonths($offset)->format('Y-m'))
                ->all();
        }

        return [now()->format('Y-m')];
    }
}
