<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaisseSyntheseJournaliere;
use App\Models\DashboardStat;
use App\Models\DemandeConge;
use App\Models\Employe;
use App\Models\PaieSyntheseMensuelle;
use App\Models\Pointage;
use App\Models\User;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function seedLargeDataset(Request $request)
    {
        $auth = $this->authorizeSeed($request);
        if ($auth) {
            return $auth;
        }

        $status = $this->seedStatus();
        if (($status['status'] ?? null) === 'running' && !$this->isStaleRunningStatus($status)) {
            return response()->json([
                'message' => 'Le seeding est déjà en cours.',
                'status' => $status,
            ], 409);
        }

        $year = (string) $request->input('year', now()->format('Y'));
        if (!preg_match('/^\d{4}$/', $year)) {
            return response()->json(['message' => 'Paramètre year invalide.'], 422);
        }

        $this->writeSeedStatus('queued', ['year' => $year]);
        $this->startSeedProcess($year);

        return response()->json([
            'message' => 'Seeding lancé en arrière-plan.',
            'status_url' => '/api/admin/seed-large-dataset/status',
        ], 202);
    }

    public function seedLargeDatasetStatus(Request $request)
    {
        $auth = $this->authorizeSeed($request);
        if ($auth) {
            return $auth;
        }

        return response()->json([
            'status' => $this->seedStatus(),
            'counts' => $this->counts(),
        ]);
    }

    private function authorizeSeed(Request $request)
    {
        $expected = (string) env('SEED_ADMIN_TOKEN', '');
        if ($expected === '') {
            return response()->json(['message' => 'SEED_ADMIN_TOKEN non configuré.'], 503);
        }

        $provided = (string) ($request->header('X-Seed-Token') ?: $request->input('token', ''));
        if ($provided === '' || !hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        return null;
    }

    private function startSeedProcess(string $year): void
    {
        $php = escapeshellarg(PHP_BINARY);
        $artisan = escapeshellarg(base_path('artisan'));
        $log = escapeshellarg(storage_path('logs/seed-large-dataset.log'));
        $yearArg = escapeshellarg("--year={$year}");

        exec("{$php} {$artisan} maintenance:seed-large-dataset {$yearArg} > {$log} 2>&1 &");
    }

    private function seedStatus(): array
    {
        $path = $this->statusPath();
        if (!is_file($path)) {
            return ['status' => 'idle'];
        }

        $status = json_decode((string) file_get_contents($path), true);
        return is_array($status) ? $status : ['status' => 'unknown'];
    }

    private function writeSeedStatus(string $status, array $payload = []): void
    {
        $path = $this->statusPath();
        @mkdir(dirname($path), 0775, true);

        file_put_contents($path, json_encode(array_merge([
            'status' => $status,
            'updated_at' => now()->toIso8601String(),
        ], $payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function statusPath(): string
    {
        return storage_path('app/seed-large-dataset-status.json');
    }

    private function isStaleRunningStatus(array $status): bool
    {
        $updatedAt = $status['updated_at'] ?? null;
        return !$updatedAt || now()->diffInMinutes($updatedAt) > 30;
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
