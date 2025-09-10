<?php

namespace BaseApi\Controllers;

use BaseApi\Http\JsonResponse;
use BaseApi\Http\Attributes\ResponseType;
use BaseApi\Http\Attributes\Tag;

/**
 * Minimal login endpoint for session authentication.
 * This is a stub - real credential validation is out of scope.
 */
#[Tag('Authentication')]
class LoginController extends Controller
{
    public string $userId = '';

    #[ResponseType(['userId' => 'string'])]
    public function post(): JsonResponse
    {
        $this->validate([
            'userId' => 'required|string'
        ]);

        // Set user ID in session (SessionStartMiddleware handles session initialization)
        $_SESSION['user_id'] = $this->userId;


        // Regenerate session ID to mitigate fixation attacks
        session_regenerate_id(true);


        return JsonResponse::ok([
            'userId' => $this->userId
        ]);
    }
}
