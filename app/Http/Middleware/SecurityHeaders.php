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

        // Add CSP header to allow Cloudflare Insights and Leaflet from unpkg
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://static.cloudflareinsights.com https://unpkg.com; " .
               "style-src 'self' 'unsafe-inline' https://unpkg.com; " .
               "connect-src 'self' https://cloudflareinsights.com http://unraid.iwlab.web.id https://unraid.iwlab.web.id; " .
               "img-src 'self' data: blob: https://*.tile.openstreetmap.org;";

        $response->headers->set('Content-Security-Policy', $csp);
        
        // Add CORS headers
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        return $response;
    }
}
