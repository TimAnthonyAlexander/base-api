<?php

namespace BaseApi\Http;

interface Middleware
{
    public function handle(Request $req, callable $next): Response;
}
