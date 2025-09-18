<?php

namespace BaseApi\Http\Validation\Attributes;

#[\Attribute]
class Size
{
    public function __construct(public float $maxSizeInMB)
    {
    }
}
