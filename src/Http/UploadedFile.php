<?php

namespace BaseApi\Http;

use finfo;
use BaseApi\Storage\Storage;

class UploadedFile
{
    public string $name;

    public string $clientType;

    public string $type;

    public string $tmpName;

    public int $error;

    public int $size;

    public function __construct(array $fileData)
    {
        $this->name = $fileData['name'] ?? '';
        $this->tmpName = $fileData['tmp_name'] ?? '';
        $this->error = $fileData['error'] ?? UPLOAD_ERR_NO_FILE;
        $this->size = $fileData['size'] ?? 0;
        $this->clientType = $fileData['type'] ?? '';
        $this->type = $this->getMimeType();
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    public function getExtension(): string
    {
        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }

    public function getMimeType(): string
    {
        // Check if temp file exists and is readable before trying to detect MIME type
        if ($this->tmpName !== '' && $this->tmpName !== '0' && file_exists($this->tmpName) && is_readable($this->tmpName)) {
            $f = new finfo(FILEINFO_MIME_TYPE);
            $detectedType = $f->file($this->tmpName);
            if ($detectedType !== false) {
                return $detectedType;
            }
        }

        // Fallback to client type or default
        return $this->clientType ?: 'application/octet-stream';
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getSizeInMB(): float
    {
        return $this->size / (1024 * 1024);
    }

    /**
     * Store the uploaded file using the default disk.
     * 
     * @param string|null $directory Directory to store in (null for root)
     * @param string|null $disk Storage disk name (null for default)
     * @return string The stored file path
     */
    public function store(?string $directory = null, ?string $disk = null): string
    {
        $directory ??= '';
        return Storage::disk($disk)->putFile($directory, $this);
    }

    /**
     * Store the uploaded file with a specific name using the default disk.
     * 
     * @param string $directory Directory to store in
     * @param string $name Desired filename
     * @param string|null $disk Storage disk name (null for default)
     * @return string The stored file path
     */
    public function storeAs(string $directory, string $name, ?string $disk = null): string
    {
        return Storage::disk($disk)->putFileAs($directory, $this, $name);
    }

    /**
     * Store the uploaded file publicly (on the public disk).
     * 
     * @param string|null $directory Directory to store in (null for root)
     * @return string The stored file path
     */
    public function storePublicly(?string $directory = null): string
    {
        return $this->store($directory, 'public');
    }

    /**
     * Store the uploaded file with a specific name publicly (on the public disk).
     * 
     * @param string $directory Directory to store in
     * @param string $name Desired filename
     * @return string The stored file path
     */
    public function storePubliclyAs(string $directory, string $name): string
    {
        return $this->storeAs($directory, $name, 'public');
    }
}
