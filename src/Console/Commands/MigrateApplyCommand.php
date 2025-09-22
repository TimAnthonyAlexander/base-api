<?php

namespace BaseApi\Console\Commands;

use Override;
use BaseApi\Console\Application;
use Exception;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
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
                echo ColorHelper::warning(sprintf('⚠️  No migrations found at %s', $migrationsFile)) . "\n";
                echo ColorHelper::info("📊 Run 'migrate:generate' first to create migrations.") . "\n";
                return 1;
            }

            // Get pending migrations (ones not yet executed)
            $pendingMigrations = ExecutedMigrationsFile::getPendingMigrations($allMigrations, $executedPath);

            if ($pendingMigrations === []) {
                echo ColorHelper::success("No pending migrations to apply. Database is up to date.") . "\n";
                return 0;
            }

            echo ColorHelper::info("🔍 Found " . count($pendingMigrations) . " pending migration(s).") . "\n";

            // Filter out destructive operations in safe mode
            $migrationsToApply = $pendingMigrations;
            if ($safeMode) {
                $originalCount = count($migrationsToApply);
                $migrationsToApply = array_filter($migrationsToApply, fn($mig): bool => !($mig['destructive'] ?? false));
                $filteredCount = $originalCount - count($migrationsToApply);

                if ($filteredCount > 0) {
                    echo ColorHelper::warning(sprintf('⚠️  Safe mode: Skipping %d destructive operations.', $filteredCount)) . "\n";
                }
            }

            if ($migrationsToApply === []) {
                echo ColorHelper::comment(" No migrations to execute after filtering.") . "\n";
                return 0;
            }

            // Show what will be executed
            $this->showExecutionPlan($migrationsToApply, $safeMode);

            // Confirm execution
            if (!$this->confirmExecution()) {
                echo ColorHelper::comment("❌ Migration cancelled.") . "\n";
                return 0;
            }

            // Execute migrations
            $executedIds = $this->executeMigrations($migrationsToApply);

            // Update executed migrations file
            ExecutedMigrationsFile::addMultipleExecuted($executedPath, $executedIds);

            echo "\n" . ColorHelper::success("Migrations completed successfully!") . "\n";
            echo ColorHelper::info("Executed " . count($executedIds) . " migration(s).") . "\n";

            if ($safeMode && count($executedIds) < count($pendingMigrations)) {
                $remaining = count($pendingMigrations) - count($executedIds);
                echo ColorHelper::warning(sprintf('⚠️  Note: %d destructive migration(s) remain. Run without --safe to apply them.', $remaining)) . "\n";
            }

            return 0;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }

    private function showExecutionPlan(array $migrations, bool $safeMode): void
    {
        echo ColorHelper::header("📋 Execution Plan" . ($safeMode ? " (Safe Mode)" : "")) . "\n";
        echo ColorHelper::colorize("==========================================", ColorHelper::BRIGHT_CYAN) . "\n";

        foreach ($migrations as $i => $migration) {
            $num = $i + 1;
            $destructive = ($migration['destructive'] ?? false) ? ColorHelper::colorize(" [DESTRUCTIVE]", ColorHelper::RED) : "";
            $warning = (empty($migration['warning'])) ? "" : ' - ' . ColorHelper::colorize($migration['warning'], ColorHelper::YELLOW);
            $table = $migration['table'] ? ColorHelper::colorize(sprintf(' (%s)', $migration['table']), ColorHelper::CYAN) : "";

            echo ColorHelper::info($num . '. ') .
                ColorHelper::colorize(sprintf('[%s]', $migration['operation']), ColorHelper::GREEN) .
                $table . " " .
                ColorHelper::comment($migration['sql']) .
                $destructive . $warning . "\n\n";
        }
    }

    private function confirmExecution(): bool
    {
        echo ColorHelper::warning("⚠️  Do you want to execute these migrations? [y/N]: ");
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);

        return strtolower(trim($line)) === 'y';
    }

    private function executeMigrations(array $migrations): array
    {
        $pdo = App::db()->getConnection()->pdo();
        $executedIds = [];

        echo ColorHelper::header("🔄 Executing migrations...") . "\n";

        // Group migrations by table for better organization
        $tableGroups = $this->groupMigrationsByTable($migrations);

        foreach ($tableGroups as $table => $tableMigrations) {
            echo ColorHelper::info(sprintf('📋 Processing table: %s', $table)) . "\n";

            try {
                foreach ($tableMigrations as $i => $migration) {
                    $num = $i + 1;
                    echo ColorHelper::comment(sprintf('  %s. Executing [%s]: ', $num, $migration['operation'])) .
                        ColorHelper::colorize(substr((string) $migration['sql'], 0, 50) . "...", ColorHelper::BRIGHT_BLACK) . "\n";

                    // Execute DDL statements individually (they cause implicit commits in MySQL)
                    $pdo->exec($migration['sql']);
                    $executedIds[] = $migration['id'];
                }

                echo ColorHelper::success(sprintf('  ✓ Table %s completed successfully', $table)) . "\n";
            } catch (Exception $e) {
                throw new Exception(ColorHelper::error(sprintf('Failed to execute migration for table %s: ', $table)) . $e->getMessage(), $e->getCode(), $e);
            }
        }

        echo "\n" . ColorHelper::success("🎉 All migrations executed successfully!") . "\n";
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
