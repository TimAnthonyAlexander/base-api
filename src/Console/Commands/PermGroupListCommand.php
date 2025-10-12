<?php

namespace BaseApi\Console\Commands;

use Override;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\Permissions\PermissionsService;
use BaseApi\App;

class PermGroupListCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'perm:group:list';
    }

    #[Override]
    public function description(): string
    {
        return 'List all permission groups';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        $basePath = $app?->basePath() ?? getcwd();
        App::boot($basePath);

        $permissions = App::container()->make(PermissionsService::class);
        $groups = $permissions->getGroups();

        if (empty($groups)) {
            echo ColorHelper::warning("⚠️  No groups found") . "\n";
            return 0;
        }

        echo ColorHelper::header("📋 Permission Groups") . "\n";
        echo str_repeat('─', 100) . "\n";

        // Header
        $headerName = str_pad('GROUP', 20);
        $headerWeight = str_pad('WEIGHT', 10);
        $headerInherits = str_pad('INHERITS', 30);
        $headerPerms = 'PERMISSIONS';

        echo ColorHelper::colorize($headerName, ColorHelper::BRIGHT_WHITE);
        echo ColorHelper::colorize($headerWeight, ColorHelper::BRIGHT_WHITE);
        echo ColorHelper::colorize($headerInherits, ColorHelper::BRIGHT_WHITE);
        echo ColorHelper::colorize($headerPerms, ColorHelper::BRIGHT_WHITE);
        echo "\n";
        echo str_repeat('─', 100) . "\n";

        // Sort by weight
        uasort($groups, fn($a, $b): int => $b['weight'] <=> $a['weight']);

        foreach ($groups as $id => $group) {
            $name = str_pad($id, 20);
            $weight = str_pad((string) $group['weight'], 10);
            $inherits = str_pad(implode(', ', $group['inherits']) ?: '-', 30);
            $perms = count($group['permissions']);

            echo ColorHelper::colorize($name, ColorHelper::CYAN);
            echo ColorHelper::colorize($weight, ColorHelper::YELLOW);
            echo ColorHelper::comment($inherits);
            echo ColorHelper::colorize((string) $perms, ColorHelper::GREEN) . " permission(s)";
            echo "\n";
        }

        echo str_repeat('─', 100) . "\n";
        echo ColorHelper::success(sprintf("Total: %d group(s)", count($groups))) . "\n";

        return 0;
    }
}

