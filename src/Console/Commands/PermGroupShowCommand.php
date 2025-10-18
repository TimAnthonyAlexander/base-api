<?php

namespace BaseApi\Console\Commands;

use Override;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\Permissions\PermissionsService;
use BaseApi\App;

class PermGroupShowCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'perm:group:show';
    }

    #[Override]
    public function description(): string
    {
        return 'Show details of a specific permission group';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        if ($args === []) {
            echo ColorHelper::error("❌ Error: Group ID required") . "\n";
            echo ColorHelper::info("Usage: ./mason perm:group:show <group_id>") . "\n";
            return 1;
        }

        $basePath = $app?->basePath() ?? getcwd();
        App::boot($basePath);

        $groupId = $args[0];
        $permissions = App::container()->make(PermissionsService::class);
        $group = $permissions->getGroup($groupId);

        if ($group === null) {
            echo ColorHelper::error(sprintf('❌ Group "%s" does not exist', $groupId)) . "\n";
            return 1;
        }

        echo ColorHelper::header(sprintf('📋 Group: %s', $groupId)) . "\n";
        echo str_repeat('─', 80) . "\n\n";

        // Basic info
        echo ColorHelper::info("Weight: ") . ColorHelper::colorize((string) $group['weight'], ColorHelper::YELLOW) . "\n";
        echo ColorHelper::info("Inherits: ") . ColorHelper::colorize(
            empty($group['inherits']) ? 'None' : implode(', ', $group['inherits']),
            ColorHelper::CYAN
        ) . "\n\n";

        // Permissions
        echo ColorHelper::header("Permissions") . "\n";
        if (empty($group['permissions'])) {
            echo ColorHelper::comment("  No direct permissions") . "\n";
        } else {
            foreach ($group['permissions'] as $node => $value) {
                $icon = $value ? '✓' : '✗';
                $color = $value ? ColorHelper::GREEN : ColorHelper::RED;
                echo ColorHelper::colorize(sprintf('  %s ', $icon), $color);
                echo $node . "\n";
            }
        }

        // All permissions (including inherited)
        echo "\n" . ColorHelper::header("All Permissions (with inheritance)") . "\n";
        $allPerms = $permissions->getRolePermissions($groupId);
        if (empty($allPerms)) {
            echo ColorHelper::comment("  No permissions") . "\n";
        } else {
            // Sort and display
            ksort($allPerms);
            foreach ($allPerms as $node => $value) {
                $icon = $value ? '✓' : '✗';
                $color = $value ? ColorHelper::GREEN : ColorHelper::RED;
                echo ColorHelper::colorize(sprintf('  %s ', $icon), $color);
                echo $node . "\n";
            }
        }

        echo "\n";
        echo ColorHelper::success(sprintf("Total: %d permission(s)", count($allPerms))) . "\n";

        return 0;
    }
}


