<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RolePermissionSeeder extends Seeder
{
    private const string PERM_VIEW_REPORTS = 'view reports';

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
            self::PERM_VIEW_REPORTS,
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(Permission::all());

        $teacherRole = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        $teacherRole->givePermissionTo([
            'view activities',
            'create activities',
            'edit activities',
            'delete activities',
            'view students',
            'generate reports',
            'view reports',
        ]);

        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $studentRole->givePermissionTo([
            'view own data',
            self::PERM_VIEW_REPORTS,
        ]);
    }
}
