<?php

namespace BaseApi\Controllers;

use BaseApi\Http\JsonResponse;
use BaseApi\Http\UploadedFile;

class TestController extends Controller
{
    // Test basic binding and coercion (use mixed for validation testing)
    public mixed $name = 'default';
    public mixed $age = 0;
    public mixed $active = false;
    public mixed $tags = [];
    public mixed $email = null;
    public ?UploadedFile $avatar = null;
    
    // Test precedence: route params override query
    public ?int $userId = null;
    
    // Test snake_case fallback
    public ?string $firstName = null;

    public function get(): JsonResponse
    {
        return JsonResponse::ok([
            'name' => $this->name,
            'age' => $this->age,
            'active' => $this->active,
            'tags' => $this->tags,
            'email' => $this->email,
            'userId' => $this->userId,
            'firstName' => $this->firstName,
            'avatar' => $this->avatar ? [
                'name' => $this->avatar->name,
                'size' => $this->avatar->size,
                'type' => $this->avatar->type,
                'valid' => $this->avatar->isValid()
            ] : null
        ]);
    }

    public function post(): JsonResponse
    {
        // Test validation
        $this->validate([
            'name' => 'required|min:2|max:50',
            'age' => 'required|integer|min:0|max:120',
            'email' => 'email',
            'tags' => 'array',
            'avatar' => 'file|mimes:jpg,png|size:5'
        ]);

        return JsonResponse::ok([
            'message' => 'Validation passed',
            'data' => [
                'name' => $this->name,
                'age' => $this->age,
                'email' => $this->email,
                'tags' => $this->tags
            ]
        ]);
    }
}
