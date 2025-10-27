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
        // Create default super admin account
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
            'password' => Hash::make('superadmin@citadel.edu'),
        ]);

        $this->command->info('Super Admin account created successfully!');
        $this->command->info('Email: superadmin@citadel.edu');
        $this->command->info('Username: superadmin');
        $this->command->info('Password: superadmin@citadel.edu');
    }
}
