<?php

namespace BaseApi\Http\Attributes;

use Attribute;

/**
 * Describes the successful response data shape for a handler method.
 * 
 * Usage:
 * #[ResponseType(UserDto::class)]                              // 200 { data: UserDto }
 * #[ResponseType('UserDto[]', status: 200, when: 'list')]     // 200 { data: UserDto[] }
 * #[ResponseType(['message' => 'string'], status: 201)]       // 201 { data: { message: string } }
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ResponseType
{
    public function __construct(
        public array|string|null $shape,
        public ?int $status = 200,
        public ?string $when = null
    ) {
    }

    /**
     * Parse shape into a normalized format for code generation
     */
    public function getShapeInfo(): array
    {
        if ($this->shape === null) {
            return ['type' => 'null'];
        }

        if (is_string($this->shape)) {
            // Handle array types like 'UserDto[]'
            if (str_ends_with($this->shape, '[]')) {
                return [
                    'type' => 'array',
                    'items' => substr($this->shape, 0, -2)
                ];
            }

            // Single class/type reference
            return [
                'type' => 'class',
                'class' => $this->shape
            ];
        }

        if (is_array($this->shape)) {
            // Inline object shape like ['message' => 'string', 'count' => 'int']
            return [
                'type' => 'object',
                'properties' => $this->shape
            ];
        }

        return ['type' => 'unknown'];
    }

    /**
     * Check if this shape represents a class/DTO reference
     */
    public function isClassReference(): bool
    {
        $info = $this->getShapeInfo();
        return $info['type'] === 'class' || ($info['type'] === 'array' && class_exists($info['items']));
    }

    /**
     * Get all class references in this shape
     */
    public function getClassReferences(): array
    {
        $info = $this->getShapeInfo();
        
        if ($info['type'] === 'class') {
            return [$info['class']];
        }
        
        if ($info['type'] === 'array' && class_exists($info['items'])) {
            return [$info['items']];
        }
        
        return [];
    }
}
