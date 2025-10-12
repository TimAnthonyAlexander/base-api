<?php

namespace BaseApi\Console\Commands;

use Override;
use BaseApi\Queue\Drivers\DatabaseQueueDriver;
use Exception;
use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\Console\ColorHelper;
use BaseApi\App;

class QueueStatusCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'queue:status';
    }

    #[Override]
    public function description(): string
    {
        return 'Display queue status and statistics';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        // Boot the app to load configuration
        App::boot($app?->basePath() ?? getcwd());

        try {
            $queueManager = App::queue();
            $driver = $queueManager->driver();

            // Get stats for common queues
            $queues = ['default', 'high', 'low', 'emails', 'processing'];

            echo ColorHelper::header("📊 Queue Status") . "\n";
            echo str_repeat('─', 80) . "\n\n";

            $totalJobs = 0;
            foreach ($queues as $queue) {
                $size = $driver->size($queue);
                $totalJobs += $size;

                if ($size > 0) {
                    echo ColorHelper::info(sprintf("  %-12s: ", $queue)) . ColorHelper::colorize($size . " jobs", ColorHelper::YELLOW) . "\n";
                }
            }

            if ($totalJobs === 0) {
                echo ColorHelper::comment(" No pending jobs in any queue.") . "\n";
            } else {
                echo "\n" . ColorHelper::info("Total pending jobs: ") . ColorHelper::colorize((string)$totalJobs, ColorHelper::BRIGHT_YELLOW) . "\n";
            }

            // If using database driver, show additional stats
            if ($driver::class === DatabaseQueueDriver::class) {
                $this->showDatabaseStats();
            }

            return 0;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error getting queue status: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }

    private function showDatabaseStats(): void
    {
        try {
            $db = App::db();

            // Get job counts by status
            $stats = $db->raw("
                SELECT status, COUNT(*) as count 
                FROM jobs 
                GROUP BY status
            ");

            if ($stats !== []) {
                echo "\n" . ColorHelper::header("📊 Job Status Statistics") . "\n";
                echo str_repeat('─', 80) . "\n";

                foreach ($stats as $stat) {
                    $status = ucfirst((string) $stat['status']);
                    $color = match ($stat['status']) {
                        'completed' => ColorHelper::GREEN,
                        'failed' => ColorHelper::RED,
                        'processing' => ColorHelper::YELLOW,
                        default => ColorHelper::CYAN
                    };
                    echo ColorHelper::info(sprintf("  %-12s: ", $status)) . ColorHelper::colorize((string)$stat['count'], $color) . "\n";
                }
            }

            // Get failed jobs count from last 24 hours
            $failedRecent = $db->scalar("
                SELECT COUNT(*) as count 
                FROM jobs 
                WHERE status = 'failed' 
                AND failed_at > datetime('now', '-1 day')
            ");

            if ($failedRecent > 0) {
                echo "\n" . ColorHelper::warning("⚠️  Recent failures (24h): ") . ColorHelper::colorize((string)$failedRecent, ColorHelper::RED) . "\n";
            }
        } catch (Exception) {
            // Silently ignore database stats errors
        }
    }
}
