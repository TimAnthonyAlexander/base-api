<?php

namespace BaseApi\Console\Commands;

use Override;
use BaseApi\Console\Application;
use Exception;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\Database\Migrations\ModelScanner;
use BaseApi\Database\Migrations\DatabaseIntrospector;
use BaseApi\Database\Migrations\DiffEngine;
use BaseApi\Database\Migrations\MigrationsFile;
use BaseApi\Database\Migrations\SqlGenerator;
use BaseApi\App;

class MigrateGenerateCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'migrate:generate';
    }

    #[Override]
    public function description(): string
    {
        return 'Generate migration plan from model changes';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        try {
            echo ColorHelper::info("🔍 Scanning models...") . "\n";
            
            // Scan models
            $scanner = new ModelScanner();
            $modelSchema = $scanner->scan(App::basePath('app/Models'));
            
            echo ColorHelper::info("📋 Introspecting database...") . "\n";
            
            // Introspect database
            $introspector = new DatabaseIntrospector();
            $dbSchema = $introspector->snapshot();
            
            echo ColorHelper::info("🔄 Generating migration plan...") . "\n";
            
            // Generate diff
            $diffEngine = new DiffEngine();
            $plan = $diffEngine->diff($modelSchema, $dbSchema);
            
            if ($plan->isEmpty()) {
                echo "\n" . ColorHelper::success("✅ No changes detected. Database is up to date.") . "\n";
                return 0;
            }
            
            // Generate SQL statements from the plan
            echo ColorHelper::info("⚙️  Converting to SQL statements...") . "\n";
            $generator = new SqlGenerator();
            $sqlStatements = $generator->generate($plan);
            
            // Convert SQL statements to migration format
            $migrations = [];
            foreach ($sqlStatements as $statement) {
                // Use table from statement if provided, otherwise extract from SQL
                $table = $statement['table'] ?? $this->extractTableFromSql($statement['sql']);
                $operation = $this->guessOperationFromSql($statement['sql']);
                
                $migrations[] = [
                    'id' => MigrationsFile::generateMigrationId(
                        $statement['sql'], 
                        $table, 
                        $operation
                    ),
                    'sql' => $statement['sql'],
                    'destructive' => $statement['destructive'] ?? false,
                    'generated_at' => date('c'),
                    'table' => $table,
                    'operation' => $operation,
                    'warning' => $statement['warning'] ?? null
                ];
            }
            
            // Get migrations file path
            $migrationsFile = App::config()->get('MIGRATIONS_FILE', 'storage/migrations.json');
            $fullPath = App::basePath($migrationsFile);
            
            // Get existing migrations before adding new ones
            $existingMigrations = MigrationsFile::readMigrations($fullPath);
            $existingIds = array_column($existingMigrations, 'id');
            
            // Append new migrations to file
            MigrationsFile::appendMigrations($fullPath, $migrations);
            
            // Count how many were actually new (not duplicates)
            $newMigrationIds = array_column($migrations, 'id');
            $actuallyNewIds = array_diff($newMigrationIds, $existingIds);
            $finalCount = count($actuallyNewIds);
            
            // Print summary
            $this->printSummary($migrations);
            
            if ($finalCount === 0) {
                echo "\n" . ColorHelper::comment("ℹ️  No new migrations added (duplicates filtered out).") . "\n";
            } else {
                echo "\n" . ColorHelper::success(sprintf('✅ %d new migrations added to: %s', $finalCount, $migrationsFile)) . "\n";
                echo ColorHelper::info("📊 Run 'migrate:apply' to execute pending migrations.") . "\n";
            }
            
            return 0;
            
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }

    private function printSummary(array $migrations): void
    {
        if ($migrations === []) {
            return;
        }
        
        $counts = [];
        $destructiveCount = 0;
        
        foreach ($migrations as $migration) {
            $operation = $migration['operation'];
            $counts[$operation] = ($counts[$operation] ?? 0) + 1;
            
            if ($migration['destructive'] ?? false) {
                $destructiveCount++;
            }
        }
        
        echo "\n" . ColorHelper::header("📊 Migration Summary") . "\n";
        echo ColorHelper::colorize("==================", ColorHelper::BRIGHT_CYAN) . "\n";
        
        foreach ($counts as $operation => $count) {
            $displayName = ucwords(str_replace('_', ' ', $operation));
            echo ColorHelper::info($displayName . ': ') . ColorHelper::colorize((string)$count, ColorHelper::YELLOW) . "\n";
        }
        
        if ($destructiveCount > 0) {
            echo "\n" . ColorHelper::warning(sprintf('⚠️  WARNING: %d destructive operations detected!', $destructiveCount)) . "\n";
            echo ColorHelper::info("💡 Use 'migrate:apply --safe' to skip destructive changes.") . "\n";
        }
    }

    private function extractTableFromSql(string $sql): string
    {
        // Enhanced regex to extract table names from common SQL patterns
        // Handles backticks, double quotes, square brackets, or no quotes
        $patterns = [
            '/CREATE TABLE [`"\[]?(\w+)[`"\]]?/i',
            '/ALTER TABLE [`"\[]?(\w+)[`"\]]?/i',
            '/DROP TABLE [`"\[]?(\w+)[`"\]]?/i',
            '/CREATE (?:UNIQUE )?INDEX .* ON [`"\[]?(\w+)[`"\]]?/i',
            '/DROP INDEX .* ON [`"\[]?(\w+)[`"\]]?/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $sql, $matches)) {
                return $matches[1];
            }
        }

        return 'unknown';  // Return fallback instead of null
    }

    private function guessOperationFromSql(string $sql): string
    {
        $sql = strtoupper(trim($sql));

        if (str_starts_with($sql, 'CREATE TABLE')) {
            return 'create_table';
        }

        if (str_starts_with($sql, 'DROP TABLE')) {
            return 'drop_table';
        }

        if (str_starts_with($sql, 'CREATE UNIQUE INDEX') || str_starts_with($sql, 'CREATE INDEX')) {
            return 'add_index';
        }

        if (str_starts_with($sql, 'DROP INDEX')) {
            return 'drop_index';
        }

        if (str_contains($sql, 'ALTER TABLE')) {
            if (str_contains($sql, 'ADD COLUMN')) {
                return 'add_column';
            }

            if (str_contains($sql, 'DROP COLUMN')) {
                return 'drop_column';
            }

            if (str_contains($sql, 'MODIFY COLUMN') || str_contains($sql, 'ALTER COLUMN')) {
                return 'modify_column';
            }

            if (str_contains($sql, 'ADD INDEX') || str_contains($sql, 'CREATE INDEX')) {
                return 'add_index';
            }

            if (str_contains($sql, 'DROP INDEX')) {
                return 'drop_index';
            }

            if (str_contains($sql, 'ADD FOREIGN KEY') || str_contains($sql, 'ADD CONSTRAINT')) {
                return 'add_fk';
            }

            if (str_contains($sql, 'DROP FOREIGN KEY') || str_contains($sql, 'DROP CONSTRAINT')) {
                return 'drop_fk';
            }
        }

        return 'unknown';
    }
}
