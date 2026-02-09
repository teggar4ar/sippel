<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ForceHttpsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Force HTTPS in production
        if (app()->environment('production') && config('app.force_https', false)) {
            // Check if request is not secure before mutating server vars
            if (! $request->isSecure() && $request->header('X-Forwarded-Proto') !== 'https') {
                return redirect()->secure($request->getRequestUri());
            }

            // Set HTTPS flag for secure requests
            $request->server->set('HTTPS', 'on');
        }

        return $next($request);
    }
}
