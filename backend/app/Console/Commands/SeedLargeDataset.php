<?php

namespace App\Console\Commands;

use App\Models\CaisseSyntheseJournaliere;
use App\Models\DashboardStat;
use App\Models\DemandeConge;
use App\Models\Employe;
use App\Models\PaieSyntheseMensuelle;
use App\Models\Pointage;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedLargeDataset extends Command
{
    protected $signature = 'maintenance:seed-large-dataset {--year= : Année à rafraîchir pour les read models}';

    protected $description = 'Insère le grand jeu de données de test puis rafraîchit les tables de synthèse';

    public function handle(): int
    {
        $year = (string) ($this->option('year') ?: now()->format('Y'));
        $this->writeStatus('running', ['year' => $year, 'started_at' => now()->toIso8601String()]);

        try {
            $this->info('Seeding LargeDashboardDatasetSeeder...');
            Artisan::call('db:seed', [
                '--class' => 'LargeDashboardDatasetSeeder',
                '--force' => true,
            ]);
            $this->output->write(Artisan::output());

            $this->info("Refreshing read models for {$year}...");
            Artisan::call('read-models:refresh', ['--annee' => $year]);
            $this->output->write(Artisan::output());

            $counts = $this->counts();
            $this->writeStatus('completed', [
                'year' => $year,
                'completed_at' => now()->toIso8601String(),
                'counts' => $counts,
            ]);

            $this->info('Large dataset completed.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->writeStatus('failed', [
                'year' => $year,
                'failed_at' => now()->toIso8601String(),
                'error' => $e->getMessage(),
            ]);

            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    private function writeStatus(string $status, array $payload = []): void
    {
        $path = storage_path('app/seed-large-dataset-status.json');
        @mkdir(dirname($path), 0775, true);

        file_put_contents($path, json_encode(array_merge([
            'status' => $status,
            'updated_at' => now()->toIso8601String(),
        ], $payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function counts(): array
    {
        return [
            'load_employes' => Employe::where('matricule', 'like', 'LOAD-20260501-%')->count(),
            'load_users' => User::where('email', 'like', 'load.employee.%@example.test')->count(),
            'load_conges' => DemandeConge::whereHas('employe', fn ($q) => $q->where('matricule', 'like', 'LOAD-20260501-%'))->count(),
            'load_pointages' => Pointage::where('source', 'seed')->count(),
            'dashboard_stats' => DashboardStat::count(),
            'paie_synthese' => PaieSyntheseMensuelle::count(),
            'caisse_synthese' => CaisseSyntheseJournaliere::count(),
        ];
    }
}
