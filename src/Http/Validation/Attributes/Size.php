<?php

namespace BaseApi\Http\Validation\Attributes;

use Attribute;

#[Attribute]
class Size
{
    public function __construct(public float $maxSizeInMB)
    {
    }
}
