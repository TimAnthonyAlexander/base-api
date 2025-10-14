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

class PermCheckCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'perm:check';
    }

    #[Override]
    public function description(): string
    {
        return 'Check if a user has a specific permission';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        if (count($args) < 2) {
            echo ColorHelper::error("❌ Error: User ID/email and permission node required") . "\n";
            echo ColorHelper::info("Usage: ./mason perm:check <user_id|email> <permission_node>") . "\n";
            return 1;
        }

        $basePath = $app?->basePath() ?? getcwd();
        App::boot($basePath);

        $identifier = $args[0];
        $node = $args[1];

        try {
            $userId = $this->resolveUserId($identifier);

            if ($userId === null) {
                echo ColorHelper::error(sprintf('❌ User "%s" not found', $identifier)) . "\n";
                return 1;
            }

            $permissions = App::container()->make(PermissionsService::class);
            $allowed = $permissions->check($userId, $node);

            if ($allowed) {
                echo ColorHelper::success('✓ ALLOWED') . "\n";
                echo sprintf('  User "%s" has permission "%s"', $identifier, $node) . "\n";
                return 0;
            }

            echo ColorHelper::error('✗ DENIED') . "\n";
            echo sprintf('  User "%s" does NOT have permission "%s"', $identifier, $node) . "\n";
            return 1;
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

