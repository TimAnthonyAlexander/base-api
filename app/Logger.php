<?php

namespace BaseApi;

class Logger
{
    private static ?string $requestId = null;

    public static function setRequestId(string $requestId): void
    {
        self::$requestId = $requestId;
    }

    public function debug(string $msg, array $ctx = []): void
    {
        $this->log('DEBUG', $msg, $ctx);
    }

    public function info(string $msg, array $ctx = []): void
    {
        $this->log('INFO', $msg, $ctx);
    }

    public function warn(string $msg, array $ctx = []): void
    {
        $this->log('WARN', $msg, $ctx);
    }

    public function error(string $msg, array $ctx = []): void
    {
        $this->log('ERROR', $msg, $ctx);
    }

    private function log(string $level, string $msg, array $ctx): void
    {
        $requestIdPart = self::$requestId ? '[' . self::$requestId . '] ' : '';
        $contextPart = !empty($ctx) ? ' ' . json_encode($ctx) : '';
        
        $logMessage = "[{$level}] {$requestIdPart}{$msg}{$contextPart}";
        
        error_log($logMessage);
    }
}
