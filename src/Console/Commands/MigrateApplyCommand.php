<?php

namespace BaseApi\Console\Commands;

use BaseApi\Console\Command;
use BaseApi\Database\Migrations\MigrationPlan;
use BaseApi\Database\Migrations\SqlGenerator;
use BaseApi\Database\Migrations\MigrationsFile;
use BaseApi\App;
use PDO;

class MigrateApplyCommand implements Command
{
    public function name(): string
    {
        return 'migrate:apply';
    }

    public function description(): string
    {
        return 'Apply migration plan to database';
    }

    public function execute(array $args, ?\BaseApi\Console\Application $app = null): int
    {
        try {
            // Check for --safe flag
            $safeMode = in_array('--safe', $args);
            
            // Get migrations file path
            $migrationsFile = App::config()->get('MIGRATIONS_FILE', 'storage/migrations.json');
            $fullPath = App::basePath($migrationsFile);
            
            // Read migration plan
            $planData = MigrationsFile::read($fullPath);
            if ($planData === null) {
                echo "Error: No migration plan found at {$migrationsFile}\n";
                echo "Run 'migrate:generate' first to create a migration plan.\n";
                return 1;
            }
            
            // Check if already applied
            if (MigrationsFile::isApplied($fullPath)) {
                echo "Migration plan has already been applied.\n";
                echo "Run 'migrate:generate' to create a new plan.\n";
                return 0;
            }
            
            $plan = MigrationPlan::fromArray($planData);
            
            if ($plan->isEmpty()) {
                echo "No migrations to apply.\n";
                return 0;
            }
            
            // Generate SQL statements
            $generator = new SqlGenerator();
            $statements = $generator->generate($plan);
            
            // Filter out destructive operations in safe mode
            if ($safeMode) {
                $originalCount = count($statements);
                $statements = array_filter($statements, fn($stmt) => !($stmt['destructive'] ?? false));
                $filteredCount = $originalCount - count($statements);
                
                if ($filteredCount > 0) {
                    echo "Safe mode: Skipping {$filteredCount} destructive operations.\n";
                }
            }
            
            if (empty($statements)) {
                echo "No statements to execute.\n";
                return 0;
            }
            
            // Show what will be executed
            $this->showExecutionPlan($statements, $safeMode);
            
            // Confirm execution
            if (!$this->confirmExecution()) {
                echo "Migration cancelled.\n";
                return 0;
            }
            
            // Execute migrations
            $this->executeMigrations($statements);
            
            // Mark as applied if not in safe mode or no destructive operations were skipped
            if (!$safeMode || $plan->getDestructiveCount() === 0) {
                MigrationsFile::markApplied($fullPath);
                echo "\nMigration completed and marked as applied.\n";
            } else {
                echo "\nMigration completed (partial - destructive operations skipped).\n";
                echo "Run without --safe to apply destructive changes.\n";
            }
            
            return 0;
            
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return 1;
        }
    }

    private function showExecutionPlan(array $statements, bool $safeMode): void
    {
        echo "Execution Plan" . ($safeMode ? " (Safe Mode)" : "") . ":\n";
        echo "==========================================\n";
        
        foreach ($statements as $i => $statement) {
            $num = $i + 1;
            $destructive = $statement['destructive'] ? " [DESTRUCTIVE]" : "";
            $warning = $statement['warning'] ? " - {$statement['warning']}" : "";
            
            echo "{$num}. {$statement['sql']}{$destructive}{$warning}\n\n";
        }
    }

    private function confirmExecution(): bool
    {
        echo "Do you want to execute these migrations? [y/N]: ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        return strtolower(trim($line)) === 'y';
    }

    private function executeMigrations(array $statements): void
    {
        $pdo = App::db()->pdo();
        
        echo "Executing migrations...\n";
        
        // Group statements by table for transaction boundaries
        $tableGroups = $this->groupStatementsByTable($statements);
        
        foreach ($tableGroups as $table => $tableStatements) {
            echo "Processing table: {$table}\n";
            
            $pdo->beginTransaction();
            
            try {
                foreach ($tableStatements as $i => $statement) {
                    $num = $i + 1;
                    echo "  {$num}. Executing: " . substr($statement['sql'], 0, 50) . "...\n";
                    
                    $pdo->exec($statement['sql']);
                }
                
                $pdo->commit();
                echo "  ✓ Table {$table} completed successfully\n";
                
            } catch (\Exception $e) {
                $pdo->rollBack();
                throw new \Exception("Failed to execute migration for table {$table}: " . $e->getMessage());
            }
        }
        
        echo "\nAll migrations executed successfully!\n";
    }

    private function groupStatementsByTable(array $statements): array
    {
        $groups = [];
        
        foreach ($statements as $statement) {
            $sql = $statement['sql'];
            
            // Extract table name from SQL
            $table = $this->extractTableName($sql);
            if (!$table) {
                $table = 'general';
            }
            
            if (!isset($groups[$table])) {
                $groups[$table] = [];
            }
            
            $groups[$table][] = $statement;
        }
        
        return $groups;
    }

    private function extractTableName(string $sql): ?string
    {
        // Simple regex to extract table names from common SQL patterns
        $patterns = [
            '/CREATE TABLE `?(\w+)`?/i',
            '/ALTER TABLE `?(\w+)`?/i',
            '/DROP TABLE `?(\w+)`?/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $sql, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
}
