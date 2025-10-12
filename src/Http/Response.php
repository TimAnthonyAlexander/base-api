<?php

namespace BaseApi\Http;

class Response
{
    public int $status;

    public array $headers;

    public mixed $body;

    public function __construct(int $status = 200, array $headers = [], mixed $body = '')
    {
        $this->status = $status;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function withHeader(string $name, string $value): self
    {
        $new = clone $this;
        
        // Remove any existing header with the same name (case-insensitive)
        foreach (array_keys($new->headers) as $existingName) {
            if (strcasecmp($existingName, $name) === 0) {
                unset($new->headers[$existingName]);
            }
        }
        
        $new->headers[$name] = $value;
        return $new;
    }

    public function withStatus(int $status): self
    {
        $new = clone $this;
        $new->status = $status;
        return $new;
    }

    public function withHeaders(array $headers): self
    {
        $new = clone $this;
        foreach ($headers as $name => $value) {
            $new->headers[$name] = $value;
        }

        return $new;
    }
}
