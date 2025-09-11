<?php

namespace BaseApi\Http;

use BaseApi\Logger;

class RequestIdMiddleware implements Middleware
{
    public function handle(Request $req, callable $next): Response
    {
        // Generate UUIDv7-like request ID (simplified version)
        $req->requestId = $this->generateUuidV7();
        
        // Set it for the logger
        Logger::setRequestId($req->requestId);
        
        // Process request
        $response = $next($req);
        
        // Add request ID to response headers
        return $response->withHeader('X-Request-Id', $req->requestId);
    }

    private function generateUuidV7(): string
    {
        // Simplified UUIDv7 generation
        $timestamp = (int)(microtime(true) * 1000); // milliseconds since epoch
        $timestampHex = str_pad(dechex($timestamp), 12, '0', STR_PAD_LEFT);
        
        $randomBytes = random_bytes(10);
        $randomHex = bin2hex($randomBytes);
        
        // Format as UUID
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($timestampHex, 0, 8),
            substr($timestampHex, 8, 4),
            '7' . substr($randomHex, 1, 3), // Version 7
            sprintf('%x', (hexdec(substr($randomHex, 4, 1)) & 0x3) | 0x8) . substr($randomHex, 5, 3), // Variant bits
            substr($randomHex, 8, 12)
        );
    }
}
