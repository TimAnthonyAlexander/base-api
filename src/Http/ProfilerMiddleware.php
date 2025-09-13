<?php

namespace BaseApi\Http;

use BaseApi\App;

class ProfilerMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        // Check if profiling should be enabled
        $shouldProfile = $this->shouldEnableProfiling($request);
        
        if ($shouldProfile) {
            App::profiler()->enable();
            
            // Start a span for the entire request
            $requestSpanId = App::profiler()->start('http_request', [
                'method' => $request->method,
                'path' => $request->path,
            ]);
        }

        $response = $next($request);

        if ($shouldProfile) {
            // Stop the request span
            App::profiler()->stop($requestSpanId);
            
            // Add profiling data to JSON responses
            if ($response instanceof JsonResponse) {
                $response = $this->addProfilingToJsonResponse($response);
            }
        }

        return $response;
    }

    private function shouldEnableProfiling(Request $request): bool
    {
        // Only enable in local/development environment
        if (App::config('app.env') !== 'local') {
            return false;
        }

        // Check for profiling query parameter
        return isset($request->query['profiling']) && 
               filter_var($request->query['profiling'], FILTER_VALIDATE_BOOLEAN);
    }

    private function addProfilingToJsonResponse(JsonResponse $response): JsonResponse
    {
        $data = json_decode($response->body, true);

        if (!is_array($data)) {
            return $response;
        }

        $data['profiling'] = App::profiler()->getSummary();

        return new JsonResponse($data, $response->status, $response->headers);
    }
}
