<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\Auth\UserProvider;
use BaseApi\Permissions\PermissionsService;
use BaseApi\App;

class PermUserSetRoleCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'perm:user:set-role';
    }

    #[Override]
    public function description(): string
    {
        return 'Set the role of a user';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        if (count($args) < 2) {
            echo ColorHelper::error("❌ Error: User ID/email and role required") . "\n";
            echo ColorHelper::info("Usage: ./mason perm:user:set-role <user_id|email> <role>") . "\n";
            return 1;
        }

        $basePath = $app?->basePath() ?? getcwd();
        App::boot($basePath);

        $identifier = $args[0];
        $role = $args[1];

        try {
            // Verify role exists
            $permissions = App::container()->make(PermissionsService::class);
            if (!$permissions->groupExists($role)) {
                echo ColorHelper::error(sprintf('❌ Role "%s" does not exist', $role)) . "\n";
                echo ColorHelper::info("Use 'perm:group:list' to see available roles") . "\n";
                return 1;
            }

            $userId = $this->resolveUserId($identifier);

            if ($userId === null) {
                echo ColorHelper::error(sprintf('❌ User "%s" not found', $identifier)) . "\n";
                return 1;
            }

            $userProvider = App::container()->make(UserProvider::class);
            $success = $userProvider->setRole($userId, $role);

            if (!$success) {
                echo ColorHelper::error("❌ Failed to set user role") . "\n";
                return 1;
            }

            echo ColorHelper::success(sprintf('✓ User "%s" role set to "%s"', $identifier, $role)) . "\n";
            
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


