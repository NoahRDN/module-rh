<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\DashboardController;
use Illuminate\Console\Command;

class RefreshDashboardStats extends Command
{
    protected $signature = 'dashboard:refresh
        {--filtre=all : all, annee, mois ou periode}
        {--date= : Date de référence au format YYYY-MM-DD}
        {--date_debut= : Début pour filtre=periode}
        {--date_fin= : Fin pour filtre=periode}';

    protected $description = 'Recalcule et sauvegarde les snapshots du dashboard RH';

    public function handle(DashboardController $dashboard): int
    {
        $filtre = (string) $this->option('filtre');
        $date = $this->option('date') ?: now()->format('Y-m-d');
        $dateDebut = $this->option('date_debut');
        $dateFin = $this->option('date_fin');

        if (!in_array($filtre, ['all', 'annee', 'mois', 'periode'], true)) {
            $this->error('Le filtre doit être all, annee, mois ou periode.');
            return self::FAILURE;
        }

        if ($filtre === 'periode' && (!$dateDebut || !$dateFin)) {
            $this->error('Les options --date_debut et --date_fin sont requises pour filtre=periode.');
            return self::FAILURE;
        }

        $jobs = $filtre === 'all'
            ? [
                ['annee', $date, null, null],
                ['mois', $date, null, null],
            ]
            : [[$filtre, $date, $dateDebut, $dateFin]];

        foreach ($jobs as [$jobFiltre, $jobDate, $jobDateDebut, $jobDateFin]) {
            $snapshot = $dashboard->refreshSnapshot($jobFiltre, $jobDate, $jobDateDebut, $jobDateFin);

            $this->info(sprintf(
                'Snapshot %s [%s -> %s] rafraîchi à %s.',
                $snapshot->filtre,
                $snapshot->date_debut->format('Y-m-d'),
                $snapshot->date_fin->format('Y-m-d'),
                optional($snapshot->generated_at)->format('Y-m-d H:i:s') ?: now()->format('Y-m-d H:i:s')
            ));
        }

        return self::SUCCESS;
    }
}
