<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Master data management
            'manage master data',
            'manage classes',
            'manage subjects',
            'manage academic year',

            // Learning activities
            'view activities',
            'create activities',
            'edit activities',
            'delete activities',

            // Student data
            'view students',
            'view own data',

            // Reports
            'generate reports',
            'view reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Admin role - full access
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Teacher role - can manage activities and view students
        $teacherRole = Role::create(['name' => 'teacher']);
        $teacherRole->givePermissionTo([
            'view activities',
            'create activities',
            'edit activities',
            'delete activities',
            'view students',
            'generate reports',
            'view reports',
        ]);

        // Student role - can only view own data
        $studentRole = Role::create(['name' => 'student']);
        $studentRole->givePermissionTo([
            'view own data',
            'view reports',
        ]);
    }
}
