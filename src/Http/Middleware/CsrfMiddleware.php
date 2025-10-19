<?php

declare(strict_types=1);

namespace BaseApi\Http\Middleware;

use BaseApi\Http\JsonResponse;
use BaseApi\Http\Request;
use BaseApi\Http\Response;

final class CsrfMiddleware
{
    /**
     * @param callable(Request): Response $next
     */
    public function handle(Request $req, callable $next): Response
    {
        $method = strtoupper($req->method);
        
        // Skip CSRF for safe methods
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($req);
        }

        // Skip CSRF for API token authentication (stateless, token is proof)
        // Webhooks and SSO callbacks should use their own signature/nonce verification
        if ($req->authMethod !== null && $req->authMethod === 'api_token') {
            return $next($req);
        }

        // CSRF applies only to session-based authentication
        $session = $req->session;
        
        // Generate CSRF token if not present
        $expected = $session['csrf_token'] ?? bin2hex(random_bytes(32));
        $req->session['csrf_token'] = $expected;

        // Check provided token
        $provided = $req->headers['X-CSRF-Token'] ?? ($req->body['csrf_token'] ?? '');
        
        if ($provided === '' || !hash_equals($expected, (string) $provided)) {
            return JsonResponse::unauthorized('invalid CSRF token');
        }

        return $next($req);
    }
}
