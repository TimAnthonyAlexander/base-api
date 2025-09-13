<?php

declare(strict_types=1);

namespace BaseApi\Http;

use BaseApi\Http\Request;
use BaseApi\Http\Response;

final class SecurityHeadersMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        return $next($request)->withHeaders([
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
            'X-Frame-Options' => 'DENY',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        ]);
    }
}
