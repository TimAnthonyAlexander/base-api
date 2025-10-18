<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\Permissions\PermissionsService;
use BaseApi\App;

class PermGroupDeleteCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'perm:group:delete';
    }

    #[Override]
    public function description(): string
    {
        return 'Delete a permission group';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        if ($args === []) {
            echo ColorHelper::error("❌ Error: Group ID required") . "\n";
            echo ColorHelper::info("Usage: ./mason perm:group:delete <group_id>") . "\n";
            return 1;
        }

        $basePath = $app?->basePath() ?? getcwd();
        App::boot($basePath);

        $groupId = $args[0];

        try {
            $permissions = App::container()->make(PermissionsService::class);
            
            // Confirm deletion
            echo ColorHelper::warning(sprintf('⚠️  Are you sure you want to delete group "%s"? [y/N]: ', $groupId));
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            fclose($handle);

            if (strtolower(trim($line)) !== 'y') {
                echo ColorHelper::comment("Deletion cancelled") . "\n";
                return 0;
            }

            $permissions->deleteGroup($groupId);

            echo ColorHelper::success(sprintf('✓ Group "%s" deleted successfully', $groupId)) . "\n";
            
            return 0;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }
}


