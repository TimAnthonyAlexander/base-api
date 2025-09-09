<?php

namespace BaseApi\Console\Commands;

use BaseApi\Console\Command;

class ServeCommand implements Command
{
    public function name(): string
    {
        return 'serve';
    }

    public function description(): string
    {
        return 'Start the development server';
    }

    public function execute(array $args): int
    {
        $host = $_ENV['APP_HOST'] ?? 'localhost';
        $port = $_ENV['APP_PORT'] ?? '8000';
        
        $address = "{$host}:{$port}";
        
        echo "Starting BaseApi development server on http://{$address}\n";
        echo "Press Ctrl+C to stop the server\n\n";
        
        $command = sprintf(
            'php -S %s -t public public/router.php',
            escapeshellarg($address)
        );
        
        passthru($command, $exitCode);
        
        return $exitCode;
    }
}
