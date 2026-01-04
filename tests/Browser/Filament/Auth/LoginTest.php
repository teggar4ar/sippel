<?php

declare(strict_types=1);

/** @var Tests\TestCase $this */

use App\Models\User;

beforeEach(function (): void {
    auth()->logout();

    // Create user with role (roles are already seeded in TestCase)
    $this->user = User::factory()->create([
        'email' => 'demo@pestphp.com',
        'password' => 'password',
    ]);
    $this->user->assignRole('admin');
});

test('an unauthenticated user can login', function (): void {
    visit('/app/login')
        ->fill('form.email', $this->user->email)
        ->fill('form.password', 'password')
        ->submit()
        ->assertSee('Dashboard');

    $this->assertAuthenticated();
})->skip('Playwright needs updating. Run: npm install playwright@latest && npx playwright install');
