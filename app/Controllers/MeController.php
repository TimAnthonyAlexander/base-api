<?php

namespace BaseApi\Controllers;

use BaseApi\Http\JsonResponse;
use BaseApi\Http\Attributes\ResponseType;
use BaseApi\Http\Attributes\Tag;
use BaseApi\Models\User;

/**
 * Protected endpoint to return current user information.
 * Requires AuthMiddleware to populate $request->user.
 */
#[Tag('Authentication')]
class MeController extends Controller
{
    #[ResponseType(User::class)]
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
