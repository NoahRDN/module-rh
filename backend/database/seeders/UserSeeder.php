<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name'       => 'Admin RH',
                'email'      => 'admin@rh.test',
                // 'password'   => Hash::make('password'),
                'password'   => 'password',
                'role'       => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Responsable RH',
                'email'      => 'rh@rh.test',
                'password'   => 'password',
                'role'       => 'rh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Manager',
                'email'      => 'manager@rh.test',
                'password'   => 'password',
                'role'       => 'manager',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
