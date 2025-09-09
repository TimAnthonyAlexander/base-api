<?php

namespace BaseApi\Http;

use BaseApi\App;

class CorsMiddleware implements Middleware
{
    public function handle(Request $req, callable $next): Response
    {
        $config = App::config();
        $allowlist = $config->list('CORS_ALLOWLIST');
        
        $origin = $req->headers['ORIGIN'] ?? $req->headers['Origin'] ?? null;
        $isAllowed = $origin && in_array($origin, $allowlist);
        

        // Handle preflight OPTIONS request
        if ($req->method === 'OPTIONS') {
            $response = new Response(204);
            
            if ($isAllowed) {
                $response = $response
                    ->withHeader('Access-Control-Allow-Origin', $origin)
                    ->withHeader('Access-Control-Allow-Credentials', 'true')
                    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS')
                    ->withHeader('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, X-Request-Id, Authorization');
            }
            
            // Always add Vary: Origin for OPTIONS requests to avoid cache poisoning
            $response = $response->withHeader('Vary', 'Origin');
            
            return $response;
        }

        // Process the actual request
        $response = $next($req);

        // Add CORS headers if origin is allowed
        if ($isAllowed) {
            $response = $response
                ->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Access-Control-Allow-Credentials', 'true')
                ->withHeader('Access-Control-Expose-Headers', 'X-Request-Id, ETag')
                ->withHeader('Vary', 'Origin');
        } elseif ($origin) {
            // Always add Vary: Origin when Origin header is present to avoid cache poisoning
            $response = $response->withHeader('Vary', 'Origin');
        }

        return $response;
    }
}
