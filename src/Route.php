<?php

namespace BaseApi;

class Route
{
    private string $compiledRegex;

    private array $paramNames;

    public function __construct(private readonly string $method, private readonly string $path, private array $pipeline)
    {
        $this->compileRoute();
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function middlewares(): array
    {
        return array_slice($this->pipeline, 0, -1);
    }

    public function controllerClass(): string
    {
        return end($this->pipeline);
    }

    public function match(string $path): ?array
    {
        if (preg_match($this->compiledRegex, $path, $matches)) {
            $params = [];
            $counter = count($matches);
            for ($i = 1; $i < $counter; $i++) {
                $params[$this->paramNames[$i - 1]] = $matches[$i];
            }

            return $params;
        }

        return null;
    }

    private function compileRoute(): void
    {
        $this->paramNames = [];

        // Extract parameter names
        preg_match_all('/\{([^}]+)\}/', $this->path, $matches);
        $this->paramNames = $matches[1];

        // Convert route pattern to regex
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $this->path);
        $pattern = str_replace('/', '\/', $pattern);

        $this->compiledRegex = '/^' . $pattern . '$/';
    }
}
