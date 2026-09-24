<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\Permissions\PermissionsService;
use BaseApi\App;
use BaseApi\Console\Concerns\ResolvesUserIdentifier;

class PermTraceCommand implements Command
{
    use ResolvesUserIdentifier;

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
            $chainWithWeights = [];
            foreach ($trace['inheritanceChain'] as $groupId) {
                $groupData = $permissions->getGroup($groupId);
                $weight = $groupData ? $groupData['weight'] : 0;
                $chainWithWeights[] = sprintf('%s (w:%d)', $groupId, $weight);
            }

            echo "  " . implode(' → ', $chainWithWeights) . "\n\n";

            // Matching patterns
            echo ColorHelper::header("Matching Patterns") . "\n";
            if (empty($trace['matches'])) {
                if ($trace['implicitDeny']) {
                    echo ColorHelper::comment("  No matching patterns found (implicit deny)") . "\n";
                } else {
                    echo ColorHelper::comment("  No patterns found") . "\n";
                }
            } else {
                foreach ($trace['matches'] as $match) {
                    $icon = $match['value'] ? '✓' : '✗';
                    $color = $match['value'] ? ColorHelper::GREEN : ColorHelper::RED;
                    $isWinner = $trace['winner'] && $trace['winner']['pattern'] === $match['pattern'] && $trace['winner']['group'] === $match['group'];
                    $winnerMark = $isWinner ? ' ← CHOSEN' : '';

                    echo ColorHelper::colorize(sprintf("  %s ", $icon), $color);
                    echo sprintf(
                        "%-30s [%s] (spec:%d, weight:%d)%s",
                        $match['pattern'],
                        $match['group'],
                        $match['specificity'],
                        $match['weight'],
                        $winnerMark
                    ) . "\n";
                }
            }

            // Tie-break explanation
            if (!empty($trace['tieBreakExplanation'])) {
                echo "\n" . ColorHelper::header("Resolution") . "\n";
                echo "  " . ColorHelper::colorize($trace['tieBreakExplanation'], ColorHelper::YELLOW) . "\n";
            }

            // Show all candidates for debugging (if multiple groups)
            if (count($trace['inheritanceChain']) > 1) {
                $nonMatches = array_filter($trace['allCandidates'], fn($c): bool => !$c['matches']);
                if ($nonMatches !== []) {
                    echo "\n" . ColorHelper::header("Non-Matching Patterns (for reference)") . "\n";
                    foreach (array_slice($nonMatches, 0, 5) as $candidate) {
                        echo ColorHelper::comment(sprintf(
                            "    %-30s [%s] (spec:%d)",
                            $candidate['pattern'],
                            $candidate['group'],
                            $candidate['specificity']
                        )) . "\n";
                    }

                    if (count($nonMatches) > 5) {
                        echo ColorHelper::comment(sprintf("    ... and %d more", count($nonMatches) - 5)) . "\n";
                    }
                }
            }

            return 0;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }
}

