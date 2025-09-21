<?php

namespace BaseApi\Http;

use Override;
use JsonSerializable;

abstract class ApiResource implements JsonSerializable
{
    public function __construct(protected mixed $resource)
    {
    }

    /**
     * Transform the resource into an array.
     */
    abstract public function toArray(): array;

    /**
     * Create a new resource instance.
     */
    public static function make(mixed $resource): static
    {
        /** @phpstan-ignore-next-line */
        return new static($resource);
    }

    /**
     * Create a collection of resources.
     */
    public static function collection(array $resources): array
    {
        return array_map(fn($r): array => static::make($r)->toArray(), $resources);
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
