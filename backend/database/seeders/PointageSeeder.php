<?php

namespace Database\Seeders;

use App\Models\Employe;
use App\Models\Pointage;
use Illuminate\Database\Seeder;

class PointageSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employe::query()
            ->orderBy('id')
            ->take(4)
            ->get(['id']);

        if ($employees->isEmpty()) {
            return;
        }

        $days = [
            ['date' => '2025-09-09', 'start' => '08:06:00', 'pause_start' => '12:01:00', 'pause_end' => '13:00:00', 'end' => '17:14:00'],
            ['date' => '2025-09-10', 'start' => '08:13:00', 'pause_start' => '12:08:00', 'pause_end' => '13:06:00', 'end' => '17:23:00'],
            ['date' => '2025-11-18', 'start' => '08:02:00', 'pause_start' => '12:00:00', 'pause_end' => '13:02:00', 'end' => '17:16:00'],
            ['date' => '2025-11-19', 'start' => '08:09:00', 'pause_start' => '12:05:00', 'pause_end' => '13:03:00', 'end' => '17:21:00'],
            ['date' => '2026-01-14', 'start' => '08:05:00', 'pause_start' => '12:02:00', 'pause_end' => '13:01:00', 'end' => '17:19:00'],
            ['date' => '2026-01-15', 'start' => '08:10:00', 'pause_start' => '12:07:00', 'pause_end' => '13:05:00', 'end' => '17:28:00'],
            ['date' => '2026-03-12', 'start' => '07:59:00', 'pause_start' => '12:00:00', 'pause_end' => '13:00:00', 'end' => '17:11:00'],
            ['date' => '2026-03-13', 'start' => '08:07:00', 'pause_start' => '12:04:00', 'pause_end' => '13:03:00', 'end' => '17:22:00'],
            ['date' => '2026-05-04', 'start' => '08:04:00', 'pause_start' => '12:03:00', 'pause_end' => '13:02:00', 'end' => '17:18:00'],
            ['date' => '2026-05-05', 'start' => '08:11:00', 'pause_start' => '12:06:00', 'pause_end' => '13:04:00', 'end' => '17:27:00'],
            ['date' => '2026-05-06', 'start' => '07:58:00', 'pause_start' => '12:01:00', 'pause_end' => '13:01:00', 'end' => '17:09:00'],
        ];

        foreach ($employees as $index => $employee) {
            foreach ($days as $offset => $day) {
                $minuteShift = ($index * 3) + $offset;

                $this->storePointage($employee->id, 'entree', $day['date'], $this->shiftTime($day['start'], $minuteShift));
                $this->storePointage($employee->id, 'pause_debut', $day['date'], $this->shiftTime($day['pause_start'], $minuteShift));
                $this->storePointage($employee->id, 'pause_fin', $day['date'], $this->shiftTime($day['pause_end'], $minuteShift));
                $this->storePointage($employee->id, 'sortie', $day['date'], $this->shiftTime($day['end'], $minuteShift));
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
