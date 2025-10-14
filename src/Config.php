<?php

namespace BaseApi;

class Config
{
    public function __construct(private readonly array $config, private array $env = [])
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        // First check environment variables for direct key match
        if (isset($this->env[$key])) {
            return $this->env[$key];
        }

        // Then check nested config using dot notation
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
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
        
        if (is_array($value)) {
            return $value;
        }
        
        return array_map('trim', explode(',', (string) $value));
    }

    /**
     * Get all configuration as array
     */
    public function all(): array
    {
        return $this->config;
    }
}