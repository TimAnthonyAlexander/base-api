<?php

namespace BaseApi\Http;

class UploadedFile
{
    public string $name;
    public string $type;
    public string $tmpName;
    public int $error;
    public int $size;

    public function __construct(array $fileData)
    {
        $this->name = $fileData['name'] ?? '';
        $this->type = $fileData['type'] ?? '';
        $this->tmpName = $fileData['tmp_name'] ?? '';
        $this->error = $fileData['error'] ?? UPLOAD_ERR_NO_FILE;
        $this->size = $fileData['size'] ?? 0;
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
        return $this->type;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getSizeInMB(): float
    {
        return $this->size / (1024 * 1024);
    }
}
