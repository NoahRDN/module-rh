<?php

namespace Database\Seeders;

use App\Models\Employe;
use App\Models\Pointage;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PointageSeeder extends Seeder
{
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

    private const MONTHS = [
        '2025-11',
        '2025-12',
        '2026-01',
        '2026-02',
        '2026-03',
        '2026-04',
    ];

    public function run(): void
    {
        $employees = Employe::query()
            ->whereIn('matricule', self::EMPLOYEE_MATRICULES)
            ->orderBy('id')
            ->get(['id', 'matricule']);

        if ($employees->isEmpty()) {
            return;
        }

        foreach (self::MONTHS as $monthIndex => $month) {
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
                if ($day->isWeekend()) {
                    continue;
                }

                foreach ($employees as $employeeIndex => $employee) {
                    $minuteShift = ($employeeIndex * 3) + ($monthIndex * 2) + ($day->day % 4);
                    $entry = $this->shiftTime('08:00:00', $minuteShift);
                    $pauseStart = $this->shiftTime('12:00:00', $minuteShift % 8);
                    $pauseEnd = $this->shiftTime('13:00:00', $minuteShift % 7);
                    $exit = $this->shiftTime('17:15:00', ($minuteShift % 9) + ($day->day % 3));

                    $this->storePointage($employee->id, 'entree', $day->toDateString(), $entry);
                    $this->storePointage($employee->id, 'pause_debut', $day->toDateString(), $pauseStart);
                    $this->storePointage($employee->id, 'pause_fin', $day->toDateString(), $pauseEnd);
                    $this->storePointage($employee->id, 'sortie', $day->toDateString(), $exit);
                }
            }
        }
    }

    private function storePointage(int $employeId, string $type, string $date, string $time): void
    {
        Pointage::query()->updateOrCreate(
            [
                'employe_id' => $employeId,
                'type' => $type,
                'pointe_a' => "{$date} {$time}",
            ],
            [
                'source' => 'seed',
                'commentaire' => 'Pointage de démonstration',
            ]
        );
    }

    private function shiftTime(string $time, int $minutes): string
    {
        return date('H:i:s', strtotime($time) + ($minutes * 60));
    }
}
