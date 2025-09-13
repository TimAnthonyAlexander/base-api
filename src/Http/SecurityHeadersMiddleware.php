<?php

declare(strict_types=1);

namespace BaseApi\Http;

final class SecurityHeadersMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        $security = [
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy'        => 'no-referrer',
            'X-Frame-Options'        => 'DENY',
            'Permissions-Policy'     => 'geolocation=(), microphone=(), camera=()',
        ];

        foreach ($security as $name => $value) {
            if (!$this->hasHeader($response, $name)) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $response;
    }

    private function hasHeader(Response $response, string $name): bool
    {
        foreach ($response->headers as $k => $_) {
            if (strcasecmp($k, $name) === 0) {
                return true;
            }
        }
        return false;
    }
}
