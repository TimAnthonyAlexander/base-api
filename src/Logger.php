<?php

namespace BaseApi;

class Logger
{
    private static ?string $requestId = null;
    private ?string $logPath = null;
    private string $channel = 'file';
    private string $minLevel = 'debug';

    private const LEVELS = [
        'debug' => 0,
        'info' => 1,
        'warn' => 2,
        'error' => 3,
    ];

    public function __construct()
    {
        // Get logging configuration - check env first, then config
        $this->channel = $_ENV['LOG_CHANNEL'] ?? App::config('logging.default', 'file');
        $this->minLevel = strtolower($_ENV['LOG_LEVEL'] ?? App::config('logging.level', 'debug'));

        // For file driver, set up the log path in application's storage directory
        if ($this->channel === 'file') {
            $relativePath = $_ENV['LOG_FILE'] ?? App::config('logging.path', 'storage/logs/baseapi.log');
            
            // If path starts with 'storage/', use storagePath helper
            if (str_starts_with($relativePath, 'storage/')) {
                $pathWithoutStorage = substr($relativePath, strlen('storage/'));
                $this->logPath = App::storagePath($pathWithoutStorage);
            } else {
                $this->logPath = App::basePath($relativePath);
            }

            // Ensure log directory exists
            $logDir = dirname($this->logPath);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
        }
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
