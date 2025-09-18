<?php

namespace BaseApi\Http\Validation\Attributes;

#[\Attribute]
class In
{
    public function __construct(public array $values)
    {
    }
}
