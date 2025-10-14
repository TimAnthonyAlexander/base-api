<?php

namespace BaseApi\Http;

interface Middleware
{
    /**
     * @param callable(Request): Response $next
     */
    public function handle(Request $req, callable $next): Response;
}
