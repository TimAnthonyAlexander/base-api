<?php

namespace BaseApi\Http;

use Override;

class ResponseTimeMiddleware implements Middleware
{
    #[Override]
    public function handle(Request $request, callable $next): Response
    {
        $start = hrtime(true); // nanoseconds

        $response = $next($request);

        $elapsedMs = (int) ((hrtime(true) - $start) / 1_000_000); // integer milliseconds

        // Always attach header
        $response = $response->withHeader('X-Response-Time-Ms', (string) $elapsedMs);

        // Add responseTimeMs only to JSON responses
        if ($response instanceof JsonResponse) {
            return $this->addResponseTimeToJsonResponse($response, $elapsedMs);
        }

        return $response;
    }

    private function addResponseTimeToJsonResponse(JsonResponse $response, int $elapsedMs): JsonResponse
    {
        $data = json_decode((string) $response->body, true);

        if (!is_array($data)) {
            return $response;
        }

        $data['responseTimeMs'] = $elapsedMs;

        return new JsonResponse($data, $response->status, $response->headers);
    }
}
