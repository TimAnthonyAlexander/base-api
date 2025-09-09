<?php

namespace BaseApi;

class Route
{
    private string $method;
    private string $path;
    private array $pipeline;
    private string $compiledRegex;
    private array $paramNames;

    public function __construct(string $method, string $path, array $pipeline)
    {
        $this->method = $method;
        $this->path = $path;
        $this->pipeline = $pipeline;
        
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
            for ($i = 1; $i < count($matches); $i++) {
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
