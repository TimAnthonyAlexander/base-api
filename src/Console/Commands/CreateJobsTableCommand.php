<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\Console\ColorHelper;
use BaseApi\Database\Migrations\MigrationsFile;
use BaseApi\App;

class CreateJobsTableCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'queue:install';
    }

    #[Override]
    public function description(): string
    {
        return 'Create the jobs table migration for queue system';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        $basePath = $app?->basePath() ?? getcwd();
        App::boot($basePath);

        try {
            $migrationsFile = App::config()->get('MIGRATIONS_FILE', 'storage/migrations.json');
            $migrationsPath = App::basePath($migrationsFile);

            // Create jobs table migration
            $migration = $this->createJobsTableMigration();

            // Add to migrations file
            MigrationsFile::appendMigrations($migrationsPath, [$migration]);

            echo ColorHelper::success("Jobs table migration created successfully!") . "\n";
            echo ColorHelper::info("📊 Run 'console migrate:apply' to create the jobs table and performance indexes.") . "\n";

            return 0;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error creating jobs table migration: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }

    private function createJobsTableMigration(): array
    {
        $driver = App::db()->getConnection()->getDriver();
        $driverName = $driver->getName();
        
        // Generate database-specific SQL
        $sql = $this->generateJobsTableSql($driverName);

        // Return the table creation migration
        $migration = [
            'id' => MigrationsFile::generateMigrationId($sql, 'jobs', 'create_table'),
            'sql' => $sql,
            'destructive' => false,
            'generated_at' => date('c'),
            'table' => 'jobs',
            'operation' => 'create_table',
            'description' => 'Create jobs table and indexes for queue system'
        ];

        return $migration;
    }
    
    private function generateJobsTableSql(string $driverName): string
    {
        return match ($driverName) {
            'mysql' => $this->generateMySqlJobsTable(),
            'postgresql' => $this->generatePostgreSqlJobsTable(),
            'sqlite' => $this->generateSqliteJobsTable(),
            default => $this->generateMySqlJobsTable(), // Default to MySQL
        };
    }
    
    private function generateMySqlJobsTable(): string
    {
        return <<<'SQL'
CREATE TABLE `jobs` (
    `id` VARCHAR(36) NOT NULL PRIMARY KEY,
    `queue` VARCHAR(255) NOT NULL DEFAULT 'default',
    `payload` TEXT NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `attempts` INT NOT NULL DEFAULT 0,
    `error` TEXT,
    `run_at` DATETIME NOT NULL,
    `started_at` DATETIME,
    `completed_at` DATETIME,
    `failed_at` DATETIME,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create essential indexes for queue performance
CREATE INDEX jobs_queue_status_run_at_index ON jobs (queue, status, run_at);
CREATE INDEX jobs_status_run_at_index ON jobs (status, run_at);
CREATE INDEX jobs_status_index ON jobs (status);
SQL;
    }
    
    private function generatePostgreSqlJobsTable(): string
    {
        return <<<'SQL'
CREATE TABLE "jobs" (
    "id" VARCHAR(36) NOT NULL PRIMARY KEY,
    "queue" VARCHAR(255) NOT NULL DEFAULT 'default',
    "payload" TEXT NOT NULL,
    "status" VARCHAR(50) NOT NULL DEFAULT 'pending' CHECK ("status" IN ('pending', 'processing', 'completed', 'failed')),
    "attempts" INTEGER NOT NULL DEFAULT 0,
    "error" TEXT,
    "run_at" TIMESTAMP NOT NULL,
    "started_at" TIMESTAMP,
    "completed_at" TIMESTAMP,
    "failed_at" TIMESTAMP,
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create essential indexes for queue performance
CREATE INDEX jobs_queue_status_run_at_index ON jobs (queue, status, run_at);
CREATE INDEX jobs_status_run_at_index ON jobs (status, run_at);
CREATE INDEX jobs_status_index ON jobs (status);
SQL;
    }
    
    private function generateSqliteJobsTable(): string
    {
        return <<<'SQL'
CREATE TABLE jobs (
    id TEXT PRIMARY KEY,
    queue TEXT NOT NULL DEFAULT 'default',
    payload TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
    attempts INTEGER NOT NULL DEFAULT 0,
    error TEXT,
    run_at DATETIME NOT NULL,
    started_at DATETIME,
    completed_at DATETIME,
    failed_at DATETIME,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create essential indexes for queue performance
CREATE INDEX jobs_queue_status_run_at_index ON jobs (queue, status, run_at);
CREATE INDEX jobs_status_run_at_index ON jobs (status, run_at);
CREATE INDEX jobs_status_index ON jobs (status);
SQL;
    }
}
