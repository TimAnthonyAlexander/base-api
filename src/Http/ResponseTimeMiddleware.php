<?php

namespace BaseApi\Http;

class ResponseTimeMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $start = hrtime(true); // nanoseconds

        $response = $next($request);

        $elapsedMs = (int) ((hrtime(true) - $start) / 1_000_000); // integer milliseconds

        // Always attach header
        $response = $response->withHeader('X-Response-Time-Ms', (string) $elapsedMs);

        // Add responseTimeMs only to JSON responses
        if ($response instanceof JsonResponse) {
            $response = $this->addResponseTimeToJsonResponse($response, $elapsedMs);
        }

        return $response;
    }

    private function addResponseTimeToJsonResponse(JsonResponse $response, int $elapsedMs): JsonResponse
    {
        $data = json_decode($response->body, true);

        if (!is_array($data)) {
            return $response;
        }

        $data['responseTimeMs'] = $elapsedMs;

        return new JsonResponse($data, $response->status, $response->headers);
    }
}
