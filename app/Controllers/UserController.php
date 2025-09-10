<?php

namespace BaseApi\Controllers;

use BaseApi\Http\JsonResponse;
use BaseApi\Models\User;

class UserController extends Controller
{
    public ?int $id = null;
    public ?int $perPage = 10; // Test snake_case fallback (per_page)

    public function get(): JsonResponse
    {
        if ($this->id) {
            return JsonResponse::ok([
                'user' => User::firstWhere('id', '=', $this->id),
            ]);
        }

        return JsonResponse::ok([
            'users' => User::all($this->perPage),
            'perPage' => $this->perPage,
        ]);
    }

    public function delete(): JsonResponse
    {
        $this->validate([
            'id' => 'required|integer|min:1'
        ]);

        $user = User::firstWhere('id', '=', $this->id);
        if (!$user instanceof User) {
            return JsonResponse::notFound('User not found');
        }

        $user->delete();

        return JsonResponse::ok(['message' => "User {$this->id} deleted"]);
    }
}
