<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // CSP - Nonaktif untuk development, aktifkan untuk production
        if (app()->environment('production')) {
            $csp = "default-src 'self'; " .
                   "script-src 'self' 'unsafe-inline' https://static.cloudflareinsights.com https://unpkg.com; " .
                   "style-src 'self' 'unsafe-inline' https://unpkg.com; " .
                   "connect-src 'self' https://cloudflareinsights.com; " .
                   "img-src 'self' data: blob: https://*.tile.openstreetmap.org;";
            $response->headers->set('Content-Security-Policy', $csp);
        }
        
        // Add CORS headers
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        return $response;
    }
}
