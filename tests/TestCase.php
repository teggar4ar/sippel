<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions before each test (required for RBAC)
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create([
            'name' => config('app.default_user.name'),
            'email' => config('app.default_user.email'),
            'password' => config('app.default_user.password'),
        ]);

        // Assign admin role for testing (required for RBAC)
        $user->assignRole('admin');

        $this->actingAs($user);

        $this->withoutVite();
    }
}
