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
 * Middleware to authenticate users via API tokens.
 * Checks for Bearer token in Authorization header and attaches user to request.
 */
class ApiTokenAuthMiddleware implements Middleware
{
    #[Override]
    public function handle(Request $request, callable $next): Response
    {
        // Look for Authorization header with Bearer token
        $authHeader = $request->headers['Authorization'] ?? $request->headers['authorization'] ?? null;
        
        if (!$authHeader || !str_starts_with((string) $authHeader, 'Bearer ')) {
            return JsonResponse::error('Unauthorized', 401);
        }
        
        // Extract token from "Bearer <token>"
        $token = substr((string) $authHeader, 7);
        
        if ($token === '' || $token === '0') {
            return JsonResponse::error('Unauthorized', 401);
        }
        
        // Find and validate token using the configured UserProvider
        // We'll need to extend the UserProvider interface or create a token-specific method
        $user = $this->validateTokenAndGetUser($token);
        
        if ($user === null) {
            return JsonResponse::error('Unauthorized', 401);
        }
        
        // Attach user to request for controller access
        $request->user = $user;
        $request->authMethod = 'api_token';
        
        return $next($request);
    }
    
    /**
     * Validate token and return user data
     * This method handles the token validation logic
     */
    private function validateTokenAndGetUser(string $token): ?array
    {
        // Since we can't modify the UserProvider interface in the core framework,
        // we'll use a more direct approach by checking if common token models exist
        
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
                    // Update last used timestamp (async to avoid performance impact)
                    $tokenModel->updateLastUsed();
                }
                
                return $user;
            }
            
            return null;
        } catch (Exception) {
            return null;
        }
    }
}
