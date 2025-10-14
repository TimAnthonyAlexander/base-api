<?php

namespace BaseApi\Database;

use Exception;
use Throwable;

class DbException extends Exception
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
