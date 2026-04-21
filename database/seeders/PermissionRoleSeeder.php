<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PermissionRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Admin']);
        $employeeRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Employee']);

        // Find the default test user and assign Admin role
        $user = \App\Models\User::first();
        if ($user) {
            $user->assignRole($adminRole);
        }
    }
}
