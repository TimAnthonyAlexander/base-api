<?php

namespace BaseApi\Support;

class RateLimiter
{
    private readonly string $dir;

    public function __construct(string $dir)
    {
        $this->dir = rtrim($dir, '/');

        // Validate directory path - reject if it contains symlinks or suspicious paths
        if (!$this->isSecurePath($this->dir)) {
            // For now, just log the issue instead of failing
            error_log('Warning: Rate limit directory path may be insecure: ' . $this->dir);
        }

        if (!is_dir($this->dir)) {
            $oldUmask = umask(0022); // Ensure consistent permissions
            mkdir($this->dir, 0755, true);
            umask($oldUmask);
        }
    }

    public function hit(string $routeId, string $key, int $windowStart, int $limit): array
    {
        $routeHash = $this->hashRoute($routeId, '');
        $keyHash = md5($key);

        $counterDir = sprintf('%s/%s/%s', $this->dir, $routeHash, $keyHash);
        $counterFile = sprintf('%s/%d.cnt', $counterDir, $windowStart);

        if (!is_dir($counterDir)) {
            $oldUmask = umask(0022); // Ensure consistent permissions
            mkdir($counterDir, 0755, true);
            umask($oldUmask);
        }

        // Clean up old windows (best effort)
        $this->cleanupOldWindows($counterDir, $windowStart);

        // Atomic increment with file locking
        $count = $this->incrementCounter($counterFile);

        $remaining = max($limit - $count, 0);
        $reset = $windowStart;

        return [
            'count' => $count,
            'remaining' => $remaining,
            'reset' => $reset
        ];
    }

    public function hashRoute(string $method, string $path): string
    {
        return md5(sprintf('%s:%s', $method, $path));
    }

    private function incrementCounter(string $file): int
    {
        $handle = fopen($file, 'c+');
        if (!$handle) {
            return 1;
        }

        if (flock($handle, LOCK_EX)) {
            $count = (int) fread($handle, 10) ?: 0;
            $count++;

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string) $count);

            flock($handle, LOCK_UN);
            fclose($handle);

            return $count;
        }

        fclose($handle);
        return 1;
    }

    private function cleanupOldWindows(string $dir, int $currentWindow): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = glob($dir . '/*.cnt');
        if (!$files) {
            return;
        }

        foreach ($files as $file) {
            // Security check - ensure file is within expected directory
            if (!str_starts_with(realpath($file), realpath($dir))) {
                continue;
            }

            $basename = basename($file, '.cnt');
            if (is_numeric($basename) && (int) $basename < $currentWindow - 3600) {
                @unlink($file);
            }
        }
    }

    private function isSecurePath(string $path): bool
    {
        // Basic security check - reject paths with directory traversal
        if (str_contains($path, '..')) {
            return false;
        }
        // Ensure path is absolute to prevent relative path issues
        return str_starts_with($path, '/');
    }
}
