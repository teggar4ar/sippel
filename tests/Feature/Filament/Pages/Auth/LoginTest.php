<?php

declare(strict_types=1);

/** @var Tests\TestCase $this */

use App\Filament\Pages\Auth\Login;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;

use function Pest\Livewire\livewire;

test('an unauthenticated user can access the login page', function (): void {
    Auth::logout();

    $this->get(Filament::getLoginUrl())
        ->assertOk();
});

test('an unauthenticated user can not access the admin panel', function (): void {
    Auth::logout();

    $this->get('app')
        ->assertRedirect(Filament::getLoginUrl());
});

test('an unauthenticated user can login', function (): void {
    Auth::logout();

    livewire(Login::class)
        ->fillForm([
            'email' => config('app.default_user.email'),
            'password' => config('app.default_user.password'),
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();
});

test('an authenticated user can access the admin panel', function (): void {
    $this->get('app')
        ->assertOk();
});

test('an authenticated user can logout', function (): void {
    $this->assertAuthenticated();

    // Disable CSRF validation for this test
    $this->withoutMiddleware(Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

    $this->post(Filament::getLogoutUrl())
        ->assertRedirect(Filament::getLoginUrl());
});
