<?php

namespace BaseApi\Console\Commands;

use BaseApi\Console\Command;
use BaseApi\App;

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

    public function execute(array $args, ?\BaseApi\Console\Application $app = null): int
    {
        $basePath = $app?->basePath() ?? getcwd();
        
        // Boot the app to load configuration
        App::boot($basePath);
        
        $host = App::config('app.host') ?? $_ENV['APP_HOST'] ?? 'localhost';
        $port = App::config('app.port') ?? $_ENV['APP_PORT'] ?? '7879';
        
        $address = "{$host}:{$port}";
        
        echo "Starting BaseApi development server on http://{$address}\n";
        echo "Press Ctrl+C to stop the server\n\n";
        
        // Check if public directory exists
        $publicDir = $basePath . '/public';
        if (!is_dir($publicDir)) {
            echo "Error: public directory not found at {$publicDir}\n";
            return 1;
        }
        
        // Create router.php path relative to public directory
        $routerPath = $basePath . '/public/router.php';
        if (!file_exists($routerPath)) {
            // Use index.php if router.php doesn't exist
            $routerPath = $basePath . '/public/index.php';
        }
        
        $command = sprintf(
            'php -S %s -t %s %s',
            escapeshellarg($address),
            escapeshellarg($publicDir),
            escapeshellarg($routerPath)
        );
        
        // Change to the application directory
        $originalDir = getcwd();
        chdir($basePath);
        
        passthru($command, $exitCode);
        
        // Restore original directory
        chdir($originalDir);
        
        return $exitCode;
    }
}
