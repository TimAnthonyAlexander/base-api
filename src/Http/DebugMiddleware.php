<?php

namespace BaseApi\Http;

use BaseApi\App;
use BaseApi\Debug\DebugPanel;

class DebugMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        // Only enable debugging in development environments
        if (!$this->shouldEnableDebugging($request)) {
            return $next($request);
        }

        // Enable profiler and start request tracking
        $profiler = App::profiler();
        $profiler->enable();

        // Log initial memory snapshot
        $profiler->trackMemory('request_start');

        // Log request details
        $profiler->logRequest($request);

        // Start profiling the entire request
        $requestSpanId = $profiler->start('http_request', [
            'method' => $request->method,
            'path' => $request->path,
            'user_agent' => $request->headers['User-Agent'] ?? null,
        ]);

        try {
            $response = $next($request);

            // Log response details
            $profiler->logResponse($response);

            // Take final memory snapshot
            $profiler->trackMemory('request_end');

            // Inject debug information if appropriate
            $response = $this->injectDebugInfo($request, $response);

            return $response;
        } catch (\Throwable $exception) {
            // Log any exceptions that occur during request processing
            $profiler->logException($exception, [
                'request_method' => $request->method,
                'request_path' => $request->path,
            ]);

            throw $exception;
        } finally {
            // Always stop the request span
            $profiler->stop($requestSpanId);
        }
    }

    /**
     * Determine if debugging should be enabled for this request
     */
    private function shouldEnableDebugging(Request $request): bool
    {
        // Only enable in development environments
        $env = App::config('app.env');
        if ($env !== 'local' && $env !== 'development') {
            return false;
        }

        // Check if debugging is explicitly enabled in config
        $debugConfig = App::config('debug.enabled', false);
        if ($debugConfig) {
            return true;
        }

        // Allow enabling via query parameter (for API testing)
        return isset($request->query['debug']) &&
            filter_var($request->query['debug'], FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Inject debug information into the response
     */
    private function injectDebugInfo(Request $request, Response $response): Response
    {
        $debugPanel = new DebugPanel(true);

        // For JSON responses, add debug data to the response body
        if ($response instanceof JsonResponse) {
            return $this->addDebugToJsonResponse($response, $debugPanel);
        }

        // For HTML responses, inject debug panel
        if ($this->isHtmlResponse($response)) {
            return $this->addDebugPanelToHtmlResponse($response, $debugPanel);
        }

        return $response;
    }

    /**
     * Add debug information to JSON responses
     */
    private function addDebugToJsonResponse(JsonResponse $response, DebugPanel $debugPanel): JsonResponse
    {
        $data = json_decode($response->body, true);

        if (!is_array($data)) {
            return $response;
        }

        // Add debug information under a 'debug' key
        $data['debug'] = $debugPanel->getMetrics();

        return new JsonResponse($data, $response->status, $response->headers);
    }

    /**
     * Add debug panel to HTML responses
     */
    private function addDebugPanelToHtmlResponse(Response $response, DebugPanel $debugPanel): Response
    {
        $debugHtml = $debugPanel->renderPanel();

        if (empty($debugHtml)) {
            return $response;
        }

        // Try to inject before closing body tag
        $body = $response->body;
        $debugHtml = "\n" . $debugHtml . "\n";

        if (stripos($body, '</body>') !== false) {
            $body = str_ireplace('</body>', $debugHtml . '</body>', $body);
        } else {
            // If no body tag, just append
            $body .= $debugHtml;
        }

        return new Response($response->status, $response->headers, $body);
    }

    /**
     * Check if response contains HTML content
     */
    private function isHtmlResponse(Response $response): bool
    {
        $contentType = '';

        // Check Content-Type header
        foreach ($response->headers as $name => $value) {
            if (strtolower($name) === 'content-type') {
                $contentType = strtolower($value);
                break;
            }
        }

        return str_contains($contentType, 'text/html') ||
            (empty($contentType) && str_contains($response->body, '<html'));
    }
}
