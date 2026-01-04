<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportRedirects\Redirector;

final class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return redirect()->intended(Filament::getUrl());
        }

        // Admin goes to FilamentPHP dashboard
        if ($user->hasRole('admin')) {
            return redirect()->intended(Filament::getUrl());
        }

        // Teacher goes to Flux UI teacher interface
        if ($user->hasRole('teacher')) {
            return redirect('/teacher');
        }

        // Student goes to Flux UI student interface
        if ($user->hasRole('student')) {
            return redirect('/student');
        }

        // Fallback to FilamentPHP dashboard
        return redirect()->intended(Filament::getUrl());
    }
}
