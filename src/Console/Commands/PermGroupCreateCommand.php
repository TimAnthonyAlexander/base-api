<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\Permissions\PermissionsService;
use BaseApi\App;

class PermGroupCreateCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'perm:group:create';
    }

    #[Override]
    public function description(): string
    {
        return 'Create a new permission group';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        if ($args === []) {
            echo ColorHelper::error("❌ Error: Group ID required") . "\n";
            echo ColorHelper::info("Usage: ./mason perm:group:create <group_id> [--weight=N]") . "\n";
            return 1;
        }

        $basePath = $app?->basePath() ?? getcwd();
        App::boot($basePath);

        $groupId = $args[0];
        $weight = 0;

        // Parse options
        foreach ($args as $arg) {
            if (str_starts_with((string) $arg, '--weight=')) {
                $weight = (int) substr((string) $arg, 9);
            }
        }

        try {
            $permissions = App::container()->make(PermissionsService::class);
            $permissions->createGroup($groupId, $weight);

            echo ColorHelper::success(sprintf('✓ Group "%s" created successfully', $groupId)) . "\n";
            echo ColorHelper::info(sprintf("  Weight: %d", $weight)) . "\n";
            
            return 0;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }
}

