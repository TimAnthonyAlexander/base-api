<?php

namespace BaseApi\Storage;

use BaseApi\Storage\Exceptions\FileNotFoundException;
use BaseApi\Http\UploadedFile;

/**
 * Storage interface for file operations across different storage backends.
 * 
 * Simplified interface following KISS principles - covers 90% of use cases.
 */
interface StorageInterface
{
    /**
     * Store content at the given path.
     * 
     * @param string $path Destination path
     * @param mixed $contents Content to store (string or resource)
     * @param array $options Additional options (e.g., visibility, metadata)
     * @return string The path where the file was stored
     */
    public function put(string $path, mixed $contents, array $options = []): string;

    /**
     * Store an uploaded file.
     * 
     * @param string $directory Directory to store in
     * @param UploadedFile $file The uploaded file
     * @param array $options Additional options
     * @return string The path where the file was stored
     */
    public function putFile(string $directory, UploadedFile $file, array $options = []): string;

    /**
     * Store an uploaded file with a specific name.
     * 
     * @param string $directory Directory to store in
     * @param UploadedFile $file The uploaded file
     * @param string $name Desired filename
     * @param array $options Additional options
     * @return string The path where the file was stored
     */
    public function putFileAs(string $directory, UploadedFile $file, string $name, array $options = []): string;

    /**
     * Get the content of a file.
     *
     * @param string $path File path
     * @return string File content
     * @throws FileNotFoundException If file doesn't exist
     */
    public function get(string $path): string;

    /**
     * Check if a file exists.
     * 
     * @param string $path File path
     * @return bool True if file exists
     */
    public function exists(string $path): bool;

    /**
     * Delete a file.
     * 
     * @param string $path File path to delete
     * @return bool True if deleted successfully
     */
    public function delete(string $path): bool;

    /**
     * Get the URL for a file.
     * 
     * @param string $path File path
     * @return string Public URL to access the file
     */
    public function url(string $path): string;

    /**
     * Get the size of a file in bytes.
     * 
     * @param string $path File path
     * @return int File size in bytes
     */
    public function size(string $path): int;
}

