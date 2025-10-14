<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\Permissions\PermissionsService;
use BaseApi\App;

class PermGrantCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'perm:grant';
    }

    #[Override]
    public function description(): string
    {
        return 'Grant a permission to a group';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        if (count($args) < 2) {
            echo ColorHelper::error("❌ Error: Group ID and permission node required") . "\n";
            echo ColorHelper::info("Usage: ./mason perm:grant <group_id> <permission_node> [--deny] [--force]") . "\n";
            echo ColorHelper::comment("Examples:") . "\n";
            echo ColorHelper::comment("  ./mason perm:grant user content.create") . "\n";
            echo ColorHelper::comment("  ./mason perm:grant guest admin.* --deny") . "\n";
            echo ColorHelper::comment("  ./mason perm:grant admin '*' --force") . "\n";
            return 1;
        }

        $basePath = $app?->basePath() ?? getcwd();
        App::boot($basePath);

        $groupId = $args[0];
        $node = $args[1];
        $allow = !in_array('--deny', $args);
        $force = in_array('--force', $args);

        try {
            $permissions = App::container()->make(PermissionsService::class);
            $permissions->grant($groupId, $node, $allow, $force);

            $action = $allow ? 'granted' : 'denied';
            $color = $allow ? ColorHelper::GREEN : ColorHelper::RED;
            
            echo ColorHelper::success(sprintf('✓ Permission "%s" %s for group "%s"', $node, $action, $groupId)) . "\n";
            echo "  " . ColorHelper::colorize($node, $color) . " → " . ($allow ? 'ALLOW' : 'DENY') . "\n";
            
            return 0;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error: " . $exception->getMessage()) . "\n";
            return 2;
        }
    }
}

