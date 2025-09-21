<?php

namespace BaseApi\Support\Translation;

use Throwable;
use Exception;

class TranslationException extends Exception
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
