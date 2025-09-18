<?php

namespace BaseApi\Http\Validation\Attributes;

#[\Attribute]
class Min
{
    public function __construct(public int $value)
    {
    }
}
