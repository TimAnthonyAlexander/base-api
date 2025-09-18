<?php

namespace BaseApi\Http\Validation\Attributes;

#[\Attribute]
class Confirmed
{
    public function __construct(public ?string $field = null)
    {
    }
}
