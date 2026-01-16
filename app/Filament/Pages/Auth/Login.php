<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Auth\Pages\Login as BasePage;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class Login extends BasePage
{
    /**
     * Override to show Indonesian error message for failed login.
     */
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => __('Email atau kata sandi salah.'),
        ]);
    }

    /**
     * Custom heading for login page.
     */
    public function getHeading(): string
    {
        return 'Login ke SIPPEL';
    }

    public function mount(): void
    {
        // If already authenticated, redirect based on role
        if (Filament::auth()->check()) {
            redirect($this->getRoleBasedRedirectUrl());

            return;
        }

        $this->form->fill();
    }

    /**
     * Get the appropriate redirect URL based on user role.
     */
    private function getRoleBasedRedirectUrl(): string
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return Filament::getUrl();
        }

        // Admin goes to FilamentPHP dashboard
        if ($user->hasRole('admin')) {
            return Filament::getUrl();
        }

        // Teacher goes to Flux UI teacher interface
        if ($user->hasRole('teacher')) {
            return '/teacher';
        }

        // Student goes to Flux UI student interface
        if ($user->hasRole('student')) {
            return '/student';
        }

        // Fallback to FilamentPHP dashboard
        return Filament::getUrl();
    }
}
