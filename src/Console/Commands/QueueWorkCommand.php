<?php

namespace BaseApi\Console\Commands;

use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\Queue\QueueWorker;
use BaseApi\App;

class QueueWorkCommand implements Command
{
    public function name(): string
    {
        return 'queue:work';
    }

    public function description(): string
    {
        return 'Process jobs in the queue';
    }

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
        
        echo "Starting queue worker for queue: {$queue}\n";
        echo "Options: sleep={$sleep}s, max-jobs=" . ($maxJobs ?: 'unlimited') . ", max-time=" . ($maxTime ?: 'unlimited') . "s, memory={$memoryLimit}MB\n\n";
        
        try {
            $worker = new QueueWorker(App::queue()->driver());
            $worker->work($queue, $sleep, $maxJobs, $maxTime, $memoryLimit);
            
            echo "Queue worker stopped gracefully.\n";
            return 0;
        } catch (\Exception $e) {
            echo "Queue worker error: " . $e->getMessage() . "\n";
            return 1;
        }
    }
    
    private function parseOptions(array $args): array
    {
        $options = [];
        
        for ($i = 0; $i < count($args); $i++) {
            $arg = $args[$i];
            
            if (strpos($arg, '--') === 0) {
                $option = substr($arg, 2);
                
                if (strpos($option, '=') !== false) {
                    [$key, $value] = explode('=', $option, 2);
                    $options[$key] = $value;
                } else {
                    // Check if next argument is the value
                    if (isset($args[$i + 1]) && strpos($args[$i + 1], '--') !== 0) {
                        $options[$option] = $args[$i + 1];
                        $i++; // Skip the next argument
                    } else {
                        $options[$option] = true;
                    }
                }
            }
        }
        
        return $options;
    }
}
