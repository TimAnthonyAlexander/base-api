<?php

namespace BaseApi\Console\Commands;

use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\Database\Migrations\MigrationsFile;
use BaseApi\Database\Migrations\SqlGenerator;
use BaseApi\App;

class CreateJobsTableCommand implements Command
{
    public function name(): string
    {
        return 'queue:install';
    }

    public function description(): string
    {
        return 'Create the jobs table migration for queue system';
    }

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
            
            echo "Jobs table migration created successfully!\n";
            echo "Run 'console migrate:apply' to create the jobs table.\n";
            
            return 0;
        } catch (\Exception $e) {
            echo "Error creating jobs table migration: " . $e->getMessage() . "\n";
            return 1;
        }
    }
    
    private function createJobsTableMigration(): array
    {
        $sql = "CREATE TABLE jobs (
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
)";

        // Create indexes for performance
        $indexMigrations = [
            "CREATE INDEX idx_jobs_queue_status_run_at ON jobs(queue, status, run_at)",
            "CREATE INDEX idx_jobs_status_run_at ON jobs(status, run_at)",
            "CREATE INDEX idx_jobs_status ON jobs(status)",
        ];
        
        // Return the table creation migration
        $migration = [
            'id' => MigrationsFile::generateMigrationId($sql, 'jobs', 'create_table'),
            'sql' => $sql,
            'destructive' => false,
            'generated_at' => date('c'),
            'table' => 'jobs',
            'operation' => 'create_table',
            'description' => 'Create jobs table for queue system'
        ];
        
        return $migration;
    }
}
