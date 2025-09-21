<?php

namespace BaseApi\Console\Commands;

use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\App;

class QueueRetryCommand implements Command
{
    public function name(): string
    {
        return 'queue:retry';
    }

    public function description(): string
    {
        return 'Retry failed jobs';
    }

    public function execute(array $args, ?Application $app = null): int
    {
        // Boot the app to load configuration
        App::boot($app?->basePath() ?? getcwd());
        
        try {
            $options = $this->parseOptions($args);
            $jobId = $options['id'] ?? null;
            
            if ($jobId) {
                return $this->retrySpecificJob($jobId);
            } else {
                return $this->retryAllFailedJobs();
            }
            
        } catch (\Exception $e) {
            echo "Error retrying jobs: " . $e->getMessage() . "\n";
            return 1;
        }
    }
    
    private function retrySpecificJob(string $jobId): int
    {
        $driver = App::queue()->driver();
        
        if ($driver->retry($jobId)) {
            echo "Job {$jobId} queued for retry.\n";
            return 0;
        } else {
            echo "Failed to retry job {$jobId}. Job may not exist or is not in failed state.\n";
            return 1;
        }
    }
    
    private function retryAllFailedJobs(): int
    {
        // This only works with database driver
        $driver = App::queue()->driver();
        
        if (get_class($driver) !== 'BaseApi\Queue\Drivers\DatabaseQueueDriver') {
            echo "Error: Bulk retry is only supported with database queue driver.\n";
            return 1;
        }
        
        $db = App::db();
        
        // Get all failed jobs
        $failedJobs = $db->query("
            SELECT id FROM jobs 
            WHERE status = 'failed'
            ORDER BY failed_at DESC
        ")->fetchAll(\PDO::FETCH_COLUMN);
        
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
