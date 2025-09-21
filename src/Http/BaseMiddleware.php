<?php

namespace BaseApi\Http;

use Override;
use BaseApi\App;
use BaseApi\Container\ContainerInterface;

/**
 * Base middleware class providing container access.
 * 
 * Middleware classes can extend this to get easy access to the DI container.
 */
abstract class BaseMiddleware implements Middleware
{
    /**
     * Handle the request.
     * 
     * @param Request $req The request
     * @param callable(Request): Response $next The next middleware
     * @return Response The response
     */
    #[Override]
    abstract public function handle(Request $req, callable $next): Response;

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

