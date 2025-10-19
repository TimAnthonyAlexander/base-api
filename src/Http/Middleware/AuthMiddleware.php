<?php

namespace BaseApi\Http\Middleware;

use Override;
use BaseApi\Http\Middleware;
use BaseApi\Http\Request;
use BaseApi\Http\Response;
use BaseApi\Http\JsonResponse;
use BaseApi\App;

/**
 * Middleware to protect routes requiring authentication.
 * Checks for $_SESSION['user_id'] and attaches user to request.
 */
class AuthMiddleware implements Middleware
{
    #[Override]
    public function handle(Request $request, callable $next): Response
    {

        // Check if user_id is set in session (SessionStartMiddleware handles session initialization)
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            return JsonResponse::error('Unauthorized', 401);
        }

        // Resolve user using UserProvider
        $userProvider = App::userProvider();
        $user = $userProvider->byId($_SESSION['user_id']);


        if ($user === null) {
            // User ID in session but user doesn't exist - clear invalid session
            unset($_SESSION['user_id']);
            return JsonResponse::error('Unauthorized', 401);
        }

        // Attach user and auth method to request for controller access
        $request->user = $user;
        $request->authMethod = 'session';

        return $next($request);
    }
}
