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

class PermTraceCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'perm:trace';
    }

    #[Override]
    public function description(): string
    {
        return 'Trace permission resolution for debugging';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        if (count($args) < 2) {
            echo ColorHelper::error("❌ Error: User ID/email and permission node required") . "\n";
            echo ColorHelper::info("Usage: ./mason perm:trace <user_id|email> <permission_node>") . "\n";
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
            $trace = $permissions->trace($userId, $node);

            echo ColorHelper::header("🔍 Permission Trace") . "\n";
            echo str_repeat('─', 80) . "\n\n";

            echo ColorHelper::info("User ID: ") . ColorHelper::colorize($trace['userId'], ColorHelper::CYAN) . "\n";
            echo ColorHelper::info("Role: ") . ColorHelper::colorize($trace['role'], ColorHelper::CYAN) . "\n";
            echo ColorHelper::info("Node: ") . ColorHelper::colorize($trace['node'], ColorHelper::YELLOW) . "\n";
            
            $resultColor = $trace['result'] === 'allow' ? ColorHelper::GREEN : ColorHelper::RED;
            echo ColorHelper::info("Result: ") . ColorHelper::colorize(strtoupper((string) $trace['result']), $resultColor) . "\n\n";

            // Inheritance chain
            echo ColorHelper::header("Inheritance Chain") . "\n";
            echo "  " . implode(' → ', $trace['inheritanceChain']) . "\n\n";

            // Matching patterns
            echo ColorHelper::header("Matching Patterns") . "\n";
            if (empty($trace['matches'])) {
                echo ColorHelper::comment("  No matching patterns found (implicit deny)") . "\n";
            } else {
                foreach ($trace['matches'] as $match) {
                    $icon = $match['value'] ? '✓' : '✗';
                    $color = $match['value'] ? ColorHelper::GREEN : ColorHelper::RED;
                    $specificity = $match['specificity'];
                    
                    echo ColorHelper::colorize(sprintf("  %s ", $icon), $color);
                    echo sprintf("%-40s (specificity: %d)", $match['pattern'], $specificity) . "\n";
                }
            }

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

