<?php

namespace BaseApi\Controllers;

use BaseApi\Http\Validation\Validator;

abstract class Controller
{
    protected function validate(array $rules): void
    {
        $validator = new Validator();
        $validator->validate($this, $rules);
    }
}
