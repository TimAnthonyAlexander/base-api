<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\Permissions\PermissionsService;
use BaseApi\App;

class PermValidateCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'perm:validate';
    }

    #[Override]
    public function description(): string
    {
        return 'Validate permissions configuration';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        $basePath = $app?->basePath() ?? getcwd();
        App::boot($basePath);

        try {
            $permissions = App::container()->make(PermissionsService::class);
            
            echo ColorHelper::header("🔍 Validating permissions configuration...") . "\n";
            echo str_repeat('─', 80) . "\n\n";

            $errors = $permissions->validate();

            if (empty($errors)) {
                echo ColorHelper::success("✓ All validations passed!") . "\n";
                echo ColorHelper::comment("  No errors found in permissions configuration") . "\n";
                return 0;
            }

            echo ColorHelper::error(sprintf("❌ Found %d error(s):", count($errors))) . "\n\n";
            foreach ($errors as $error) {
                echo ColorHelper::error("  • " . $error) . "\n";
            }

            return 1;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }
}


