<?php

namespace BaseApi\Http;

use BaseApi\App;

class JsonBodyParserMiddleware implements Middleware
{
    public function handle(Request $req, callable $next): Response
    {
        $contentType = $req->headers['Content-Type'] ?? '';

        if (str_starts_with($contentType, 'application/json')) {
            $result = $this->parseJsonBody($req);
            if ($result instanceof Response) {
                return $result; // Size limit exceeded
            }
        }

        return $next($req);
    }

    private function parseJsonBody(Request $req): ?Response
    {
        $config = App::config();
        $maxMb = $config->int('REQUEST_MAX_JSON_MB', 2);
        $maxBytes = $maxMb * 1024 * 1024;

        if ($req->rawBody === null || !json_validate($req->rawBody)) {
            return null;
        }

        if (strlen($req->rawBody) > $maxBytes) {
            // Return 413 Payload Too Large with proper error format
            return new JsonResponse([
                'error' => "Request body too large. Maximum {$maxMb}MB allowed.",
                'requestId' => \BaseApi\Logger::getRequestId()
            ], 413);
        }

        $decoded = json_decode($req->rawBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        $req->body = $decoded ?? [];
        return null;
    }
}
