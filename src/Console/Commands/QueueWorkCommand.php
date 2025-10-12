<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\Console\ColorHelper;
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
        return 'Process jobs in the queue (use --screen to run in detached screen session)';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        $basePath = $app?->basePath() ?? getcwd();
        
        // Boot the app to load configuration
        App::boot($basePath);

        // Parse command line options
        $options = $this->parseOptions($args);
        
        // Check for --screen flag
        $useScreen = in_array('--screen', $args) || isset($options['screen']);

        $queue = $options['queue'] ?? 'default';
        $sleep = (int) ($options['sleep'] ?? 3);
        $maxJobs = (int) ($options['max-jobs'] ?? 0);
        $maxTime = (int) ($options['max-time'] ?? 0);
        $memoryLimit = (int) ($options['memory'] ?? 128);

        if ($useScreen) {
            return $this->runInScreen($basePath, $queue, $sleep, $maxJobs, $maxTime, $memoryLimit);
        }

        echo ColorHelper::header(sprintf('🔄 Starting queue worker for queue: %s', $queue)) . "\n";
        echo ColorHelper::info('Options: ') . ColorHelper::comment(sprintf('sleep=%ds, max-jobs=%s, max-time=%ss, memory=%dMB', $sleep, $maxJobs ?: 'unlimited', $maxTime ?: 'unlimited', $memoryLimit)) . "\n\n";

        try {
            $worker = new QueueWorker(App::queue()->driver());
            $worker->work($queue, $sleep, $maxJobs, $maxTime, $memoryLimit);

            echo ColorHelper::success("Queue worker stopped gracefully.") . "\n";
            return 0;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Queue worker error: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }

    private function runInScreen(
        string $basePath,
        string $queue,
        int $sleep,
        int $maxJobs,
        int $maxTime,
        int $memoryLimit
    ): int {
        // Generate screen session name
        $appName = App::config('app.name') ?? $_ENV['APP_NAME'] ?? 'baseapi';
        $screenName = strtolower((string) preg_replace('/[^a-zA-Z0-9-_]/', '-', (string) $appName)) . '-queue';
        
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
        
        // Build the queue:work command for screen
        $masonPath = $basePath . '/mason';
        if (!file_exists($masonPath)) {
            echo ColorHelper::error(sprintf('❌ Error: mason command not found at %s', $masonPath)) . "\n";
            return 1;
        }
        
        $queueCommand = sprintf(
            'php %s queue:work --queue=%s --sleep=%d --max-jobs=%d --max-time=%d --memory=%d',
            escapeshellarg($masonPath),
            escapeshellarg($queue),
            $sleep,
            $maxJobs,
            $maxTime,
            $memoryLimit
        );
        
        // Run in detached screen
        $screenCommand = sprintf(
            'screen -dmS %s bash -c %s',
            escapeshellarg($screenName),
            escapeshellarg(sprintf('cd %s && %s', escapeshellarg($basePath), $queueCommand))
        );
        
        exec($screenCommand, $output, $exitCode);
        
        if ($exitCode === 0) {
            echo ColorHelper::success(sprintf('✓ Queue worker started in screen session "%s"', $screenName)) . "\n";
            echo ColorHelper::info(sprintf('   Queue: %s', $queue)) . "\n";
            echo ColorHelper::info(sprintf('   Options: sleep=%ds, max-jobs=%s, max-time=%ss, memory=%dMB', $sleep, $maxJobs ?: 'unlimited', $maxTime ?: 'unlimited', $memoryLimit)) . "\n";
            echo ColorHelper::info(sprintf('   Attach: screen -r %s', $screenName)) . "\n";
            echo ColorHelper::info(sprintf('   Stop: screen -S %s -X quit', $screenName)) . "\n";
        } else {
            echo ColorHelper::error("❌ Failed to start screen session.") . "\n";
        }
        
        return $exitCode;
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
