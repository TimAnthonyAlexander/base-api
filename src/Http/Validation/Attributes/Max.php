<?php

namespace BaseApi\Http\Validation\Attributes;

#[\Attribute]
class Max
{
    public function __construct(public int $value)
    {
    }
}
