<?php

namespace BaseApi\Controllers;

use BaseApi\Http\JsonResponse;
use BaseApi\Models\User;
use BaseApi\Http\Attributes\ResponseType;
use BaseApi\Http\Attributes\Tag;

#[Tag('Users')]
class UserController extends Controller
{
    public ?int $id = null;
    public ?int $perPage = 10; // Test snake_case fallback (per_page)

    #[ResponseType(['user' => User::class], when: 'single')]
    #[ResponseType(['users' => 'User[]', 'perPage' => 'int'], when: 'list')]
    public function get(): JsonResponse
    {
        if ($this->id) {
            $user = User::firstWhere('id', '=', $this->id);

            return JsonResponse::ok([
                'user' => $user,
            ], $user instanceof User ? 200 : 404);
        }

        return JsonResponse::ok([
            'users' => User::all($this->perPage),
            'perPage' => $this->perPage,
        ]);
    }

    #[ResponseType(['message' => 'string'])]
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
