<?php

namespace BaseApi\Http\Validation;

use Exception;

class ValidationException extends Exception
{
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Validation failed.');
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
