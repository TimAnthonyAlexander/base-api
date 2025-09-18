<?php

namespace BaseApi\Http\Validation\Attributes;

#[\Attribute]
class Mimes
{
    public function __construct(public array $types)
    {
    }
}
