<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class RedirectBasedOnRole
{
    /**
     * Handle an incoming request.
     * Protects role-specific routes (/teacher, /student).
     * Note: /app protection is handled by User::canAccessPanel()
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            // Protect teacher routes - only teachers can access
            if (($request->is('teacher') || $request->is('teacher/*')) && ! $user->hasRole('teacher')) {
                abort(403, 'Akses tidak diizinkan. Halaman ini hanya untuk guru.');
            }

            // Protect student routes - only students can access
            if (($request->is('student') || $request->is('student/*')) && ! $user->hasRole('student')) {
                abort(403, 'Akses tidak diizinkan. Halaman ini hanya untuk siswa.');
            }
        }

        $response = $next($request);

        // Prevent browser caching for all role-specific routes
        // This ensures back button always makes a fresh server request
        if ($request->is('teacher', 'teacher/*', 'student', 'student/*', 'app', 'app/*')) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        }

        return $response;
    }
}
