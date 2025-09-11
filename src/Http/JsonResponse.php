<?php

namespace BaseApi\Http;

class JsonResponse extends Response
{
    public function __construct(mixed $data, int $status = 200, array $headers = [])
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        parent::__construct($status, $headers, $body);
    }

    public static function ok(mixed $payload, int $status = 200): self
    {
        return new self(['data' => $payload], $status);
    }

    public static function created(mixed $payload): self
    {
        return new self(['data' => $payload], 201);
    }

    public static function noContent(): Response
    {
        return new Response(204);
    }

    public static function badRequest(string $message, array $errors = []): self
    {
        $data = [
            'error' => $message,
            'requestId' => self::getCurrentRequestId()
        ];
        
        if (!empty($errors)) {
            $data['errors'] = $errors;
        }
        
        return new self($data, 400);
    }

    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self([
            'error' => $message,
            'requestId' => self::getCurrentRequestId()
        ], 401);
    }

    public static function notFound(string $message = 'Not Found'): self
    {
        return new self([
            'error' => $message,
            'requestId' => self::getCurrentRequestId()
        ], 404);
    }

    public static function error(string $message = 'Server Error', int $status = 500): self
    {
        return new self([
            'error' => $message,
            'requestId' => self::getCurrentRequestId()
        ], $status);
    }

    private static function getCurrentRequestId(): ?string
    {
        // Get from Logger's static property set by RequestIdMiddleware
        return \BaseApi\Logger::getRequestId();
    }
}
