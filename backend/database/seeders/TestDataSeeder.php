<?php

namespace Database\Seeders;

use App\Http\Controllers\Api\CaisseController;
use App\Http\Controllers\Api\PaieController;
use App\Models\Caisse;
use App\Models\CaisseMouvement;
use App\Models\Employe;
use App\Models\Paie;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TestDataSeeder extends Seeder
{
    private const CASHBOX_SOURCE = 'Initialisation des caisses 2025-10-31';

    private const OPENING_BALANCE_DAY = '2025-10-31';

    private const SYNTHESIS_DAY = '2026-05-06';

    private const PAYROLL_MONTHS = [
        '2025-11',
        '2025-12',
        '2026-01',
        '2026-02',
        '2026-03',
        '2026-04',
    ];

    private const CASHBOXES = [
        [
            'nom' => 'Banque',
            'description' => 'Compte bancaire principal',
            'solde_initial' => 5500000.00,
            'mouvement_description' => 'Solde initial de la caisse Banque',
        ],
        [
            'nom' => 'Espèces',
            'description' => 'Caisse en espèces',
            'solde_initial' => 5500000.00,
            'mouvement_description' => 'Solde initial de la caisse Espèces',
        ],
        [
            'nom' => 'Mobile Money',
            'description' => 'Caisse mobile money',
            'solde_initial' => 4000000.00,
            'mouvement_description' => 'Solde initial de la caisse Mobile Money',
        ],
        [
            'nom' => 'Caisse paie',
            'description' => 'Caisse dédiée au paiement des salaires',
            'solde_initial' => 5000000.00,
            'mouvement_description' => 'Solde initial de la caisse paie',
        ],
    ];

    private const EMPLOYEE_MATRICULES = [
        'EMP-20260429-0001',
        'EMP-20260429-0002',
        'EMP-20260429-0003',
        'EMP-20260429-0004',
        'EMP-20260429-0005',
        'EMP-20260429-0007',
        'EMP-20260429-0008',
        'EMP-20260429-0010',
    ];

    // Rotation volontaire des statuts pour exposer plusieurs cas métier sur plusieurs périodes.
    private const STATUS_PATTERNS = [
        '2025-11' => ['paye', 'paye', 'non_paye', 'paye', 'paiement_en_validation', 'non_paye', 'paye', 'paiement_en_validation'],
        '2025-12' => ['paye', 'non_paye', 'paye', 'paiement_en_validation', 'paye', 'paye', 'non_paye', 'paiement_en_validation'],
        '2026-01' => ['non_paye', 'paye', 'paiement_en_validation', 'paye', 'paye', 'non_paye', 'paye', 'paye'],
        '2026-02' => ['paye', 'paiement_en_validation', 'paye', 'non_paye', 'paye', 'paye', 'paiement_en_validation', 'non_paye'],
        '2026-03' => ['paye', 'paye', 'paye', 'non_paye', 'non_paye', 'paiement_en_validation', 'paye', 'paiement_en_validation'],
        '2026-04' => ['paye', 'paye', 'paye', 'non_paye', 'non_paye', 'paiement_en_validation', 'paiement_en_validation', 'paiement_en_validation'],
    ];

    private const CASHBOX_ROTATION = ['Banque', 'Mobile Money', 'Caisse paie', 'Espèces'];

    public function run(): void
    {
        $employees = Employe::query()
            ->whereIn('matricule', self::EMPLOYEE_MATRICULES)
            ->get()
            ->keyBy('matricule');

        $this->call(PointageSeeder::class);

        $paieController = app(PaieController::class);
        $caisseController = app(CaisseController::class);

        DB::transaction(function () use ($employees, $paieController, $caisseController) {
            $this->resetPreviousScenario($employees->pluck('id')->all());
            $caisses = $this->seedCashboxes();
            $this->seedPayrollScenario($employees, $caisses, $paieController, $caisseController);
            $this->syncCashboxBalances($caisses->pluck('id')->all());
        });

        foreach (self::PAYROLL_MONTHS as $month) {
            $paieController->refreshPaieSyntheseMonth($month);
        }

        $caisseController->refreshCaisseSyntheseDay(self::SYNTHESIS_DAY);
    }

    private function resetPreviousScenario(array $employeeIds): void
    {
        if ($employeeIds) {
            $paieIds = Paie::query()
                ->whereIn('mois', self::PAYROLL_MONTHS)
                ->whereIn('employe_id', $employeeIds)
                ->pluck('id');

            if ($paieIds->isNotEmpty()) {
                DB::table('caisse_mouvements')->whereIn('paie_id', $paieIds)->delete();
            }

            Paie::query()
                ->whereIn('mois', self::PAYROLL_MONTHS)
                ->whereIn('employe_id', $employeeIds)
                ->delete();
        }

        DB::table('caisse_mouvements')
            ->where('source', 'like', 'Initialisation des caisses %')
            ->delete();

        DB::table('caisse_mouvements')
            ->whereIn('caisse_id', Caisse::query()->where('nom', 'Caisse principale')->pluck('id'))
            ->delete();

        Caisse::query()->where('nom', 'Caisse principale')->delete();
    }

    private function seedCashboxes()
    {
        $now = now();

        foreach (self::CASHBOXES as $cashbox) {
            Caisse::query()->updateOrCreate(
                ['nom' => $cashbox['nom']],
                [
                    'description' => $cashbox['description'],
                    'solde' => 0,
                    'active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $caisses = Caisse::query()
            ->whereIn('nom', collect(self::CASHBOXES)->pluck('nom')->all())
            ->get()
            ->keyBy('nom');

        foreach (self::CASHBOXES as $cashbox) {
            $caisse = $caisses->get($cashbox['nom']);

            if (!$caisse) {
                continue;
            }

            DB::table('caisse_mouvements')->insert([
                'caisse_id' => $caisse->id,
                'paie_id' => null,
                'type' => 'entree',
                'categorie' => 'apport_capital',
                'montant' => $cashbox['solde_initial'],
                'source' => self::CASHBOX_SOURCE,
                'description' => $cashbox['mouvement_description'],
                'statut' => 'valide',
                'demande_validation_le' => self::OPENING_BALANCE_DAY . ' 08:00:00',
                'valide_le' => self::OPENING_BALANCE_DAY . ' 08:05:00',
                'created_at' => self::OPENING_BALANCE_DAY . ' 08:00:00',
                'updated_at' => self::OPENING_BALANCE_DAY . ' 08:05:00',
            ]);

            $caisse->forceFill([
                'solde' => $cashbox['solde_initial'],
                'updated_at' => self::OPENING_BALANCE_DAY . ' 08:05:00',
            ])->save();
        }

        return $caisses;
    }

    private function seedPayrollScenario($employees, $caisses, PaieController $paieController, CaisseController $caisseController): void
    {
        foreach (self::PAYROLL_MONTHS as $monthIndex => $month) {
            foreach (self::EMPLOYEE_MATRICULES as $employeeIndex => $matricule) {
                $employee = $employees->get($matricule);

                if (!$employee) {
                    continue;
                }

                $status = self::STATUS_PATTERNS[$month][$employeeIndex];
                $createdAt = $this->monthDateTime($month, 2, 8 + ($employeeIndex % 8), 0);
                $validatedAt = $this->monthDateTime($month, 27, 17, $employeeIndex * 5);
                $paymentRequestedAt = $status === 'non_paye'
                    ? null
                    : $this->monthDateTime($month, 28, 9 + ($employeeIndex % 4), ($employeeIndex * 10) % 60);
                $paidOn = $status === 'paye' ? $this->monthDate($month, 28) : null;
                $updatedAt = $status === 'paye'
                    ? $this->monthDateTime($month, 28, 9 + ($employeeIndex % 4), 15 + (($employeeIndex * 10) % 40))
                    : ($paymentRequestedAt ?: $validatedAt);

                $paie = $this->generatePayroll($paieController, $employee->id, $month);
                $this->alignGeneratedPayrollTimestamps($paie, $createdAt);

                $this->assertSuccess($paieController->valider($paie->id), "Validation paie {$month} {$matricule}");

                $paie->refresh();
                $paie->forceFill([
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                    'demande_validation_le' => $createdAt,
                    'valide_le' => $validatedAt,
                ])->save();

                if ($status === 'non_paye') {
                    continue;
                }

                $cashboxName = self::CASHBOX_ROTATION[($employeeIndex + (int) substr($month, -2)) % count(self::CASHBOX_ROTATION)];
                $caisse = $caisses->get($cashboxName);

                if (!$caisse) {
                    continue;
                }

                $this->assertSuccess(
                    $paieController->payer($this->makeRequest(['caisse_id' => $caisse->id]), $paie->id),
                    "Paiement paie {$month} {$matricule}"
                );

                $paie->refresh();
                $mouvement = CaisseMouvement::query()
                    ->where('paie_id', $paie->id)
                    ->latest('id')
                    ->firstOrFail();

                $mouvement->forceFill([
                    'created_at' => $paymentRequestedAt,
                    'updated_at' => $status === 'paye' ? $updatedAt : $paymentRequestedAt,
                    'demande_validation_le' => $paymentRequestedAt,
                    'valide_le' => $status === 'paye' ? $updatedAt : null,
                ])->save();

                $paie->forceFill([
                    'updated_at' => $status === 'paye' ? $updatedAt : $paymentRequestedAt,
                    'demande_validation_le' => $paymentRequestedAt,
                    'valide_le' => $validatedAt,
                    'paye_le' => $paidOn,
                ])->save();

                if ($status !== 'paye') {
                    continue;
                }

                $this->assertSuccess(
                    $caisseController->valider($mouvement->id),
                    "Validation mouvement de paiement {$month} {$matricule}"
                );

                $mouvement->refresh();
                $mouvement->forceFill([
                    'created_at' => $paymentRequestedAt,
                    'updated_at' => $updatedAt,
                    'demande_validation_le' => $paymentRequestedAt,
                    'valide_le' => $updatedAt,
                ])->save();

                $paie->refresh();
                $paie->forceFill([
                    'updated_at' => $updatedAt,
                    'demande_validation_le' => $paymentRequestedAt,
                    'valide_le' => $validatedAt,
                    'paye_le' => $paidOn,
                ])->save();
            }
        }
    }

    private function generatePayroll(PaieController $paieController, int $employeeId, string $month): Paie
    {
        $response = $paieController->genererPaie($this->makeRequest([
            'employe_id' => $employeeId,
            'mois' => $month,
        ]));

        $payload = $this->assertSuccess($response, "Génération paie {$month} employé {$employeeId}");
        $paieId = data_get($payload, 'paie.id');

        return Paie::query()->findOrFail($paieId);
    }

    private function alignGeneratedPayrollTimestamps(Paie $paie, string $createdAt): void
    {
        $paie->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
            'demande_validation_le' => $createdAt,
        ])->save();

        DB::table('paie_details')
            ->where('paie_id', $paie->id)
            ->update([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

        DB::table('paie_primes')
            ->where('paie_id', $paie->id)
            ->update([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
    }

    private function makeRequest(array $payload): Request
    {
        return Request::create('/', 'POST', $payload);
    }

    private function assertSuccess($response, string $context): array
    {
        $status = $response->getStatusCode();
        $payload = $response->getData(true);

        if ($status >= 400) {
            throw new RuntimeException($context . ': ' . ($payload['message'] ?? 'Erreur inconnue'));
        }

        return $payload;
    }

    private function monthDate(string $month, int $day): string
    {
        return sprintf('%s-%02d', $month, $day);
    }

    private function monthDateTime(string $month, int $day, int $hour, int $minute): string
    {
        return sprintf('%s-%02d %02d:%02d:00', $month, $day, $hour, $minute);
    }

    private function syncCashboxBalances(array $cashboxIds): void
    {
        if (!$cashboxIds) {
            return;
        }

        $balances = DB::table('caisse_mouvements')
            ->selectRaw("
                caisse_id,
                SUM(
                    CASE
                        WHEN type = 'entree' AND statut = 'valide' THEN montant
                        WHEN type = 'sortie' AND statut = 'valide' THEN -montant
                        ELSE 0
                    END
                ) AS solde
            ")
            ->whereIn('caisse_id', $cashboxIds)
            ->groupBy('caisse_id')
            ->pluck('solde', 'caisse_id');

        foreach ($cashboxIds as $cashboxId) {
            Caisse::query()->whereKey($cashboxId)->update([
                'solde' => (float) ($balances[$cashboxId] ?? 0),
                'updated_at' => now(),
            ]);
        }
    }
}
