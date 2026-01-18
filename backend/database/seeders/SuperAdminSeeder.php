<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            [
                'email' => 'superadmin@moonolith.com',
            ],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('super123'),
                'phone_number' => '080000000000',
                'address' => 'Moonolith HQ',
                'role' => 'super',
            ]
        );
    }
}
