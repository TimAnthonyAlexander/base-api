<?php

namespace BaseApi\Http;

class ResponseTimeMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        // Capture start time
        $request->startTime = microtime(true);

        // Call next middleware/controller
        $response = $next($request);

        $responseTime = round((microtime(true) - $request->startTime), 2);

        // Add response time to JSON responses only
        if ($response instanceof JsonResponse) {
            $response = $this->addResponseTimeToJsonResponse($response, $responseTime);
        }

        return $response;
    }

    private function addResponseTimeToJsonResponse(JsonResponse $response, float $responseTime): JsonResponse
    {
        // Decode the existing JSON body
        $data = json_decode($response->body, true);

        if ($data === null) {
            // If JSON decode fails, return original response
            return $response;
        }

        // Add responseTime to the root level
        $data['responseTime'] = $responseTime;

        // Create new JsonResponse with updated data
        return new JsonResponse($data, $response->status, $response->headers);
    }
}
