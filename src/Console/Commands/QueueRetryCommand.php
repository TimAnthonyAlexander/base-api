<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Queue\Drivers\DatabaseQueueDriver;
use PDO;
use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\App;

class QueueRetryCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'queue:retry';
    }

    #[Override]
    public function description(): string
    {
        return 'Retry failed jobs';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        // Boot the app to load configuration
        App::boot($app?->basePath() ?? getcwd());
        
        try {
            $options = $this->parseOptions($args);
            $jobId = $options['id'] ?? null;

            if ($jobId) {
                return $this->retrySpecificJob($jobId);
            }

            return $this->retryAllFailedJobs();
            
        } catch (Exception $exception) {
            echo "Error retrying jobs: " . $exception->getMessage() . "\n";
            return 1;
        }
    }
    
    private function retrySpecificJob(string $jobId): int
    {
        $driver = App::queue()->driver();

        if ($driver->retry($jobId)) {
            echo "Job {$jobId} queued for retry.\n";
            return 0;
        }

        echo "Failed to retry job {$jobId}. Job may not exist or is not in failed state.\n";
        return 1;
    }
    
    private function retryAllFailedJobs(): int
    {
        // This only works with database driver
        $driver = App::queue()->driver();
        
        if ($driver::class !== DatabaseQueueDriver::class) {
            echo "Error: Bulk retry is only supported with database queue driver.\n";
            return 1;
        }
        
        $db = App::db();
        
        // Get all failed jobs
        $failedJobs = $db->query("
            SELECT id FROM jobs 
            WHERE status = 'failed'
            ORDER BY failed_at DESC
        ")->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($failedJobs)) {
            echo "No failed jobs found.\n";
            return 0;
        }
        
        echo "Found " . count($failedJobs) . " failed jobs.\n";
        echo "Retry all failed jobs? [y/N]: ";
        
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (strtolower(trim($line)) !== 'y') {
            echo "Retry cancelled.\n";
            return 0;
        }
        
        $retried = 0;
        foreach ($failedJobs as $jobId) {
            if ($driver->retry($jobId)) {
                $retried++;
            }
        }
        
        echo "Successfully queued {$retried} jobs for retry.\n";
        return 0;
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
