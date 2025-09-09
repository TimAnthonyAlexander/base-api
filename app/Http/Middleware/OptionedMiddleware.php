<?php

namespace BaseApi\Http\Middleware;

interface OptionedMiddleware
{
    public function setOptions(array $options): void;
}
