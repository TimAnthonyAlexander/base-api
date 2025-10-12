<?php

namespace BaseApi\Permissions;

use BaseApi\Http\Middleware;
use BaseApi\Http\Request;
use BaseApi\Http\Response;
use BaseApi\Http\JsonResponse;
use Override;

/**
 * Middleware to protect routes requiring specific permissions.
 * 
 * Usage in routes:
 *   PermissionsMiddleware::class => ['node' => 'content.create']
 */
class PermissionsMiddleware implements Middleware
{
    public function __construct(
        private readonly PermissionsService $permissions
    ) {
    }

    #[Override]
    public function handle(Request $request, callable $next): Response
    {
        $user = $request->user ?? null;
        
        if ($user === null) {
            return JsonResponse::error('Unauthorized', 401);
        }

        // User is always an array (per Request type definition)
        $userId = $user['id'] ?? null;
        
        if ($userId === null) {
            return JsonResponse::error('Unauthorized', 401);
        }

        // Get required permission node from middleware options
        $node = $request->middlewareOptions[self::class]['node'] ?? null;
        
        if ($node === null) {
            // No specific permission required, just authentication
            return $next($request);
        }

        // Check permission
        if (!$this->permissions->check($userId, $node)) {
            return JsonResponse::error('Forbidden', 403);
        }

        return $next($request);
    }
}

