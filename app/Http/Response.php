<?php

namespace BaseApi\Http;

class Response
{
    public int $status;
    public array $headers;
    public string|resource $body;

    public function __construct(int $status = 200, array $headers = [], string|resource $body = '')
    {
        $this->status = $status;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function withHeader(string $name, string $value): self
    {
        $new = clone $this;
        $new->headers[$name] = $value;
        return $new;
    }

    public function withStatus(int $status): self
    {
        $new = clone $this;
        $new->status = $status;
        return $new;
    }
}
