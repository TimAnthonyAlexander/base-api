<?php

declare(strict_types=1);

namespace BaseApi\Http\Attributes;

use Attribute;

/**
 * Defines validation rules for a controller property
 * Used by IR generation to infer parameter requirements and types
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Rules
{
    public function __construct(
        public string $rules
    ) {}
    
    public function isRequired(): bool
    {
        return str_contains($this->rules, 'required');
    }
}

