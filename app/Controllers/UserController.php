<?php

namespace BaseApi\Controllers;

use BaseApi\Http\JsonResponse;

class UserController extends Controller
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $email = null;
    public ?int $perPage = 10; // Test snake_case fallback (per_page)

    public function get(): JsonResponse
    {
        if ($this->id) {
            return JsonResponse::ok([
                'user' => [
                    'id' => $this->id,
                    'name' => $this->name,
                    'email' => $this->email
                ]
            ]);
        }

        return JsonResponse::ok([
            'users' => [
                ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com']
            ],
            'perPage' => $this->perPage
        ]);
    }

    public function post(): JsonResponse
    {
        $this->validate([
            'name' => 'required|min:2|max:100',
            'email' => 'required|email'
        ]);

        return JsonResponse::created([
            'user' => [
                'id' => 123,
                'name' => $this->name,
                'email' => $this->email
            ]
        ]);
    }

    public function delete(): JsonResponse
    {
        $this->validate([
            'id' => 'required|integer'
        ]);

        return JsonResponse::ok(['message' => "User {$this->id} deleted"]);
    }
}
