<?php

namespace BaseApi\Controllers;

use BaseApi\App;
use BaseApi\Http\Validation\Validator;
use BaseApi\Http\Request;
use BaseApi\Container\ContainerInterface;

abstract class Controller
{
    public ?Request $request = null;

    protected function validate(array $rules): void
    {
        $validator = new Validator();
        $validator->validate($this, $rules);
    }

    /**
     * Get the application container instance.
     */
    protected function container(): ContainerInterface
    {
        return App::container();
    }

    /**
     * Resolve a service from the container.
     * 
     * @param string $abstract The service identifier
     * @param array $parameters Additional parameters
     * @return mixed The resolved service
     */
    protected function make(string $abstract, array $parameters = []): mixed
    {
        return $this->container()->make($abstract, $parameters);
    }
}
