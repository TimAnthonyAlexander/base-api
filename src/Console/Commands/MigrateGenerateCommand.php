<?php

namespace BaseApi\Console\Commands;

use BaseApi\Console\Command;
use BaseApi\Database\Migrations\ModelScanner;
use BaseApi\Database\Migrations\DatabaseIntrospector;
use BaseApi\Database\Migrations\DiffEngine;
use BaseApi\Database\Migrations\MigrationsFile;
use BaseApi\Database\Migrations\SqlGenerator;
use BaseApi\App;

class MigrateGenerateCommand implements Command
{
    public function name(): string
    {
        return 'migrate:generate';
    }

    public function description(): string
    {
        return 'Generate migration plan from model changes';
    }

    public function execute(array $args, ?\BaseApi\Console\Application $app = null): int
    {
        try {
            echo "Scanning models...\n";
            
            // Scan models
            $scanner = new ModelScanner();
            $modelSchema = $scanner->scan(App::basePath('app/Models'));
            
            echo "Introspecting database...\n";
            
            // Introspect database
            $introspector = new DatabaseIntrospector();
            $dbSchema = $introspector->snapshot();
            
            echo "Generating migration plan...\n";
            
            // Generate diff
            $diffEngine = new DiffEngine();
            $plan = $diffEngine->diff($modelSchema, $dbSchema);
            
            if ($plan->isEmpty()) {
                echo "\nNo changes detected. Database is up to date.\n";
                return 0;
            }
            
            // Generate SQL statements from the plan
            echo "Converting to SQL statements...\n";
            $generator = new SqlGenerator();
            $sqlStatements = $generator->generate($plan);
            
            // Convert SQL statements to migration format
            $migrations = [];
            foreach ($sqlStatements as $statement) {
                $table = $this->extractTableFromSql($statement['sql']);
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
            
            // Append new migrations to file
            $addedCount = count($migrations);
            MigrationsFile::appendMigrations($fullPath, $migrations);
            
            // Check if any were actually added (after deduplication)
            $currentMigrations = MigrationsFile::readMigrations($fullPath);
            $actuallyAdded = array_filter($currentMigrations, function($mig) use ($migrations) {
                return in_array($mig['id'], array_column($migrations, 'id'));
            });
            
            $finalCount = count($actuallyAdded);
            
            // Print summary
            $this->printSummary($migrations);
            
            if ($finalCount === 0) {
                echo "\nNo new migrations added (duplicates filtered out).\n";
            } else {
                echo "\n{$finalCount} new migrations added to: {$migrationsFile}\n";
                echo "Run 'migrate:apply' to execute pending migrations.\n";
            }
            
            return 0;
            
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return 1;
        }
    }

    private function printSummary(array $migrations): void
    {
        if (empty($migrations)) {
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
        
        echo "\nMigration Summary:\n";
        echo "==================\n";
        
        foreach ($counts as $operation => $count) {
            echo ucwords(str_replace('_', ' ', $operation)) . ": {$count}\n";
        }
        
        if ($destructiveCount > 0) {
            echo "\nWARNING: {$destructiveCount} destructive operations detected!\n";
            echo "Use 'migrate:apply --safe' to skip destructive changes.\n";
        }
    }

    private function extractTableFromSql(string $sql): ?string
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
        
        if (strpos($sql, 'CREATE TABLE') === 0) {
            return 'create_table';
        }
        if (strpos($sql, 'DROP TABLE') === 0) {
            return 'drop_table';
        }
        if (strpos($sql, 'CREATE UNIQUE INDEX') === 0 || strpos($sql, 'CREATE INDEX') === 0) {
            return 'add_index';
        }
        if (strpos($sql, 'DROP INDEX') === 0) {
            return 'drop_index';
        }
        if (strpos($sql, 'ALTER TABLE') !== false) {
            if (strpos($sql, 'ADD COLUMN') !== false) {
                return 'add_column';
            }
            if (strpos($sql, 'DROP COLUMN') !== false) {
                return 'drop_column';
            }
            if (strpos($sql, 'MODIFY COLUMN') !== false || strpos($sql, 'ALTER COLUMN') !== false) {
                return 'modify_column';
            }
            if (strpos($sql, 'ADD INDEX') !== false || strpos($sql, 'CREATE INDEX') !== false) {
                return 'add_index';
            }
            if (strpos($sql, 'DROP INDEX') !== false) {
                return 'drop_index';
            }
            if (strpos($sql, 'ADD FOREIGN KEY') !== false || strpos($sql, 'ADD CONSTRAINT') !== false) {
                return 'add_fk';
            }
            if (strpos($sql, 'DROP FOREIGN KEY') !== false || strpos($sql, 'DROP CONSTRAINT') !== false) {
                return 'drop_fk';
            }
        }
        
        return 'unknown';
    }
}
