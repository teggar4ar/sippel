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
    /** @var array<string,string> Role → redirect path (admin/unknown falls through to Filament). */
    private const array ROLE_REDIRECTS = [
        'teacher' => '/guru',
        'student' => '/siswa',
    ];

    public function toResponse($request): RedirectResponse|Redirector
    {
        /** @var User|null $user */
        $user = Auth::user();

        foreach (self::ROLE_REDIRECTS as $role => $route) {
            if ($user?->hasRole($role)) {
                return redirect($route);
            }
        }

        // Admin and unknown roles go to FilamentPHP dashboard
        return redirect()->intended(Filament::getUrl());
    }
}
