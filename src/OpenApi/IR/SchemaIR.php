<?php

declare(strict_types=1);

namespace BaseApi\OpenApi\IR;

/**
 * Intermediate representation of a schema
 * Algebraic form that can express primitives, arrays, objects, refs, and unions
 */
readonly class SchemaIR
{
    private function __construct(
        public string $kind,
        public mixed $data = null
    ) {}

    public static function ref(string $name): self
    {
        return new self('ref', ['name' => $name]);
    }

    public static function primitive(
        string $type,
        bool $nullable = false,
        ?array $enum = null,
        ?string $format = null
    ): self {
        return new self('primitive', [
            'type' => $type,
            'nullable' => $nullable,
            'enum' => $enum,
            'format' => $format,
        ]);
    }

    public static function array(self $items): self
    {
        return new self('array', ['items' => $items]);
    }

    public static function object(array $properties, bool $additional = false): self
    {
        return new self('object', [
            'properties' => $properties, // Record<string, { schema: SchemaIR, required: bool }>
            'additional' => $additional,
        ]);
    }

    public static function union(string $kind, array $members): self
    {
        return new self('union', [
            'kind' => $kind, // oneOf | anyOf
            'members' => $members,
        ]);
    }

    public static function unknown(): self
    {
        return new self('unknown');
    }

    public function isRef(): bool
    {
        return $this->kind === 'ref';
    }

    public function isPrimitive(): bool
    {
        return $this->kind === 'primitive';
    }

    public function isArray(): bool
    {
        return $this->kind === 'array';
    }

    public function isObject(): bool
    {
        return $this->kind === 'object';
    }

    public function isUnion(): bool
    {
        return $this->kind === 'union';
    }

    public function isUnknown(): bool
    {
        return $this->kind === 'unknown';
    }
}


