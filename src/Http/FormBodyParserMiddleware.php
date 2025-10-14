<?php

namespace BaseApi\Http;

use Override;

class FormBodyParserMiddleware implements Middleware
{
    #[Override]
    public function handle(Request $req, callable $next): Response
    {
        $contentType = $req->headers['Content-Type'] ?? '';
        
        if (str_starts_with((string) $contentType, 'application/x-www-form-urlencoded')) {
            $this->parseFormBody($req);
        } elseif (str_starts_with((string) $contentType, 'multipart/form-data')) {
            $this->parseMultipartBody($req);
        }

        return $next($req);
    }

    private function parseFormBody(Request $req): void
    {
        if ($req->rawBody !== null && $req->rawBody !== '') {
            // Parse URL-encoded data manually for better control
            $parsed = [];
            parse_str($req->rawBody, $parsed);
            $req->body = $parsed;
        } else {
            // Fallback to PHP's $_POST if raw body is empty
            $req->body = $_POST;
        }
    }

    private function parseMultipartBody(Request $req): void
    {
        // For multipart/form-data, PHP automatically parses into $_POST and $_FILES
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
                $counter = count($file['name']);
                for ($i = 0; $i < $counter; $i++) {
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
