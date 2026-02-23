<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view bookings',
            'create bookings',
            'edit bookings',
            'delete bookings',
            'view expenses',
            'create expenses',
            'edit expenses',
            'delete expenses',
            'view discounts',
            'create discounts',
            'edit discounts',
            'delete discounts',
            'view settings',
            'edit settings',
            'export data',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Staff role
        $staff = Role::firstOrCreate(['name' => 'staff']);
        $staff->syncPermissions([
            'view bookings',
            'create bookings',
            'view expenses',
            'create expenses',
        ]);

        // Create Admin role - all permissions
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());
    }
}
