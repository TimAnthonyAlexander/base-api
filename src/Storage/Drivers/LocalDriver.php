<?php

namespace BaseApi\Storage\Drivers;

use Override;
use RuntimeException;
use InvalidArgumentException;
use BaseApi\Http\UploadedFile;
use BaseApi\Storage\Exceptions\FileNotFoundException;
use BaseApi\Storage\Exceptions\UnableToWriteFileException;
use BaseApi\Storage\Exceptions\UnableToDeleteFileException;
use BaseApi\Storage\StorageInterface;

/**
 * Local filesystem storage driver.
 */
class LocalDriver implements StorageInterface
{
    private readonly string $root;

    private readonly ?string $url;

    private array $permissions;

    public function __construct(string $root, ?string $url = null, array $permissions = [])
    {
        $this->root = rtrim($root, '/\\');
        $this->url = $url ? rtrim($url, '/') : null;
        $this->permissions = array_merge([
            'file' => 0644,
            'dir' => 0755,
        ], $permissions);

        $this->ensureDirectoryExists($this->root);
    }

    #[Override]
    public function put(string $path, mixed $contents, array $options = []): string
    {
        $path = $this->normalizePath($path);
        $fullPath = $this->getFullPath($path);

        // Ensure directory exists
        $directory = dirname($fullPath);
        $this->ensureDirectoryExists($directory);

        // Write the file
        if (file_put_contents($fullPath, $contents) === false) {
            throw new UnableToWriteFileException('Unable to write file at path: ' . $path);
        }

        // Set file permissions
        chmod($fullPath, $this->permissions['file']);

        return $path;
    }

    #[Override]
    public function putFile(string $directory, UploadedFile $file, array $options = []): string
    {
        $filename = $this->generateUniqueFilename($file->name);
        return $this->putFileAs($directory, $file, $filename, $options);
    }

    #[Override]
    public function putFileAs(string $directory, UploadedFile $file, string $name, array $options = []): string
    {
        if (!$file->isValid()) {
            throw new UnableToWriteFileException("Invalid uploaded file");
        }

        $directory = trim($directory, '/');
        $path = $directory !== '' && $directory !== '0' ? $directory . '/' . $name : $name;
        $fullPath = $this->getFullPath($path);

        // Ensure directory exists
        $dirPath = dirname($fullPath);
        $this->ensureDirectoryExists($dirPath);

        // Move uploaded file
        if (!move_uploaded_file($file->tmpName, $fullPath)) {
            throw new UnableToWriteFileException('Unable to move uploaded file to: ' . $path);
        }

        // Set file permissions
        chmod($fullPath, $this->permissions['file']);

        return $path;
    }

    #[Override]
    public function get(string $path): string
    {
        $fullPath = $this->getFullPath($path);

        if (!$this->exists($path)) {
            throw new FileNotFoundException('File not found at path: ' . $path);
        }

        $contents = file_get_contents($fullPath);
        if ($contents === false) {
            throw new FileNotFoundException('Unable to read file at path: ' . $path);
        }

        return $contents;
    }

    #[Override]
    public function exists(string $path): bool
    {
        return file_exists($this->getFullPath($path));
    }

    #[Override]
    public function delete(string $path): bool
    {
        $fullPath = $this->getFullPath($path);

        if (!file_exists($fullPath)) {
            return true; // Already deleted
        }

        if (!unlink($fullPath)) {
            throw new UnableToDeleteFileException('Unable to delete file at path: ' . $path);
        }

        return true;
    }

    #[Override]
    public function url(string $path): string
    {
        if ($this->url === null) {
            throw new RuntimeException("No URL configured for local storage disk");
        }

        return $this->url . '/' . ltrim($path, '/');
    }

    #[Override]
    public function size(string $path): int
    {
        $fullPath = $this->getFullPath($path);

        if (!$this->exists($path)) {
            throw new FileNotFoundException('File not found at path: ' . $path);
        }

        $size = filesize($fullPath);
        if ($size === false) {
            throw new RuntimeException('Unable to get file size for path: ' . $path);
        }

        return $size;
    }

    /**
     * Get the full filesystem path.
     */
    private function getFullPath(string $path): string
    {
        $relative = $this->normalizePath($path);

        $realRoot = realpath($this->root);
        if ($realRoot === false) {
            throw new InvalidArgumentException('Invalid storage root');
        }

        // Join root and relative path, then canonicalize without requiring directories to exist
        $joined = $realRoot . DIRECTORY_SEPARATOR . $relative;
        $canonical = $this->canonicalizeAbsolutePath($joined);

        // Security check: ensure canonical path stays within root
        $prefix = rtrim($realRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($canonical !== $realRoot && !str_starts_with($canonical, $prefix)) {
            throw new InvalidArgumentException('Path traversal detected: ' . $relative);
        }

        return $canonical;
    }

    /**
     * Canonicalize an absolute path by resolving '.' and '..' segments
     * without requiring the path to exist on disk.
     */
    private function canonicalizeAbsolutePath(string $absolutePath): string
    {
        $ds = DIRECTORY_SEPARATOR;
        $path = str_replace(['/', '\\'], $ds, $absolutePath);

        // Preserve Windows drive letter if present (e.g., C:) or leading separator for Unix
        $prefix = '';
        if (preg_match('/^[A-Za-z]:\\\\?/', $path) === 1) {
            $prefix = substr($path, 0, 2); // e.g., C:
            $path = substr($path, 2);
        } elseif (str_starts_with($path, $ds)) {
            $prefix = $ds;
            $path = ltrim($path, $ds);
        }

        $parts = explode($ds, $path);
        $stack = [];
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if ($part === '.') {
                continue;
            }

            if ($part === '..') {
                if ($stack !== []) {
                    array_pop($stack);
                }

                // If stack empty, we are trying to escape root; keep stack empty
                continue;
            }

            $stack[] = $part;
        }

        $resolved = $prefix . implode($ds, $stack);
        // Ensure no trailing separator unless root itself
        return $resolved === '' ? $prefix : $resolved;
    }

    /**
     * Normalize a file path.
     */
    private function normalizePath(string $path): string
    {
        // Remove leading/trailing slashes and backslashes
        $path = trim($path, '/\\');

        // Convert backslashes to forward slashes
        $path = str_replace('\\', '/', $path);

        // Remove any double slashes
        $path = preg_replace('#/+#', '/', $path);

        return $path;
    }

    /**
     * Generate a unique filename for uploaded files.
     */
    private function generateUniqueFilename(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $basename = pathinfo($originalName, PATHINFO_FILENAME);

        // Sanitize the basename
        $basename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $basename);
        $basename = trim((string) $basename, '._-');

        if ($basename === '' || $basename === '0') {
            $basename = 'file';
        }

        // Add timestamp and random component for uniqueness
        $unique = date('YmdHis') . '_' . bin2hex(random_bytes(4));

        return $basename . '_' . $unique . ($extension !== '' && $extension !== '0' ? '.' . $extension : '');
    }

    /**
     * Ensure a directory exists.
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, $this->permissions['dir'], true)) {
            throw new RuntimeException('Unable to create directory: ' . $directory);
        }
    }
}
