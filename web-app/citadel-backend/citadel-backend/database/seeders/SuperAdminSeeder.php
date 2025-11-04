<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete existing superadmin if exists
        Account::where('email', 'superadmin@citadel.edu')
            ->orWhere('username', 'superadmin')
            ->delete();

        // Create default super admin account
        $password = 'admin123'; // Simple password for testing
        Account::create([
            'fullname' => 'Super Administrator',
            'college_id' => null,
            'dob' => '1990-01-01',
            'role' => 'super_admin',
            'gender' => 'Male',
            'address' => 'University Campus',
            'contact' => '09123456789',
            'email' => 'superadmin@citadel.edu',
            'username' => 'superadmin',
            'password' => Hash::make($password),
        ]);

        $this->command->info('Super Admin account created successfully!');
        $this->command->info('Email: superadmin@citadel.edu');
        $this->command->info('Username: superadmin');
        $this->command->info('Password: admin123');
    }
}
