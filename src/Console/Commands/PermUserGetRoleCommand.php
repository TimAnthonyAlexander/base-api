<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\Auth\UserProvider;
use BaseApi\App;
use BaseApi\Console\Concerns\ResolvesUserIdentifier;

class PermUserGetRoleCommand implements Command
{
    use ResolvesUserIdentifier;

    #[Override]
    public function name(): string
    {
        return 'perm:user:get-role';
    }

    #[Override]
    public function description(): string
    {
        return 'Get the role of a user';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        if ($args === []) {
            echo ColorHelper::error("❌ Error: User ID or email required") . "\n";
            echo ColorHelper::info("Usage: ./mason perm:user:get-role <user_id|email>") . "\n";
            return 1;
        }

        $basePath = $app?->basePath() ?? getcwd();
        App::boot($basePath);

        $identifier = $args[0];

        try {
            $userProvider = App::container()->make(UserProvider::class);
            $userId = $this->resolveUserId($identifier);

            if ($userId === null) {
                echo ColorHelper::error(sprintf('❌ User "%s" not found', $identifier)) . "\n";
                return 1;
            }

            $role = $userProvider->getRole($userId);

            if ($role === null) {
                echo ColorHelper::comment(sprintf('User "%s" has no role assigned (defaults to "guest")', $identifier)) . "\n";
                return 0;
            }

            echo ColorHelper::success(sprintf('User "%s" has role:', $identifier)) . "\n";
            echo "  " . ColorHelper::colorize($role, ColorHelper::CYAN) . "\n";
            
            return 0;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }
}


