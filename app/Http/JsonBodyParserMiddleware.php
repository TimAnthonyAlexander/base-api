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
        } elseif (str_starts_with($contentType, 'application/x-www-form-urlencoded') || 
                  str_starts_with($contentType, 'multipart/form-data')) {
            $this->parseFormBody($req);
        }

        return $next($req);
    }

    private function parseJsonBody(Request $req): ?Response
    {
        $config = App::config();
        $maxMb = $config->int('REQUEST_MAX_JSON_MB', 2);
        $maxBytes = $maxMb * 1024 * 1024;

        if ($req->rawBody === null) {
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

    private function parseFormBody(Request $req): void
    {
        // Form data is already parsed by PHP into $_POST
        $req->body = $_POST;
        
        // Normalize file uploads
        $req->files = $this->normalizeFiles($_FILES);
    }

    private function normalizeFiles(array $files): array
    {
        $normalized = [];
        
        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                // Multiple files
                $normalized[$key] = [];
                for ($i = 0; $i < count($file['name']); $i++) {
                    $normalized[$key][] = [
                        'name' => $file['name'][$i],
                        'type' => $file['type'][$i],
                        'tmp_name' => $file['tmp_name'][$i],
                        'error' => $file['error'][$i],
                        'size' => $file['size'][$i]
                    ];
                }
            } else {
                // Single file
                $normalized[$key] = $file;
            }
        }
        
        return $normalized;
    }
}
