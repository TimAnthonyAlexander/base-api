<?php

namespace BaseApi\Http\Validation\Attributes;

use Attribute;

#[Attribute]
class In
{
    public function __construct(public array $values)
    {
    }
}
