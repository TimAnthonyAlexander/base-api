<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\Queue\QueueWorker;
use BaseApi\App;

class QueueWorkCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'queue:work';
    }

    #[Override]
    public function description(): string
    {
        return 'Process jobs in the queue';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        // Boot the app to load configuration
        App::boot($app?->basePath() ?? getcwd());
        
        // Parse command line options
        $options = $this->parseOptions($args);
        
        $queue = $options['queue'] ?? 'default';
        $sleep = (int) ($options['sleep'] ?? 3);
        $maxJobs = (int) ($options['max-jobs'] ?? 0);
        $maxTime = (int) ($options['max-time'] ?? 0);
        $memoryLimit = (int) ($options['memory'] ?? 128);
        
        echo sprintf('Starting queue worker for queue: %s%s', $queue, PHP_EOL);
        echo sprintf('Options: sleep=%ds, max-jobs=', $sleep) . ($maxJobs ?: 'unlimited') . ", max-time=" . ($maxTime ?: 'unlimited') . "s, memory={$memoryLimit}MB\n\n";
        
        try {
            $worker = new QueueWorker(App::queue()->driver());
            $worker->work($queue, $sleep, $maxJobs, $maxTime, $memoryLimit);
            
            echo "Queue worker stopped gracefully.\n";
            return 0;
        } catch (Exception $exception) {
            echo "Queue worker error: " . $exception->getMessage() . "\n";
            return 1;
        }
    }
    
    private function parseOptions(array $args): array
    {
        $options = [];
        $counter = count($args);
        
        for ($i = 0; $i < $counter; $i++) {
            $arg = $args[$i];
            
            if (str_starts_with((string) $arg, '--')) {
                $option = substr((string) $arg, 2);
                
                if (str_contains($option, '=')) {
                    [$key, $value] = explode('=', $option, 2);
                    $options[$key] = $value;
                } elseif (isset($args[$i + 1]) && !str_starts_with((string) $args[$i + 1], '--')) {
                    // Check if next argument is the value
                    $options[$option] = $args[$i + 1];
                    $i++;
                    // Skip the next argument
                } else {
                    $options[$option] = true;
                }
            }
        }
        
        return $options;
    }
}
