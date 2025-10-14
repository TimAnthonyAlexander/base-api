<?php

namespace BaseApi\Http;

use InvalidArgumentException;
use RuntimeException;

class BinaryResponse
{
    public static function fromFile(string $path, string $mime, ?string $downloadName = null): Response
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException('File not found: ' . $path);
        }

        $headers = [
            'Content-Type' => $mime,
            'Content-Length' => (string) filesize($path)
        ];

        if ($downloadName !== null) {
            $headers['Content-Disposition'] = 'attachment; filename="' . addslashes($downloadName) . '"';
        }

        $stream = fopen($path, 'r');
        if ($stream === false) {
            throw new RuntimeException('Unable to open file: ' . $path);
        }

        return new Response(200, $headers, $stream);
    }
}
