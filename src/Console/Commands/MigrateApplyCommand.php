<?php

namespace BaseApi\Console\Commands;

use Override;
use BaseApi\Console\Application;
use Exception;
use BaseApi\Console\Command;
use BaseApi\Database\Migrations\MigrationsFile;
use BaseApi\Database\Migrations\ExecutedMigrationsFile;
use BaseApi\App;

class MigrateApplyCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'migrate:apply';
    }

    #[Override]
    public function description(): string
    {
        return 'Apply migration plan to database';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        try {
            // Check for --safe flag
            $safeMode = in_array('--safe', $args);
            
            // Get file paths
            $migrationsFile = App::config()->get('MIGRATIONS_FILE', 'storage/migrations.json');
            $executedMigrationsFile = App::config()->get('EXECUTED_MIGRATIONS_FILE', 'storage/executed-migrations.json');
            
            $migrationsPath = App::basePath($migrationsFile);
            $executedPath = App::basePath($executedMigrationsFile);
            
            // Read all migrations
            $allMigrations = MigrationsFile::readMigrations($migrationsPath);
            if ($allMigrations === []) {
                echo sprintf('No migrations found at %s%s', $migrationsFile, PHP_EOL);
                echo "Run 'migrate:generate' first to create migrations.\n";
                return 1;
            }
            
            // Get pending migrations (ones not yet executed)
            $pendingMigrations = ExecutedMigrationsFile::getPendingMigrations($allMigrations, $executedPath);
            
            if ($pendingMigrations === []) {
                echo "No pending migrations to apply. Database is up to date.\n";
                return 0;
            }
            
            echo "Found " . count($pendingMigrations) . " pending migration(s).\n";
            
            // Filter out destructive operations in safe mode
            $migrationsToApply = $pendingMigrations;
            if ($safeMode) {
                $originalCount = count($migrationsToApply);
                $migrationsToApply = array_filter($migrationsToApply, fn($mig): bool => !($mig['destructive'] ?? false));
                $filteredCount = $originalCount - count($migrationsToApply);
                
                if ($filteredCount > 0) {
                    echo "Safe mode: Skipping {$filteredCount} destructive operations.\n";
                }
            }
            
            if ($migrationsToApply === []) {
                echo "No migrations to execute after filtering.\n";
                return 0;
            }
            
            // Show what will be executed
            $this->showExecutionPlan($migrationsToApply, $safeMode);
            
            // Confirm execution
            if (!$this->confirmExecution()) {
                echo "Migration cancelled.\n";
                return 0;
            }
            
            // Execute migrations
            $executedIds = $this->executeMigrations($migrationsToApply);
            
            // Update executed migrations file
            ExecutedMigrationsFile::addMultipleExecuted($executedPath, $executedIds);
            
            echo "\nMigrations completed successfully!\n";
            echo "Executed " . count($executedIds) . " migration(s).\n";
            
            if ($safeMode && count($executedIds) < count($pendingMigrations)) {
                $remaining = count($pendingMigrations) - count($executedIds);
                echo "Note: {$remaining} destructive migration(s) remain. Run without --safe to apply them.\n";
            }
            
            return 0;
            
        } catch (Exception $exception) {
            echo "Error: " . $exception->getMessage() . "\n";
            return 1;
        }
    }

    private function showExecutionPlan(array $migrations, bool $safeMode): void
    {
        echo "Execution Plan" . ($safeMode ? " (Safe Mode)" : "") . ":\n";
        echo "==========================================\n";
        
        foreach ($migrations as $i => $migration) {
            $num = $i + 1;
            $destructive = ($migration['destructive'] ?? false) ? " [DESTRUCTIVE]" : "";
            $warning = (empty($migration['warning'])) ? "" : ' - ' . $migration['warning'];
            $table = $migration['table'] ? sprintf(' (%s)', $migration['table']) : "";
            
            echo "{$num}. [{$migration['operation']}]{$table} {$migration['sql']}{$destructive}{$warning}\n\n";
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

    private function executeMigrations(array $migrations): array
    {
        $pdo = App::db()->getConnection()->pdo();
        $executedIds = [];
        
        echo "Executing migrations...\n";
        
        // Group migrations by table for transaction boundaries
        $tableGroups = $this->groupMigrationsByTable($migrations);
        
        foreach ($tableGroups as $table => $tableMigrations) {
            echo sprintf('Processing table: %s%s', $table, PHP_EOL);
            
            $pdo->beginTransaction();
            
            try {
                foreach ($tableMigrations as $i => $migration) {
                    $num = $i + 1;
                    echo sprintf('  %s. Executing [%s]: ', $num, $migration['operation']) . substr((string) $migration['sql'], 0, 50) . "...\n";
                    
                    $pdo->exec($migration['sql']);
                    $executedIds[] = $migration['id'];
                }
                
                $pdo->commit();
                echo "  ✓ Table {$table} completed successfully\n";
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw new Exception(sprintf('Failed to execute migration for table %s: ', $table) . $e->getMessage(), $e->getCode(), $e);
            }
        }
        
        echo "\nAll migrations executed successfully!\n";
        return $executedIds;
    }

    private function groupMigrationsByTable(array $migrations): array
    {
        $groups = [];
        
        foreach ($migrations as $migration) {
            $table = $migration['table'] ?? 'general';
            
            if (!isset($groups[$table])) {
                $groups[$table] = [];
            }
            
            $groups[$table][] = $migration;
        }
        
        return $groups;
    }
}
