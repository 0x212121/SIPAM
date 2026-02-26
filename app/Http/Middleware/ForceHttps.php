<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        // Force HTTPS scheme for URL generation
        if ($request->header('X-Forwarded-Proto') === 'https' || app()->environment('production')) {
            \URL::forceScheme('https');
        }

        // Redirect to HTTPS if not secure (except for local/testing)
        if (!$request->secure() && !app()->environment(['local', 'testing']) && !$request->header('X-Forwarded-Proto')) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
