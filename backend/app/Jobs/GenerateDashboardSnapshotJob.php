<?php

namespace App\Jobs;

use App\Http\Controllers\Api\DashboardController;
use App\Models\DashboardStat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateDashboardSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public string $periodType,
        public string $dateDebut,
        public string $dateFin,
    ) {
        $this->onQueue('dashboard');
    }

    public function handle(DashboardController $dashboard): void
    {
        DashboardStat::query()
            ->where('period_type', $this->periodType)
            ->whereDate('date_debut', $this->dateDebut)
            ->whereDate('date_fin', $this->dateFin)
            ->whereNull('generated_at')
            ->update([
                'status' => 'generating',
                'error_message' => null,
            ]);

        try {
            $dashboard->refreshSnapshot($this->periodType, $this->dateDebut, $this->dateDebut, $this->dateFin);
        } catch (\Throwable $e) {
            $existing = DashboardStat::query()
                ->where('period_type', $this->periodType)
                ->whereDate('date_debut', $this->dateDebut)
                ->whereDate('date_fin', $this->dateFin)
                ->first();
            $hasExistingData = $existing && ($existing->generated_at || $existing->statistiques);

            DashboardStat::query()->updateOrCreate(
                [
                    'filtre' => $this->periodType,
                    'date_debut' => $this->dateDebut,
                    'date_fin' => $this->dateFin,
                ],
                [
                    'period_type' => $this->periodType,
                    'statistiques' => $existing?->statistiques ?: [],
                    'donnees_rapides' => $existing?->donnees_rapides ?: [],
                    'alertes_recentes' => $existing?->alertes_recentes ?: [],
                    'status' => $hasExistingData ? 'stale' : 'failed',
                    'error_message' => $e->getMessage(),
                    'refreshed_by' => 'queue',
                ],
            );

            throw $e;
        }
    }

    public function uniqueId(): string
    {
        return "{$this->periodType}:{$this->dateDebut}:{$this->dateFin}";
    }
}
