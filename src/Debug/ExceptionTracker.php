<?php

namespace BaseApi\Debug;

use Throwable;

class ExceptionTracker
{
    private array $exceptions = [];
    private array $sensitiveFields = ['password', 'token', 'secret', 'key', 'auth'];

    public function __construct(private bool $enabled = false, array $sensitiveFields = [])
    {
        if ($sensitiveFields !== []) {
            $this->sensitiveFields = $sensitiveFields;
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Log an exception with context information
     */
    public function logException(Throwable $exception, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        // Filter sensitive data from context
        $filteredContext = $this->filterSensitiveData($context);
        
        $this->exceptions[] = [
            'message' => $exception->getMessage(),
            'class' => $exception::class,
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $this->formatStackTrace($exception->getTrace()),
            'context' => $filteredContext,
            'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Get all logged exceptions
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    /**
     * Get exception statistics
     */
    public function getStats(): array
    {
        $exceptionClasses = array_count_values(array_column($this->exceptions, 'class'));
        
        return [
            'total_exceptions' => count($this->exceptions),
            'unique_exception_types' => count($exceptionClasses),
            'exception_types' => $exceptionClasses,
            'most_common_exception' => $exceptionClasses === [] ? null : array_keys($exceptionClasses, max($exceptionClasses))[0],
        ];
    }

    /**
     * Filter sensitive data from context arrays
     */
    private function filterSensitiveData(array $data): array
    {
        $filtered = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $filtered[$key] = $this->filterSensitiveData($value);
            } elseif ($this->isSensitiveField($key)) {
                $filtered[$key] = '[FILTERED]';
            } else {
                $filtered[$key] = $value;
            }
        }
        
        return $filtered;
    }

    /**
     * Check if a field name is considered sensitive
     */
    private function isSensitiveField(string $fieldName): bool
    {
        $fieldLower = strtolower($fieldName);
        
        foreach ($this->sensitiveFields as $sensitive) {
            if (str_contains($fieldLower, strtolower((string) $sensitive))) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Format stack trace for better readability
     */
    private function formatStackTrace(array $trace): array
    {
        $formatted = [];
        
        foreach ($trace as $index => $frame) {
            $formatted[] = [
                'index' => $index,
                'file' => $frame['file'] ?? '[internal function]',
                'line' => $frame['line'] ?? null,
                'class' => $frame['class'] ?? null,
                'function' => $frame['function'] ?? null,
                'type' => $frame['type'] ?? null,
            ];
        }
        
        return $formatted;
    }

    /**
     * Clear all logged exceptions
     */
    public function clear(): void
    {
        $this->exceptions = [];
    }
}
