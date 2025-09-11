<?php

namespace BaseApi\Database\Migrations;

class MigrationsFile
{
    public static function read(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }
        
        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }
        
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        return $data;
    }

    public static function write(string $path, array $data): void
    {
        // Ensure directory exists
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($path, $json);
    }

    public static function markApplied(string $path): void
    {
        $data = self::read($path);
        if ($data === null) {
            return;
        }
        
        $data['applied_at'] = date('c');
        self::write($path, $data);
    }

    public static function isApplied(string $path): bool
    {
        $data = self::read($path);
        return $data !== null && isset($data['applied_at']);
    }
}
