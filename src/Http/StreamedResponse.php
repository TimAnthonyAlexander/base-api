<?php

namespace BaseApi\Http;

use InvalidArgumentException;

/**
 * StreamedResponse - HTTP response with streaming body
 * 
 * Allows sending response data in chunks using a callback function.
 * Perfect for Server-Sent Events (SSE), large file downloads, or AI streaming.
 * 
 * Usage:
 * ```php
 * return new StreamedResponse(function() {
 *     foreach ($data as $chunk) {
 *         echo "data: " . json_encode($chunk) . "\n\n";
 *         flush();
 *     }
 * }, 200, ['Content-Type' => 'text/event-stream']);
 * ```
 */
class StreamedResponse extends Response
{
    /**
     * @var callable(): void
     */
    private $callback;

    /**
     * @param callable(): void $callback Function that outputs the streamed content
     * @param int $status HTTP status code
     * @param array<string, string> $headers Response headers
     */
    public function __construct(callable $callback, int $status = 200, array $headers = [])
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('StreamedResponse requires a callable as the first argument');
        }

        $this->callback = $callback;
        
        parent::__construct($status, $headers, '');
    }

    /**
     * Get the streaming callback
     * 
     * @return callable(): void
     */
    public function getCallback(): callable
    {
        return $this->callback;
    }

    /**
     * Execute the streaming callback
     */
    public function sendContent(): void
    {
        // Only disable output buffering if headers have been sent (i.e., not in testing)
        // This allows tests to capture output with ob_start() while ensuring real
        // streaming responses work correctly in production
        if (headers_sent()) {
            // Disable all output buffering for streaming
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            
            ini_set('output_buffering', '0');
            ini_set('implicit_flush', '1');
        }
        
        // Disable gzip compression for streaming
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', '1');
        }
        
        ($this->callback)();
    }

    /**
     * Check if this is a streamed response
     */
    public function isStreamed(): bool
    {
        return true;
    }

    /**
     * Create a Server-Sent Events (SSE) streaming response
     * 
     * @param callable(): void $callback
     */
    public static function sse(callable $callback, int $status = 200, array $additionalHeaders = []): self
    {
        $headers = array_merge([
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
        ], $additionalHeaders);

        return new self($callback, $status, $headers);
    }

    /**
     * Create a chunked transfer encoding response
     * 
     * @param callable(): void $callback
     */
    public static function chunked(callable $callback, int $status = 200, array $additionalHeaders = []): self
    {
        $headers = array_merge([
            'Transfer-Encoding' => 'chunked',
        ], $additionalHeaders);

        return new self($callback, $status, $headers);
    }
}

