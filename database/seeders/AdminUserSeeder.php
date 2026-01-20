<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $superAdminRole = Role::where('slug', Role::SUPER_ADMIN)->first();

        if (!$superAdminRole) {
            $this->command->error('Super Admin role not found. Please run PermissionSeeder and RoleSeeder first.');
            return;
        }

        // Create default super admin user
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'المسؤول العام',
                'password' => Hash::make('password'),
                'role_id' => $superAdminRole->id,
                'is_active' => true,
            ]
        );

        $this->command->info('Default admin user created:');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Password: password');
        $this->command->warn('Please change the password after first login!');
    }
}
