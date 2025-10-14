<?php

namespace BaseApi\Http\Validation\Attributes;

use Attribute;

#[Attribute]
class Mimes
{
    public function __construct(public array $types)
    {
    }
}
