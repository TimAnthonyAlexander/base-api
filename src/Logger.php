<?php

namespace BaseApi;

class Logger
{
    private static ?string $requestId = null;

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
        $contextPart = $ctx === [] ? '' : ' ' . json_encode($ctx);
        
        $logMessage = sprintf('[%s] %s%s%s', $level, $requestIdPart, $msg, $contextPart);
        
        error_log($logMessage);
    }
}
