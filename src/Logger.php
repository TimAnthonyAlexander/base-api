<?php

namespace BaseApi;

class Logger
{
    private static ?string $requestId = null;

    private ?string $logPath = null;

    private ?string $channel = null;

    private ?string $minLevel = null;

    private bool $initialized = false;

    private const array LEVELS = [
        'debug' => 0,
        'info' => 1,
        'warn' => 2,
        'error' => 3,
    ];

    /**
     * Initialize logger configuration lazily to avoid circular dependencies
     * during container bootstrapping
     */
    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        // Get logging configuration from config
        // Config::get() safely handles env variables internally
        $this->channel = App::config('logging.default', 'file');
        $this->minLevel = strtolower((string) App::config('logging.level', 'debug'));

        // For file driver, set up the log path in application's storage directory
        if ($this->channel === 'file') {
            $relativePath = App::config('logging.path', 'storage/logs/baseapi.log');

            // Resolve the absolute path
            if (str_starts_with((string) $relativePath, 'storage/')) {
                $pathWithoutStorage = substr((string) $relativePath, strlen('storage/'));
                $this->logPath = App::storagePath($pathWithoutStorage);
            } elseif (str_starts_with((string) $relativePath, '/')) {
                // Absolute path
                $this->logPath = $relativePath;
            } else {
                $this->logPath = App::basePath($relativePath);
            }

            // Ensure log directory exists
            $logDir = dirname((string) $this->logPath);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
        }

        $this->initialized = true;
    }

    public static function setRequestId(string $requestId): void
    {
        self::$requestId = $requestId;
    }

    public static function getRequestId(): ?string
    {
        return self::$requestId;
    }

    public function debug(string $msg, array $ctx = []): void
    {
        $this->log('debug', $msg, $ctx);
    }

    public function info(string $msg, array $ctx = []): void
    {
        $this->log('info', $msg, $ctx);
    }

    public function warn(string $msg, array $ctx = []): void
    {
        $this->log('warn', $msg, $ctx);
    }

    public function error(string $msg, array $ctx = []): void
    {
        $this->log('error', $msg, $ctx);
    }

    private function log(string $level, string $msg, array $ctx): void
    {
        // Lazy initialization on first log
        if (!$this->initialized) {
            $this->initialize();
        }

        // Check if this level should be logged
        if (self::LEVELS[$level] < self::LEVELS[$this->minLevel]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $requestIdPart = self::$requestId ? '[' . self::$requestId . '] ' : '';
        $contextPart = $ctx === [] ? '' : ' ' . json_encode($ctx, JSON_UNESCAPED_SLASHES);

        $logMessage = sprintf('[%s] [%s] %s%s%s', $timestamp, strtoupper($level), $requestIdPart, $msg, $contextPart);

        // Write based on channel
        if ($this->channel === 'file' && $this->logPath) {
            // Write to application's log file
            file_put_contents($this->logPath, $logMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
        } else {
            // Fallback to stderr
            error_log($logMessage);
        }
    }
}
