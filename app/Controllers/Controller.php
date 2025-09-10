<?php

namespace BaseApi\Controllers;

use BaseApi\Http\Validation\Validator;
use BaseApi\Http\Request;

abstract class Controller
{
    public ?Request $request = null;

    protected function validate(array $rules): void
    {
        $validator = new Validator();
        $validator->validate($this, $rules);
    }
}
