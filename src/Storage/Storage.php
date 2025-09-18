<?php

namespace BaseApi\Storage;

use BaseApi\App;
use BaseApi\Http\UploadedFile;

/**
 * Storage facade providing static access to file storage operations.
 */
class Storage
{
    private static ?StorageManager $manager = null;

    /**
     * Get the storage manager instance.
     */
    public static function manager(): StorageManager
    {
        if (self::$manager === null) {
            self::$manager = App::container()->make(StorageManager::class);
        }

        return self::$manager;
    }

    /**
     * Get a storage driver instance.
     * 
     * @param string|null $name Driver name (null for default)
     * @return StorageInterface
     */
    public static function disk(?string $name = null): StorageInterface
    {
        return self::manager()->disk($name);
    }

    /**
     * Store content at the given path using the default disk.
     * 
     * @param string $path Destination path
     * @param mixed $contents Content to store
     * @param array $options Additional options
     * @return string The stored file path
     */
    public static function put(string $path, mixed $contents, array $options = []): string
    {
        return self::disk()->put($path, $contents, $options);
    }

    /**
     * Store an uploaded file using the default disk.
     * 
     * @param string $directory Directory to store in
     * @param UploadedFile $file The uploaded file
     * @param array $options Additional options
     * @return string The stored file path
     */
    public static function putFile(string $directory, UploadedFile $file, array $options = []): string
    {
        return self::disk()->putFile($directory, $file, $options);
    }

    /**
     * Store an uploaded file with a specific name using the default disk.
     * 
     * @param string $directory Directory to store in
     * @param UploadedFile $file The uploaded file
     * @param string $name Desired filename
     * @param array $options Additional options
     * @return string The stored file path
     */
    public static function putFileAs(string $directory, UploadedFile $file, string $name, array $options = []): string
    {
        return self::disk()->putFileAs($directory, $file, $name, $options);
    }

    /**
     * Get file content using the default disk.
     * 
     * @param string $path File path
     * @return string File content
     */
    public static function get(string $path): string
    {
        return self::disk()->get($path);
    }

    /**
     * Check if file exists using the default disk.
     * 
     * @param string $path File path
     * @return bool True if file exists
     */
    public static function exists(string $path): bool
    {
        return self::disk()->exists($path);
    }

    /**
     * Delete a file using the default disk.
     * 
     * @param string $path File path
     * @return bool True if deleted successfully
     */
    public static function delete(string $path): bool
    {
        return self::disk()->delete($path);
    }

    /**
     * Get the URL for a file using the default disk.
     * 
     * @param string $path File path
     * @return string Public URL
     */
    public static function url(string $path): string
    {
        return self::disk()->url($path);
    }

    /**
     * Get file size using the default disk.
     * 
     * @param string $path File path
     * @return int File size in bytes
     */
    public static function size(string $path): int
    {
        return self::disk()->size($path);
    }

    /**
     * Reset the manager instance (useful for testing).
     */
    public static function reset(): void
    {
        self::$manager = null;
    }
}
