<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\Permissions\PermissionsService;
use BaseApi\App;

class PermGroupRemoveParentCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'perm:group:remove-parent';
    }

    #[Override]
    public function description(): string
    {
        return 'Remove a parent group from inheritance';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        if (count($args) < 2) {
            echo ColorHelper::error("❌ Error: Group ID and parent ID required") . "\n";
            echo ColorHelper::info("Usage: ./mason perm:group:remove-parent <group_id> <parent_id>") . "\n";
            return 1;
        }

        $basePath = $app?->basePath() ?? getcwd();
        App::boot($basePath);

        $groupId = $args[0];
        $parentId = $args[1];

        try {
            $permissions = App::container()->make(PermissionsService::class);
            $permissions->removeParent($groupId, $parentId);

            echo ColorHelper::success(sprintf('✓ Group "%s" no longer inherits from "%s"', $groupId, $parentId)) . "\n";
            
            return 0;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }
}

