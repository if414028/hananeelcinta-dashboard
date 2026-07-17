<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', "base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'");

        if ($request->is('admin/*') || $request->is('api/v1/auth/*') || $request->is('api/v1/me')) {
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('Pragma', 'no-cache');
        }
        if ($request->is('admin/*')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }
        if ((bool) config('security.hsts.enabled') && $request->isSecure()) {
            $value = 'max-age='.(int) config('security.hsts.max_age', 31536000);
            if ((bool) config('security.hsts.include_subdomains')) {
                $value .= '; includeSubDomains';
            }
            if ((bool) config('security.hsts.preload')) {
                $value .= '; preload';
            }
            $response->headers->set('Strict-Transport-Security', $value);
        }

        return $response;
    }
}
