<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\CaisseController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PaieController;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RefreshReadModels extends Command
{
    protected $signature = 'read-models:refresh
        {--mois= : Mois de paie à rafraîchir au format YYYY-MM}
        {--annee= : Année complète de paie à rafraîchir}
        {--date= : Jour de synthèse caisse au format YYYY-MM-DD}
        {--only=all : all ou caisse}';

    protected $description = 'Rafraîchit les tables de synthèse utilisées par les pages critiques';

    public function handle(DashboardController $dashboard, PaieController $paie, CaisseController $caisse): int
    {
        $date = $this->option('date') ?: now()->format('Y-m-d');
        $mois = $this->option('mois');
        $annee = $this->option('annee');
        $only = (string) $this->option('only');

        if (!in_array($only, ['all', 'caisse'], true)) {
            $this->error('L’option --only doit être all ou caisse.');
            return self::FAILURE;
        }

        if ($only === 'all') {
            $dashboard->refreshSnapshot('annee', $date);
            $dashboard->refreshSnapshot('mois', $date);
            $this->info('dashboard_stats rafraîchi.');

            $months = $this->monthsToRefresh($mois, $annee);
            foreach ($months as $month) {
                $count = $paie->refreshPaieSyntheseMonth($month);
                $this->info("paie_synthese_mensuelle {$month}: {$count} ligne(s).");
            }
        }

        $count = $caisse->refreshCaisseSyntheseDay($date);
        $this->info("caisse_synthese_journaliere {$date}: {$count} ligne(s).");

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
