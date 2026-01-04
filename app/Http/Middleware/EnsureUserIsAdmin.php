<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to restrict Filament panel access to admin users only.
 * This runs AFTER authentication, so teachers/students who authenticated
 * via the login form will be redirected to their appropriate interface.
 */
final class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Allow login and logout pages for all users
        if ($request->routeIs('filament.*.auth.login', 'filament.*.auth.logout')) {
            return $next($request);
        }

        // If not authenticated, let Filament handle it
        if (! Auth::check()) {
            return $next($request);
        }

        /** @var User $user */
        $user = Auth::user();

        // Admin users can access the panel
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        // Non-admin users should be redirected to their interface
        if ($user->hasRole('teacher')) {
            return redirect('/teacher');
        }

        if ($user->hasRole('student')) {
            return redirect('/student');
        }

        // Fallback: redirect to login
        return redirect('/app/login');
    }
}
