<?php

namespace BaseApi\Controllers;

use BaseApi\Http\JsonResponse;

/**
 * Logout endpoint to clear session authentication.
 */
class LogoutController extends Controller
{
    public function post(): JsonResponse
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Clear user ID from session
        unset($_SESSION['user_id']);

        // Regenerate session ID
        session_regenerate_id(true);

        return JsonResponse::ok([
            'loggedOut' => true
        ]);
    }
}
