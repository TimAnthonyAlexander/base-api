<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\Auth\UserProvider;
use BaseApi\App;

class PermUserGetRoleCommand implements Command
{
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

    private function resolveUserId(string $identifier): ?string
    {
        // Try direct lookup by ID
        $userProvider = App::container()->make(UserProvider::class);
        $user = $userProvider->byId($identifier);
        
        if ($user !== null) {
            return $identifier;
        }

        // Try lookup by email (if it looks like an email)
        if (str_contains($identifier, '@')) {
            try {
                $db = App::db();
                $result = $db->raw("SELECT id FROM users WHERE email = ?", [$identifier]);
                
                if ($result !== []) {
                    return $result[0]['id'];
                }
            } catch (Exception) {
                // Ignore DB errors
            }
        }

        return null;
    }
}

