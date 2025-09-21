<?php

namespace BaseApi\Console\Commands;

use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\App;

class QueueStatusCommand implements Command
{
    public function name(): string
    {
        return 'queue:status';
    }

    public function description(): string
    {
        return 'Display queue status and statistics';
    }

    public function execute(array $args, ?Application $app = null): int
    {
        // Boot the app to load configuration
        App::boot($app?->basePath() ?? getcwd());
        
        try {
            $queueManager = App::queue();
            $driver = $queueManager->driver();
            
            // Get stats for common queues
            $queues = ['default', 'high', 'low', 'emails', 'processing'];
            
            echo "Queue Status\n";
            echo "============\n\n";
            
            $totalJobs = 0;
            foreach ($queues as $queue) {
                $size = $driver->size($queue);
                $totalJobs += $size;
                
                if ($size > 0) {
                    echo sprintf("%-12s: %d jobs\n", $queue, $size);
                }
            }
            
            if ($totalJobs === 0) {
                echo "No pending jobs in any queue.\n";
            } else {
                echo "\nTotal pending jobs: {$totalJobs}\n";
            }
            
            // If using database driver, show additional stats
            if (get_class($driver) === 'BaseApi\Queue\Drivers\DatabaseQueueDriver') {
                $this->showDatabaseStats();
            }
            
            return 0;
        } catch (\Exception $e) {
            echo "Error getting queue status: " . $e->getMessage() . "\n";
            return 1;
        }
    }
    
    private function showDatabaseStats(): void
    {
        try {
            $db = App::db();
            
            // Get job counts by status
            $stats = $db->query("
                SELECT status, COUNT(*) as count 
                FROM jobs 
                GROUP BY status
            ")->fetchAll(\PDO::FETCH_ASSOC);
            
            if (!empty($stats)) {
                echo "\nJob Status Statistics:\n";
                echo "---------------------\n";
                
                foreach ($stats as $stat) {
                    echo sprintf("%-12s: %d\n", ucfirst($stat['status']), $stat['count']);
                }
            }
            
            // Get failed jobs count from last 24 hours
            $failedRecent = $db->query("
                SELECT COUNT(*) as count 
                FROM jobs 
                WHERE status = 'failed' 
                AND failed_at > datetime('now', '-1 day')
            ")->fetchColumn();
            
            if ($failedRecent > 0) {
                echo "\nRecent failures (24h): {$failedRecent}\n";
            }
            
        } catch (\Exception $e) {
            // Silently ignore database stats errors
        }
    }
}
