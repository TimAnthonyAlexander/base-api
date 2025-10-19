<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\Permissions\PermissionsService;
use BaseApi\App;

class PermGroupRenameCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'perm:group:rename';
    }

    #[Override]
    public function description(): string
    {
        return 'Rename a permission group and update all references';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        if (count($args) < 2) {
            echo ColorHelper::error("❌ Error: Old group ID and new group ID required") . "\n";
            echo ColorHelper::info("Usage: ./mason perm:group:rename <old_id> <new_id>") . "\n";
            echo ColorHelper::comment("Example: ./mason perm:group:rename power-user poweruser") . "\n";
            return 1;
        }

        $basePath = $app?->basePath() ?? getcwd();
        App::boot($basePath);

        $oldId = $args[0];
        $newId = $args[1];

        try {
            $permissions = App::container()->make(PermissionsService::class);
            
            // Show what will be updated
            $group = $permissions->getGroup($oldId);
            if ($group === null) {
                echo ColorHelper::error(sprintf('❌ Group "%s" does not exist', $oldId)) . "\n";
                return 1;
            }

            // Check which groups reference this one
            $references = [];
            foreach ($permissions->getGroups() as $groupId => $groupData) {
                if (in_array($oldId, $groupData['inherits'])) {
                    $references[] = $groupId;
                }
            }

            echo ColorHelper::header(sprintf('📋 Renaming group "%s" to "%s"', $oldId, $newId)) . "\n";
            echo str_repeat('─', 80) . "\n\n";

            echo ColorHelper::info("Group details:") . "\n";
            echo "  Weight: " . $group['weight'] . "\n";
            echo "  Permissions: " . count($group['permissions']) . "\n";
            
            if ($references !== []) {
                echo "\n" . ColorHelper::warning("⚠️  The following groups will be updated:") . "\n";
                foreach ($references as $ref) {
                    echo "  - " . $ref . "\n";
                }
            }

            // Confirm rename
            echo "\n" . ColorHelper::warning("⚠️  Continue with rename? [y/N]: ");
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            fclose($handle);

            if (strtolower(trim($line)) !== 'y') {
                echo ColorHelper::comment("Rename cancelled") . "\n";
                return 0;
            }

            $permissions->renameGroup($oldId, $newId);

            echo ColorHelper::success(sprintf('✓ Group renamed from "%s" to "%s"', $oldId, $newId)) . "\n";
            
            if ($references !== []) {
                echo ColorHelper::success(sprintf('✓ Updated %d referencing group(s)', count($references))) . "\n";
            }
            
            return 0;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error: " . $exception->getMessage()) . "\n";
            return 2;
        }
    }
}






