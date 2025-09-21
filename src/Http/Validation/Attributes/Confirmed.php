<?php

namespace BaseApi\Http\Validation\Attributes;

use Attribute;

#[Attribute]
class Confirmed
{
    public function __construct(public ?string $field = null)
    {
    }
}
