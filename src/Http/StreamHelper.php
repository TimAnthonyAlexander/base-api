<?php

declare(strict_types=1);

namespace BaseApi\Http;

use Exception;
use Generator;

/**
 * StreamHelper - Utilities for handling streaming responses
 * 
 * Simplifies common streaming patterns like SSE and provides
 * automatic buffering control and connection management.
 */
class StreamHelper
{
    /**
     * Stream Server-Sent Events from a generator
     * 
     * Automatically handles:
     * - Output buffering control
     * - Connection abort detection
     * - Error handling
     * - Completion signals
     * 
     * @param callable(): Generator $generator Function that yields data chunks
     * @param callable(mixed): array|null $transformer Optional transformer for each chunk
     */
    public static function sse(callable $generator, ?callable $transformer = null): StreamedResponse
    {
        return StreamedResponse::sse(function () use ($generator, $transformer): void {
            // Ignore user abort to complete stream properly
            ignore_user_abort(true);
            
            try {
                foreach ($generator() as $chunk) {
                    // Check if connection is still alive
                    if (connection_aborted() !== 0) {
                        break;
                    }
                    
                    // Transform chunk if transformer provided
                    $data = $transformer !== null ? $transformer($chunk) : $chunk;
                    // Skip empty chunks
                    if ($data === null) {
                        continue;
                    }
                    if ($data === []) {
                        continue;
                    }
                    
                    // Send SSE data
                    echo "data: " . json_encode($data) . "\n\n";
                    self::flush();
                }
                
                // Send completion signal
                if (connection_aborted() === 0) {
                    echo "data: [DONE]\n\n";
                    self::flush();
                }
            } catch (Exception $exception) {
                // Send error to client
                if (connection_aborted() === 0) {
                    echo "data: " . json_encode(['error' => $exception->getMessage()]) . "\n\n";
                    self::flush();
                }
            }
        });
    }
    
    /**
     * Force flush output buffers
     */
    private static function flush(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
    
    /**
     * Extract text content from OpenAI streaming chunk
     */
    public static function openAITextTransformer(array $chunk): ?array
    {
        if (isset($chunk['delta']) && is_string($chunk['delta'])) {
            return ['content' => $chunk['delta']];
        }
        
        return null;
    }
    
    /**
     * Extract full OpenAI chunk data
     */
    public static function openAIFullTransformer(array $chunk): ?array
    {
        return $chunk;
    }
}

