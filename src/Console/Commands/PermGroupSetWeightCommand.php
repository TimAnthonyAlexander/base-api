<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\Permissions\PermissionsService;
use BaseApi\App;

class PermGroupSetWeightCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'perm:group:set-weight';
    }

    #[Override]
    public function description(): string
    {
        return 'Set the weight of a permission group';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        if (count($args) < 2) {
            echo ColorHelper::error("❌ Error: Group ID and weight required") . "\n";
            echo ColorHelper::info("Usage: ./mason perm:group:set-weight <group_id> <weight>") . "\n";
            return 1;
        }

        $basePath = $app?->basePath() ?? getcwd();
        App::boot($basePath);

        $groupId = $args[0];
        $weight = (int) $args[1];

        try {
            $permissions = App::container()->make(PermissionsService::class);
            $permissions->setGroupWeight($groupId, $weight);

            echo ColorHelper::success(sprintf('✓ Weight for group "%s" set to %d', $groupId, $weight)) . "\n";
            
            return 0;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }
}

