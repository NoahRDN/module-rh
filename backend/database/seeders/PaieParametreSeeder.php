<?php

namespace Database\Seeders;

use App\Models\PaieParametre;
use Illuminate\Database\Seeder;

class PaieParametreSeeder extends Seeder
{
    public function run(): void
    {
        PaieParametre::firstOrCreate([], [
            'cnaps' => 1.0,
            'ostie' => 1.0,
            'irsa_base' => 350000,
            'irsa_taux' => 20,
            'hs_taux' => 1.3,
            'prime_transport' => 0,
            'prime_presence' => 0,
        ]);
    }
}
