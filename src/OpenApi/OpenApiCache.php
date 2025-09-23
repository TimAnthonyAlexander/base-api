<?php

declare(strict_types=1);

namespace BaseApi\OpenApi;

use BaseApi\App;

class OpenApiCache
{
    private const CACHE_KEY = 'openapi_spec';
    private const CACHE_FILE = 'storage/cache/openapi.json';

    public function get(): ?array
    {
        // First try to get from memory cache if available
        if (class_exists('BaseApi\Cache\Cache')) {
            $cached = \BaseApi\Cache\Cache::get(self::CACHE_KEY);
            if ($cached !== null) {
                return $cached;
            }
        }

        // Fallback to file-based cache
        $cacheFile = App::basePath(self::CACHE_FILE);
        
        if (!file_exists($cacheFile)) {
            return null;
        }

        // Check if cache is still valid (file modification time)
        if ($this->isCacheStale($cacheFile)) {
            return null;
        }

        $content = file_get_contents($cacheFile);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    public function put(array $spec): void
    {
        // Store in memory cache if available
        if (class_exists('BaseApi\Cache\Cache')) {
            \BaseApi\Cache\Cache::put(self::CACHE_KEY, $spec, 3600); // Cache for 1 hour
        }

        // Store in file cache
        $cacheFile = App::basePath(self::CACHE_FILE);
        $this->ensureDirectoryExists(dirname($cacheFile));

        $content = json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($cacheFile, $content);
    }

    public function clear(): void
    {
        // Clear memory cache if available
        if (class_exists('BaseApi\Cache\Cache')) {
            \BaseApi\Cache\Cache::delete(self::CACHE_KEY);
        }

        // Clear file cache
        $cacheFile = App::basePath(self::CACHE_FILE);
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    private function isCacheStale(string $cacheFile): bool
    {
        $cacheTime = filemtime($cacheFile);
        if ($cacheTime === false) {
            return true;
        }

        // Check if routes file is newer than cache
        $routesFile = App::basePath('routes/api.php');
        if (file_exists($routesFile)) {
            $routesTime = filemtime($routesFile);
            if ($routesTime !== false && $routesTime > $cacheTime) {
                return true;
            }
        }

        // Check if any controller files in app/Controllers are newer than cache
        $controllersDir = App::basePath('app/Controllers');
        if (is_dir($controllersDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($controllersDir)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $fileTime = $file->getMTime();
                    if ($fileTime > $cacheTime) {
                        return true;
                    }
                }
            }
        }

        // Cache is still fresh
        return false;
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new \Exception('Failed to create directory: ' . $directory);
        }
    }
}
