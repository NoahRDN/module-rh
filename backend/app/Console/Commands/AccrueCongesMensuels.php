<?php

namespace App\Console\Commands;

use App\Services\CongeService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AccrueCongesMensuels extends Command
{
    protected $signature = 'conges:accrue {mois? : AAAA-MM pour créditer un mois précis (par défaut le mois précédent)}}';
    protected $description = 'Crédite les congés payés (2,5 j/mois) pour tous les employés actifs';

    public function handle(CongeService $service): int
    {
        $moisInput = $this->argument('mois');

        $mois = $moisInput
            ? Carbon::createFromFormat('Y-m', $moisInput)->startOfMonth()
            : now()->subMonthNoOverflow()->startOfMonth();

        $this->info('Crédit des congés pour le mois de ' . $mois->format('Y-m'));

        $service->accrueMois($mois);

        $this->info('Terminé.');
        return self::SUCCESS;
    }
}
