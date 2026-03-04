<?php

namespace BaseApi\Http;

use BaseApi\App;
use BaseApi\Database\PaginatedResult;
use Override;
use BaseApi\Logger;

class JsonResponse extends Response
{
    public function __construct(mixed $data, int $status = 200, array $headers = [])
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';

        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        parent::__construct($status, $headers, $body);
    }

    public static function ok(mixed $payload, int $status = 200, ?bool $wrap = null): self
    {
        if ($wrap ?? self::shouldWrapData()) {
            return new self(['data' => $payload], $status);
        }

        return new self($payload, $status);
    }

    public static function created(mixed $payload, ?bool $wrap = null): self
    {
        if ($wrap ?? self::shouldWrapData()) {
            return new self(['data' => $payload], 201);
        }

        return new self($payload, 201);
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

        if ($errors !== []) {
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

    public static function success(mixed $data = null, int $status = 200, array $meta = [], ?bool $wrap = null): self
    {
        $metaData = array_merge([
            'timestamp' => date('c'),
            'request_id' => self::getCurrentRequestId()
        ], $meta);

        if ($wrap ?? self::shouldWrapData()) {
            return new self([
                'success' => true,
                'data' => $data,
                'meta' => $metaData,
            ], $status);
        }

        // Flat mode: merge payload at top level with meta
        $response = is_array($data) ? $data : ['data' => $data];
        $response['meta'] = $metaData;

        return new self($response, $status);
    }

    public static function accepted(mixed $data = null, ?bool $wrap = null): self
    {
        return self::success($data, 202, [], $wrap);
    }

    public static function validationError(array $errors, string $message = 'Validation failed'): self
    {
        return new self([
            'success' => false,
            'error' => $message,
            'errors' => $errors,
            'meta' => [
                'timestamp' => date('c'),
                'request_id' => self::getCurrentRequestId()
            ]
        ], 422);
    }

    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self([
            'success' => false,
            'error' => $message,
            'meta' => [
                'timestamp' => date('c'),
                'request_id' => self::getCurrentRequestId()
            ]
        ], 403);
    }

    public static function unprocessable(string $message, array $details = []): self
    {
        $data = [
            'success' => false,
            'error' => $message,
            'meta' => [
                'timestamp' => date('c'),
                'request_id' => self::getCurrentRequestId()
            ]
        ];

        if ($details !== []) {
            $data['details'] = $details;
        }

        return new self($data, 422);
    }

    public static function paginated(PaginatedResult $result, array $meta = [], ?bool $wrap = null): self
    {
        $pagination = [
            'page' => $result->page,
            'per_page' => $result->perPage,
            'total' => $result->total,
            'remaining' => $result->remaining,
        ];

        $metaData = array_merge([
            'timestamp' => date('c'),
            'request_id' => self::getCurrentRequestId()
        ], $meta);

        if ($wrap ?? self::shouldWrapData()) {
            return new self([
                'success' => true,
                'data' => $result->data,
                'pagination' => $pagination,
                'meta' => $metaData,
            ], 200, $result->headers());
        }

        return new self([
            'items' => $result->data,
            'pagination' => $pagination,
            'meta' => $metaData,
        ], 200, $result->headers());
    }

    public function withMeta(array $meta): self
    {
        $data = json_decode((string) $this->body, true);
        $data['meta'] = isset($data['meta']) ? array_merge($data['meta'], $meta) : $meta;

        $new = clone $this;
        $new->body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $new;
    }

    #[Override]
    public function withHeaders(array $headers): self
    {
        $new = clone $this;
        foreach ($headers as $name => $value) {
            $new->headers[$name] = $value;
        }

        return $new;
    }

    private static function shouldWrapData(): bool
    {
        return (bool) App::config('response.wrap_data', true);
    }

    private static function getCurrentRequestId(): ?string
    {
        // Get from Logger's static property set by RequestIdMiddleware
        return Logger::getRequestId();
    }
}
