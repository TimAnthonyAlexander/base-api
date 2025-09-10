<?php

namespace BaseApi\Controllers;

use BaseApi\Http\JsonResponse;

/**
 * Protected endpoint to return current user information.
 * Requires AuthMiddleware to populate $request->user.
 */
class MeController extends Controller
{
    public function get(): JsonResponse
    {
        // User data should be attached by AuthMiddleware
        $user = $this->request->user ?? null;

        if ($user === null) {
            return JsonResponse::error('User not found in request', 500);
        }

        return JsonResponse::ok($user);
    }
}
