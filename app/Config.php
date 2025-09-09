<?php

namespace BaseApi;

class Config
{
    private array $env;

    public function __construct(array $env)
    {
        $this->env = $env;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->env[$key] ?? $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);
        if ($value === null) {
            return $default;
        }
        
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->get($key);
        if ($value === null) {
            return $default;
        }
        
        return (int) $value;
    }

    public function list(string $key): array
    {
        $value = $this->get($key, '');
        if (empty($value)) {
            return [];
        }
        
        return array_map('trim', explode(',', $value));
    }
}
