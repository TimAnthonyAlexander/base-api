<?php

namespace BaseApi\Http\Validation\Attributes;

use Attribute;

#[Attribute]
class Max
{
    public function __construct(public int $value)
    {
    }
}
