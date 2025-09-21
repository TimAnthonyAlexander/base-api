<?php

namespace BaseApi\Cache\Stores;

use Override;
use RuntimeException;
use BaseApi\Time\ClockInterface;
use BaseApi\Time\SystemClock;

/**
 * File-based cache store.
 * 
 * Stores cache data in files on the filesystem with atomic operations
 * to prevent corruption. Suitable for single-server deployments.
 */
class FileStore implements StoreInterface
{
    public function __construct(private readonly string $directory, private readonly string $prefix = '', private readonly int $permissions = 0755, private readonly ClockInterface $clock = new SystemClock())
    {
        $this->ensureDirectoryExists();
    }

    #[Override]
    public function get(string $key): mixed
    {
        $filePath = $this->getFilePath($key);
        
        if (!file_exists($filePath)) {
            return null;
        }

        $contents = file_get_contents($filePath);
        if ($contents === false) {
            return null;
        }

        $data = @unserialize($contents);
        if ($data === false) {
            // Corrupted file, remove it
            @unlink($filePath);
            return null;
        }

        // Check if expired
        if ($data['expires_at'] !== null && $data['expires_at'] < $this->clock->now()) {
            @unlink($filePath);
            return null;
        }

        return $data['value'];
    }

    #[Override]
    public function put(string $key, mixed $value, ?int $seconds): void
    {
        $filePath = $this->getFilePath($key);
        $now = $this->clock->now();
        $expiresAt = $seconds ? $now + $seconds : null;

        $data = [
            'value' => $value,
            'expires_at' => $expiresAt,
            'created_at' => $now,
        ];

        $serialized = serialize($data);
        
        // Use atomic write with temporary file
        $tempPath = $filePath . '.tmp.' . uniqid();
        
        if (file_put_contents($tempPath, $serialized, LOCK_EX) === false) {
            throw new RuntimeException('Failed to write cache file: ' . $tempPath);
        }

        if (!rename($tempPath, $filePath)) {
            @unlink($tempPath);
            throw new RuntimeException(sprintf('Failed to move cache file: %s to %s', $tempPath, $filePath));
        }

        chmod($filePath, $this->permissions);
    }

    #[Override]
    public function forget(string $key): bool
    {
        $filePath = $this->getFilePath($key);
        
        if (file_exists($filePath)) {
            return @unlink($filePath);
        }

        return false;
    }

    #[Override]
    public function flush(): bool
    {
        $pattern = $this->directory . '/' . $this->getCachePrefix() . '*';
        $files = glob($pattern);
        
        if ($files === false) {
            return false;
        }

        $success = true;
        foreach ($files as $file) {
            if (!@unlink($file)) {
                $success = false;
            }
        }

        return $success;
    }

    #[Override]
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    #[Override]
    public function increment(string $key, int $value): int
    {
        $current = $this->get($key);
        $new = is_numeric($current) ? (int)$current + $value : $value;
        
        // Preserve TTL if exists
        $filePath = $this->getFilePath($key);
        $ttl = null;
        
        if (file_exists($filePath)) {
            $contents = file_get_contents($filePath);
            if ($contents !== false) {
                $data = @unserialize($contents);
                if ($data !== false && $data['expires_at'] !== null) {
                    $ttl = max(0, $data['expires_at'] - $this->clock->now());
                }
            }
        }
        
        $this->put($key, $new, $ttl);
        return $new;
    }

    #[Override]
    public function decrement(string $key, int $value): int
    {
        return $this->increment($key, -$value);
    }

    #[Override]
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Clean up expired cache files.
     */
    public function cleanup(): int
    {
        $pattern = $this->directory . '/' . $this->getCachePrefix() . '*';
        $files = glob($pattern);
        
        if ($files === false) {
            return 0;
        }

        $removed = 0;
        $now = $this->clock->now();

        foreach ($files as $file) {
            $contents = @file_get_contents($file);
            if ($contents === false) {
                continue;
            }

            $data = @unserialize($contents);
            if ($data === false) {
                // Corrupted file, remove it
                @unlink($file);
                $removed++;
                continue;
            }

            // Check if expired
            if ($data['expires_at'] !== null && $data['expires_at'] < $now) {
                @unlink($file);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Get cache directory statistics.
     */
    public function getStats(): array
    {
        $pattern = $this->directory . '/' . $this->getCachePrefix() . '*';
        $files = glob($pattern);
        
        if ($files === false) {
            return [
                'total_files' => 0,
                'expired_files' => 0,
                'active_files' => 0,
                'total_size_bytes' => 0,
            ];
        }

        $totalFiles = count($files);
        $expiredFiles = 0;
        $totalSize = 0;
        $now = $this->clock->now();

        foreach ($files as $file) {
            $totalSize += filesize($file);
            
            $contents = @file_get_contents($file);
            if ($contents !== false) {
                $data = @unserialize($contents);
                if ($data !== false && $data['expires_at'] !== null && $data['expires_at'] < $now) {
                    $expiredFiles++;
                }
            }
        }

        return [
            'total_files' => $totalFiles,
            'expired_files' => $expiredFiles,
            'active_files' => $totalFiles - $expiredFiles,
            'total_size_bytes' => $totalSize,
        ];
    }

    private function getFilePath(string $key): string
    {
        $safeKey = $this->sanitizeKey($key);
        return $this->directory . '/' . $this->getCachePrefix() . $safeKey;
    }

    private function getCachePrefix(): string
    {
        return $this->prefix !== '' && $this->prefix !== '0' ? $this->prefix . '_' : 'cache_';
    }

    private function sanitizeKey(string $key): string
    {
        // Replace unsafe characters with safe ones
        return str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $key);
    }

    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, $this->permissions, true)) {
            throw new RuntimeException('Failed to create cache directory: ' . $this->directory);
        }

        if (!is_writable($this->directory)) {
            throw new RuntimeException('Cache directory is not writable: ' . $this->directory);
        }
    }
}
