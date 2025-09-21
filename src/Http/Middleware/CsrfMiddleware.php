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
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($req);
        }

        $session = $req->session;
        $expected = $session['csrf_token'] ?? bin2hex(random_bytes(32));
        $req->session['csrf_token'] = $expected;

        $provided = $req->headers['X-CSRF-Token'] ?: ($req->body['csrf_token'] ?? '');
        if (!hash_equals($expected, $provided)) {
            return JsonResponse::unauthorized('invalid CSRF token');
        }

        return $next($req);
    }
}
