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

            // Auto-confirm flags / CI detection
            $autoYes = in_array('--yes', $args, true)
                || in_array('-y', $args, true)
                || getenv('CI') === 'true'
                || getenv('GITHUB_ACTIONS') === 'true';

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

            // Confirm execution (unless auto-confirmed)
            if (!$autoYes && !$this->confirmExecution()) {
                echo ColorHelper::comment("❌ Migration cancelled.") . "\n";
                return 0;
            }

            // Execute migrations (now saves each migration immediately after execution)
            $executedIds = $this->executeMigrations($migrationsToApply, $executedPath);

            echo "\n" . ColorHelper::success("Migrations completed successfully!") . "\n";
            echo ColorHelper::info("Executed " . count($executedIds) . " migration(s).") . "\n";

            if ($safeMode && count($executedIds) < count($pendingMigrations)) {
                $remaining = count($pendingMigrations) - count($executedIds);
                echo ColorHelper::warning(sprintf('💡 Note: %d destructive migration(s) remain. Run without --safe to apply them.', $remaining)) . "\n";
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
        echo str_repeat('─', 80) . "\n";

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

    private function executeMigrations(array $migrations, string $executedPath): array
    {
        $pdo = App::db()->getConnection()->pdo();
        $executedIds = [];

        echo ColorHelper::header("🔄 Executing migrations...") . "\n";

        // Group migrations by operation type to ensure proper execution order
        // This prevents foreign key errors by creating all tables first
        $operationGroups = $this->groupMigrationsByOperation($migrations);

        // Define execution order: creates first, then alters, then drops
        $executionOrder = [
            'create_table',
            'add_column',
            'modify_column',
            'add_index',
            'add_fk',
            'drop_fk',
            'drop_index',
            'drop_column',
            'drop_table',
            'unknown'
        ];

        foreach ($executionOrder as $operation) {
            if (!isset($operationGroups[$operation])) {
                continue;
            }

            if ($operationGroups[$operation] === []) {
                continue;
            }

            $operationMigrations = $operationGroups[$operation];
            $operationDisplay = ucwords(str_replace('_', ' ', $operation));

            echo ColorHelper::info(sprintf(
                '📋 Processing: %s (%d migration%s)',
                $operationDisplay,
                count($operationMigrations),
                count($operationMigrations) === 1 ? '' : 's'
            )) . "\n";

            try {
                foreach ($operationMigrations as $i => $migration) {
                    $num = $i + 1;
                    $table = $migration['table'] ?? 'general';
                    echo ColorHelper::comment(sprintf('  %s. [%s] ', $num, $table)) .
                        ColorHelper::colorize(substr((string) $migration['sql'], 0, 60) . "...", ColorHelper::BRIGHT_BLACK) . "\n";

                    // Execute DDL statements individually (they cause implicit commits in MySQL)
                    $pdo->exec($migration['sql']);
                    
                    // Immediately record this migration as executed to prevent re-execution on failure
                    ExecutedMigrationsFile::addExecuted($executedPath, $migration['id']);
                    $executedIds[] = $migration['id'];
                }

                echo ColorHelper::success(sprintf('  ✓ %s completed successfully', $operationDisplay)) . "\n";
            } catch (Exception $e) {
                // Convert PDO error code to int since Exception constructor expects int
                $code = is_numeric($e->getCode()) ? (int)$e->getCode() : 0;
                $table = $migration['table'] ?? 'unknown';
                
                // Log how many migrations were successfully executed before the failure
                if ($executedIds !== []) {
                    echo ColorHelper::warning(sprintf("\n⚠️  %d migration(s) were successfully executed before failure.", count($executedIds))) . "\n";
                    echo ColorHelper::info("✓ These have been recorded and will not be re-executed.") . "\n";
                }
                
                throw new Exception(ColorHelper::error(sprintf('Failed to execute %s for table %s: ', $operation, $table)) . $e->getMessage(), $code, $e);
            }
        }

        echo "\n" . ColorHelper::success("🎉 All migrations executed successfully!") . "\n";
        return $executedIds;
    }

    private function groupMigrationsByOperation(array $migrations): array
    {
        $groups = [];

        foreach ($migrations as $migration) {
            $operation = $migration['operation'] ?? 'unknown';

            if (!isset($groups[$operation])) {
                $groups[$operation] = [];
            }

            $groups[$operation][] = $migration;
        }

        return $groups;
    }
}
