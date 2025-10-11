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
        return 'Start the development server (use --screen to run in detached screen session)';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        $basePath = $app?->basePath() ?? getcwd();
        
        // Boot the app to load configuration
        App::boot($basePath);
        
        // Check for --screen flag
        $useScreen = in_array('--screen', $args);
        
        $host = App::config('app.host') ?? $_ENV['APP_HOST'] ?? 'localhost';
        $port = App::config('app.port') ?? $_ENV['APP_PORT'] ?? '7879';
        
        $address = sprintf('%s:%s', $host, $port);
        
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
        
        if ($useScreen) {
            // Run in detached screen session
            $appName = App::config('app.name') ?? $_ENV['APP_NAME'] ?? 'baseapi';
            $screenName = strtolower((string) preg_replace('/[^a-zA-Z0-9-_]/', '-', (string) $appName)) . '-api';
            
            // Check if screen is available
            exec('which screen', $output, $returnVar);
            if ($returnVar !== 0) {
                echo ColorHelper::error("❌ Error: 'screen' command not found. Please install screen first.") . "\n";
                return 1;
            }
            
            // Check if screen session already exists
            exec(sprintf('screen -list | grep %s', escapeshellarg($screenName)), $existingOutput);
            if ($existingOutput !== []) {
                echo ColorHelper::warning(sprintf('⚠️  Screen session "%s" already exists.', $screenName)) . "\n";
                echo ColorHelper::info(sprintf('   Attach with: screen -r %s', $screenName)) . "\n";
                echo ColorHelper::info(sprintf('   Kill with: screen -S %s -X quit', $screenName)) . "\n";
                return 1;
            }
            
            // Build the serve command for screen
            $phpCommand = sprintf(
                'php -S %s -t %s %s',
                escapeshellarg($address),
                escapeshellarg($publicDir),
                escapeshellarg($routerPath)
            );
            
            // Change to the application directory for screen
            $originalDir = getcwd();
            chdir($basePath);
            
            // Run in detached screen
            $screenCommand = sprintf(
                'screen -dmS %s bash -c %s',
                escapeshellarg($screenName),
                escapeshellarg(sprintf('cd %s && %s', escapeshellarg($basePath), $phpCommand))
            );
            
            exec($screenCommand, $output, $exitCode);
            
            // Restore original directory
            chdir($originalDir);
            
            if ($exitCode === 0) {
                echo ColorHelper::success(sprintf('✓ Server started in screen session "%s"', $screenName)) . "\n";
                echo ColorHelper::info(sprintf('   URL: http://%s', $address)) . "\n";
                echo ColorHelper::info(sprintf('   Attach: screen -r %s', $screenName)) . "\n";
                echo ColorHelper::info(sprintf('   Stop: screen -S %s -X quit', $screenName)) . "\n";
            } else {
                echo ColorHelper::error("❌ Failed to start screen session.") . "\n";
            }
            
            return $exitCode;
        }
        
        // Normal serve mode (no screen)
        echo ColorHelper::success(sprintf('Starting BaseApi development server on http://%s', $address)) . "\n";
        echo ColorHelper::comment("⌨️  Press Ctrl+C to stop the server") . "\n\n";
        
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
