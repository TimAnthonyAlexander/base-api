<?php

namespace BaseApi\Console\Commands;

use Override;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\App;

class ServeCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'serve';
    }

    #[Override]
    public function description(): string
    {
        return 'Start the development server';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        $basePath = $app?->basePath() ?? getcwd();
        
        // Boot the app to load configuration
        App::boot($basePath);
        
        $host = App::config('app.host') ?? $_ENV['APP_HOST'] ?? 'localhost';
        $port = App::config('app.port') ?? $_ENV['APP_PORT'] ?? '7879';
        
        $address = sprintf('%s:%s', $host, $port);
        
        echo ColorHelper::success(sprintf('Starting BaseApi development server on http://%s', $address)) . "\n";
        echo ColorHelper::comment("⌨️  Press Ctrl+C to stop the server") . "\n\n";
        
        // Check if public directory exists
        $publicDir = $basePath . '/public';
        if (!is_dir($publicDir)) {
            echo ColorHelper::error(sprintf('❌ Error: public directory not found at %s', $publicDir)) . "\n";
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
