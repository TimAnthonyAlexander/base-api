<?php

namespace BaseApi\Http;

class Request
{
    public string $method;
    public string $path;
    public array $headers;
    public array $query;
    public array $body;
    public ?string $rawBody;
    public array $files;
    public array $cookies;
    public array $session;
    public ?array $user = null;
    public string $requestId;
    public array $pathParams = [];
    public array $allowedMethods = [];
    public ?string $routePattern = null;
    public ?string $routeMethod = null;
    public ?float $startTime = null;

    public function __construct(
        string $method,
        string $path,
        array $headers,
        array $query,
        array $body,
        ?string $rawBody,
        array $files,
        array $cookies,
        array $session,
        string $requestId
    ) {
        $this->method = $method;
        $this->path = $path;
        $this->headers = $headers;
        $this->query = $query;
        $this->body = $body;
        $this->rawBody = $rawBody;
        $this->files = $files;
        $this->cookies = $cookies;
        $this->session = $session;
        $this->requestId = $requestId;
    }
}
