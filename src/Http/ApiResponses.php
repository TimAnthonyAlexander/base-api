<?php

namespace BaseApi\Http;

use BaseApi\Database\PaginatedResult;

trait ApiResponses
{
    protected function success(mixed $data = null): JsonResponse
    {
        return JsonResponse::success($data, 200, $this->getResponseMeta());
    }

    protected function created(mixed $data = null): JsonResponse
    {
        return JsonResponse::success($data, 201, $this->getResponseMeta());
    }

    protected function accepted(mixed $data = null): JsonResponse
    {
        return JsonResponse::accepted($data);
    }

    protected function paginated(PaginatedResult $result): JsonResponse
    {
        return JsonResponse::paginated($result, $this->getResponseMeta());
    }

    protected function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return JsonResponse::validationError($errors, $message);
    }

    protected function badRequest(string $message, array $errors = []): JsonResponse
    {
        return JsonResponse::badRequest($message, $errors);
    }

    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return JsonResponse::unauthorized($message);
    }

    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return JsonResponse::forbidden($message);
    }

    protected function notFound(string $message = 'Not Found'): JsonResponse
    {
        return JsonResponse::notFound($message);
    }

    protected function unprocessable(string $message, array $details = []): JsonResponse
    {
        return JsonResponse::unprocessable($message, $details);
    }

    protected function error(string $message = 'Server Error', int $status = 500): JsonResponse
    {
        return JsonResponse::error($message, $status);
    }

    /**
     * Get response metadata. Can be overridden in controllers to add custom meta.
     */
    protected function getResponseMeta(): array
    {
        return [];
    }
}
