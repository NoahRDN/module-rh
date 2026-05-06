<?php

namespace Database\Seeders;

use App\Http\Controllers\Api\CaisseController;
use App\Http\Controllers\Api\PaieController;
use App\Models\Caisse;
use App\Models\Employe;
use App\Models\Paie;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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

    private const EMPLOYEE_BASES = [
        'EMP-20260429-0001' => 450000.00,
        'EMP-20260429-0002' => 380000.00,
        'EMP-20260429-0003' => 520000.00,
        'EMP-20260429-0004' => 410000.00,
        'EMP-20260429-0005' => 395000.00,
        'EMP-20260429-0007' => 430000.00,
        'EMP-20260429-0008' => 360000.00,
        'EMP-20260429-0010' => 500000.00,
    ];

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
        DB::transaction(function () {
            $employees = Employe::query()
                ->whereIn('matricule', array_keys(self::EMPLOYEE_BASES))
                ->get()
                ->keyBy('matricule');

            $this->resetPreviousScenario($employees->pluck('id')->all());
            $caisses = $this->seedCashboxes();
            $this->seedPayrollScenario($employees, $caisses);
            $this->syncCashboxBalances($caisses->pluck('id')->all());
        });

        foreach (self::PAYROLL_MONTHS as $month) {
            app(PaieController::class)->refreshPaieSyntheseMonth($month);
        }

        app(CaisseController::class)->refreshCaisseSyntheseDay(self::SYNTHESIS_DAY);
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
        }

        return $caisses;
    }

    private function seedPayrollScenario($employees, $caisses): void
    {
        $matricules = array_keys(self::EMPLOYEE_BASES);

        foreach (self::PAYROLL_MONTHS as $monthIndex => $month) {
            foreach ($matricules as $employeeIndex => $matricule) {
                $employee = $employees->get($matricule);

                if (!$employee) {
                    continue;
                }

                $base = self::EMPLOYEE_BASES[$matricule];
                $status = self::STATUS_PATTERNS[$month][$employeeIndex];
                $net = $base + ($monthIndex * 15000);
                $createdAt = $this->monthDateTime($month, 2, 8 + $employeeIndex, 0);
                $validatedAt = $this->monthDateTime($month, 27, 17, $employeeIndex * 5);
                $paymentRequestedAt = $status === 'non_paye'
                    ? null
                    : $this->monthDateTime($month, 28, 9 + ($employeeIndex % 4), ($employeeIndex * 10) % 60);
                $paidOn = $status === 'paye'
                    ? $this->monthDate($month, 28)
                    : null;
                $updatedAt = $status === 'paye'
                    ? $this->monthDateTime($month, 28, 9 + ($employeeIndex % 4), 15 + (($employeeIndex * 10) % 40))
                    : ($paymentRequestedAt ?: $validatedAt);

                $paie = Paie::query()->create([
                    'employe_id' => $employee->id,
                    'mois' => $month,
                    'salaire_base' => $base,
                    'heures_travaillees' => 173.33,
                    'heures_supplementaires' => 0,
                    'montant_hs' => 0,
                    'prime_transport' => 0,
                    'prime_presence' => 0,
                    'autres_primes' => $monthIndex * 5000,
                    'retenue_cnaps' => 0,
                    'retenue_ostie' => 0,
                    'retenue_irsa' => 0,
                    'total_brut' => $net,
                    'total_retenues' => 0,
                    'net_a_payer' => $net,
                    'paye_le' => $paidOn,
                    'statut' => $status,
                    'demande_validation_le' => $paymentRequestedAt,
                    'valide_le' => $validatedAt,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ]);

                $movement = $this->movementPayloadForStatus(
                    $status,
                    $month,
                    $employeeIndex,
                    $employee,
                    $net,
                    $paymentRequestedAt,
                    $updatedAt
                );

                if (!$movement) {
                    continue;
                }

                $caisse = $caisses->get($movement['caisse']);

                if (!$caisse) {
                    continue;
                }

                DB::table('caisse_mouvements')->insert([
                    'caisse_id' => $caisse->id,
                    'paie_id' => $paie->id,
                    'type' => 'sortie',
                    'categorie' => 'paie_employe',
                    'montant' => $movement['montant'],
                    'source' => 'Paiement fiche de paie ' . $month,
                    'description' => $movement['description'],
                    'statut' => $movement['statut'],
                    'demande_validation_le' => $movement['demande_validation_le'],
                    'valide_le' => $movement['valide_le'],
                    'created_at' => $movement['created_at'],
                    'updated_at' => $movement['updated_at'],
                ]);
            }
        }
    }

    private function movementPayloadForStatus(
        string $status,
        string $month,
        int $employeeIndex,
        Employe $employee,
        float $amount,
        ?string $paymentRequestedAt,
        string $updatedAt
    ): ?array {
        if ($status === 'non_paye') {
            return null;
        }

        $cashbox = self::CASHBOX_ROTATION[($employeeIndex + (int) substr($month, -2)) % count(self::CASHBOX_ROTATION)];
        $fullName = trim($employee->nom . ' ' . $employee->prenom);

        return [
            'caisse' => $cashbox,
            'montant' => $amount,
            'statut' => $status === 'paye' ? 'valide' : 'en_attente_validation',
            'description' => $status === 'paye'
                ? "Paiement de la fiche de paie de {$fullName}"
                : "Paiement en attente pour {$fullName}",
            'demande_validation_le' => $paymentRequestedAt,
            'valide_le' => $status === 'paye' ? $updatedAt : null,
            'created_at' => $paymentRequestedAt,
            'updated_at' => $updatedAt,
        ];
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
