<?php

namespace Database\Seeders;

use App\Models\BusinessOwner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BusinessOwnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BusinessOwner::firstOrCreate(
            ['email' => 'owner@aklasaas.com'],
            [
                'name' => 'SaaS Owner',
                'password' => Hash::make('password'),
            ]
        );
    }
}
