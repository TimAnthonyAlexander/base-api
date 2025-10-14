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
 *   // Single permission
 *   PermissionsMiddleware::class => ['node' => 'content.create']
 * 
 *   // Require ALL permissions
 *   PermissionsMiddleware::class => ['requiresAll' => ['content.create', 'content.publish']]
 * 
 *   // Require ANY permission (at least one)
 *   PermissionsMiddleware::class => ['requiresAny' => ['content.create', 'admin.content']]
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

        $options = $request->middlewareOptions[self::class] ?? [];

        // Handle single node (backward compatible)
        if (isset($options['node'])) {
            if (!$this->permissions->check($userId, $options['node'])) {
                return JsonResponse::error('Forbidden', 403);
            }

            return $next($request);
        }

        // Handle requiresAll (must have all listed permissions)
        if (isset($options['requiresAll'])) {
            $nodes = is_array($options['requiresAll']) ? $options['requiresAll'] : [$options['requiresAll']];
            foreach ($nodes as $node) {
                if (!$this->permissions->check($userId, $node)) {
                    return JsonResponse::error('Forbidden', 403);
                }
            }

            return $next($request);
        }

        // Handle requiresAny (must have at least one listed permission)
        if (isset($options['requiresAny'])) {
            $nodes = is_array($options['requiresAny']) ? $options['requiresAny'] : [$options['requiresAny']];
            $hasAny = false;
            foreach ($nodes as $node) {
                if ($this->permissions->check($userId, $node)) {
                    $hasAny = true;
                    break;
                }
            }

            if (!$hasAny) {
                return JsonResponse::error('Forbidden', 403);
            }

            return $next($request);
        }

        // No specific permission required, just authentication
        return $next($request);
    }
}

