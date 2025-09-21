<?php

namespace BaseApi\Http;

use Override;
use BaseApi\App;

class CorsMiddleware implements Middleware
{
    private array $defaultMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

    private array $defaultExpose = ['X-Request-Id', 'ETag'];

    private array $defaultAllowHeaders = ['Content-Type', 'Authorization', 'X-Requested-With', 'X-Request-Id'];

    #[Override]
    public function handle(Request $req, callable $next): Response
    {
        $config = App::config();
        $allowlist = array_values(array_filter(array_map('trim', $config->list('CORS_ALLOWLIST'))));
        $maxAge = (int)($config->get('CORS_MAX_AGE', 600));

        $origin = $req->headers['Origin'] ?? $req->headers['ORIGIN'] ?? null;
        $hasOrigin = is_string($origin) && $origin !== '';
        $wildcard = in_array('*', $allowlist, true);
        $allowedExact = $hasOrigin && in_array($origin, $allowlist, true);

        if ($req->method === 'OPTIONS') {
            $response = new Response(204);

            if ($allowedExact || $wildcard) {
                $reqMethod = $req->headers['Access-Control-Request-Method'] ?? $req->headers['ACCESS-CONTROL-REQUEST-METHOD'] ?? null;
                $methods = $reqMethod ? [$reqMethod] : $this->defaultMethods;

                $reqHeaders = $req->headers['Access-Control-Request-Headers'] ?? $req->headers['ACCESS-CONTROL-REQUEST-HEADERS'] ?? null;
                $allowHeaders = $reqHeaders ?: implode(', ', $this->defaultAllowHeaders);

                if ($allowedExact) {
                    $response = $response
                        ->withHeader('Access-Control-Allow-Origin', $origin)
                        ->withHeader('Access-Control-Allow-Credentials', 'true');
                } else {
                    $response = $response->withHeader('Access-Control-Allow-Origin', '*');
                }

                $response = $response
                    ->withHeader('Access-Control-Allow-Methods', implode(', ', array_unique(array_map('strtoupper', $methods))))
                    ->withHeader('Access-Control-Allow-Headers', $allowHeaders)
                    ->withHeader('Access-Control-Max-Age', (string)$maxAge);
            }

            $response = $this->addVary($response, ['Origin', 'Access-Control-Request-Method', 'Access-Control-Request-Headers']);
            return $response;
        }

        $response = $next($req);

        if (!$hasOrigin) {
            return $response;
        }

        if ($allowedExact) {
            $response = $response
                ->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Access-Control-Allow-Credentials', 'true')
                ->withHeader('Access-Control-Expose-Headers', implode(', ', $this->defaultExpose));
        } elseif ($wildcard) {
            $response = $response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Expose-Headers', implode(', ', $this->defaultExpose));
        }
        return $this->addVary($response, ['Origin']);
    }

    private function addVary(Response $resp, array $keys): Response
    {
        $existing = $resp->headers['Vary'] ?? '';
        $current = array_map('trim', $existing ? explode(',', (string) $existing) : []);
        $merged = array_unique(array_filter(array_merge($current, $keys)));
        return $resp->withHeader('Vary', implode(', ', $merged));
    }
}
