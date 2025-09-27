<?php

declare(strict_types=1);

namespace BaseApi\Http\Middleware;

use App\Models\ApiToken;
use Exception;
use Override;
use BaseApi\Http\Middleware;
use BaseApi\Http\Request;
use BaseApi\Http\Response;
use BaseApi\Http\JsonResponse;
use BaseApi\App;

/**
 * Middleware that supports both session-based and API token authentication.
 * Tries API token first, falls back to session auth.
 * This allows the same endpoint to work with both authentication methods.
 */
class CombinedAuthMiddleware implements Middleware
{
    #[Override]
    public function handle(Request $request, callable $next): Response
    {
        // First, try API token authentication
        $user = $this->tryApiTokenAuth($request);
        $authMethod = 'api_token';
        
        // If no token auth, try session auth
        if (!$user) {
            $user = $this->trySessionAuth();
            $authMethod = 'session';
        }
        
        // If neither method worked, return unauthorized
        if (!$user) {
            return JsonResponse::error('Unauthorized', 401);
        }
        
        // Attach user and auth method to request
        $request->user = $user;
        $request->authMethod = $authMethod;
        
        return $next($request);
    }
    
    /**
     * Try to authenticate via API token
     */
    private function tryApiTokenAuth(Request $request): ?array
    {
        // Look for Authorization header with Bearer token
        $authHeader = $request->headers['Authorization'] ?? $request->headers['authorization'] ?? null;
        
        if (!$authHeader || !str_starts_with((string) $authHeader, 'Bearer ')) {
            return null;
        }
        
        // Extract token from "Bearer <token>"
        $token = substr((string) $authHeader, 7);
        
        if ($token === '' || $token === '0') {
            return null;
        }
        
        try {
            // Try to use the App\Models\ApiToken class if it exists
            if (class_exists('App\Models\ApiToken')) {
                $tokenModel = ApiToken::findByToken($token);
                
                if (!$tokenModel) {
                    return null;
                }
                
                // Check if token is expired
                if ($tokenModel->isExpired()) {
                    return null;
                }
                
                // Get user via UserProvider
                $userProvider = App::userProvider();
                $user = $userProvider->byId($tokenModel->user_id);
                
                if ($user) {
                    // Update last used timestamp
                    $tokenModel->updateLastUsed();
                }
                
                return $user;
            }
            
            return null;
        } catch (Exception) {
            return null;
        }
    }
    
    /**
     * Try to authenticate via session
     */
    private function trySessionAuth(): ?array
    {
        // Check if user_id is set in session
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            return null;
        }

        try {
            // Resolve user using UserProvider
            $userProvider = App::userProvider();
            $user = $userProvider->byId($_SESSION['user_id']);

            if ($user === null) {
                // User ID in session but user doesn't exist - clear invalid session
                unset($_SESSION['user_id']);
                return null;
            }

            return $user;
        } catch (Exception) {
            return null;
        }
    }
}
