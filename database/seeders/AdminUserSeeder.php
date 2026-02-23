<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@padel.com'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('admin');

        // Create staff user
        $staff = User::firstOrCreate(
            ['email' => 'staff@padel.com'],
            [
                'name'     => 'Staff',
                'password' => Hash::make('password'),
            ]
        );
        $staff->assignRole('staff');

        // Create default settings
        Setting::firstOrCreate(['key' => 'hour_price'], ['value' => 100]);

        // Create some sample discounts
        Discount::firstOrCreate(['name' => '10% Off'], [
            'type'      => 'percentage',
            'value'     => 10,
            'is_active' => true,
        ]);

        Discount::firstOrCreate(['name' => '20% Off'], [
            'type'      => 'percentage',
            'value'     => 20,
            'is_active' => true,
        ]);

        Discount::firstOrCreate(['name' => 'Fixed EGP 50 Off'], [
            'type'      => 'fixed',
            'value'     => 50,
            'is_active' => true,
        ]);
    }
}
