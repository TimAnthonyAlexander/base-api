<?php

namespace BaseApi\Http\Validation\Attributes;

use Attribute;

#[Attribute]
class Min
{
    public function __construct(public int $value)
    {
    }
}
