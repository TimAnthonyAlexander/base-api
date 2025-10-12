<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\Permissions\PermissionsService;
use BaseApi\App;

class PermRevokeCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'perm:revoke';
    }

    #[Override]
    public function description(): string
    {
        return 'Revoke a permission from a group';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        if (count($args) < 2) {
            echo ColorHelper::error("❌ Error: Group ID and permission node required") . "\n";
            echo ColorHelper::info("Usage: ./mason perm:revoke <group_id> <permission_node>") . "\n";
            echo ColorHelper::comment("Example: ./mason perm:revoke user content.delete") . "\n";
            return 1;
        }

        $basePath = $app?->basePath() ?? getcwd();
        App::boot($basePath);

        $groupId = $args[0];
        $node = $args[1];

        try {
            $permissions = App::container()->make(PermissionsService::class);
            $permissions->revoke($groupId, $node);

            echo ColorHelper::success(sprintf('✓ Permission "%s" revoked from group "%s"', $node, $groupId)) . "\n";
            
            return 0;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }
}

