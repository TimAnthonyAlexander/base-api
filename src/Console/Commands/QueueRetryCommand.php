<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Queue\Drivers\DatabaseQueueDriver;
use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\Console\ColorHelper;
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
            echo ColorHelper::error("❌ Error retrying jobs: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }
    
    private function retrySpecificJob(string $jobId): int
    {
        $driver = App::queue()->driver();

        if ($driver->retry($jobId)) {
            echo ColorHelper::success(sprintf('♾️ Job %s queued for retry.', $jobId)) . "\n";
            return 0;
        }

        echo ColorHelper::error(sprintf('❌ Failed to retry job %s. Job may not exist or is not in failed state.', $jobId)) . "\n";
        return 1;
    }
    
    private function retryAllFailedJobs(): int
    {
        // This only works with database driver
        $driver = App::queue()->driver();
        
        if ($driver::class !== DatabaseQueueDriver::class) {
            echo ColorHelper::error("❌ Error: Bulk retry is only supported with database queue driver.") . "\n";
            return 1;
        }
        
        $db = App::db();
        
        // Get all failed jobs
        $failedJobsResult = $db->raw("
            SELECT id FROM jobs 
            WHERE status = 'failed'
            ORDER BY failed_at DESC
        ");
        
        // Extract the id values from the result
        $failedJobs = array_column($failedJobsResult, 'id');
        
        if ($failedJobs === []) {
            echo ColorHelper::info("ℹ️  No failed jobs found.") . "\n";
            return 0;
        }
        
        echo ColorHelper::info("🔍 Found " . count($failedJobs) . " failed jobs.") . "\n";
        echo ColorHelper::warning("⚠️  Retry all failed jobs? [y/N]: ");
        
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (strtolower(trim($line)) !== 'y') {
            echo ColorHelper::comment("❌ Retry cancelled.") . "\n";
            return 0;
        }
        
        $retried = 0;
        foreach ($failedJobs as $jobId) {
            if ($driver->retry($jobId)) {
                $retried++;
            }
        }
        
        echo ColorHelper::success(sprintf('✅ Successfully queued %d jobs for retry.', $retried)) . "\n";
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
