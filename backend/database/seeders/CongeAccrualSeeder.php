<?php

namespace Database\Seeders;

use App\Models\Employe;
use App\Services\CongeService;
use Illuminate\Database\Seeder;

class CongeAccrualSeeder extends Seeder
{
    public function run(): void
    {
        /** @var CongeService $service */
        $service = app(CongeService::class);

        Employe::whereHas('contrats', function ($q) {
            $now = now()->toDateString();
            $q->where('statut', 'en_cours')
                ->whereDate('date_debut', '<=', $now)
                ->where(function ($w) use ($now) {
                    $w->whereNull('date_fin')->orWhereDate('date_fin', '>=', $now);
                });
        })->chunk(100, function ($chunk) use ($service) {
            foreach ($chunk as $emp) {
                $service->accrueMensuelPourEmploye($emp);
            }
        });
    }
}
