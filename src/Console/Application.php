<?php

namespace BaseApi\Console;

use Exception;

class Application
{
    private array $commands = [];

    private readonly string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? getcwd();
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function register(string $name, Command $command): void
    {
        $this->commands[$name] = $command;
    }

    public function run(array $argv): int
    {
        // Remove script name
        array_shift($argv);

        if ($argv === []) {
            $this->showUsage();
            return 1;
        }

        $commandName = array_shift($argv);

        // Try fuzzy matching if command not found exactly
        if (!isset($this->commands[$commandName])) {
            $matches = $this->findMatchingCommands($commandName);

            if (count($matches) === 1) {
                $commandName = $matches[0];
                echo ColorHelper::comment('Running: ' . $commandName) . "\n\n";
            } elseif (count($matches) > 1) {
                echo ColorHelper::info(sprintf('Commands for %s:', $commandName)) . "\n";
                foreach ($matches as $match) {
                    $command = $this->commands[$match];
                    $padding = str_repeat(' ', max(0, 20 - strlen((string) $match)));
                    echo "  " . ColorHelper::colorize($match, ColorHelper::BRIGHT_CYAN) .
                        $padding . ColorHelper::comment($command->description()) . "\n";
                }

                return 1;
            } else {
                echo ColorHelper::error('Unknown command: ' . $commandName) . "\n\n";
                $this->showUsage();
                return 1;
            }
        }

        $command = $this->commands[$commandName];

        try {
            return $command->execute($argv, $this);
        } catch (Exception $exception) {
            echo ColorHelper::error("Error: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }

    private function findMatchingCommands(string $input): array
    {
        $matches = [];

        foreach (array_keys($this->commands) as $commandName) {
            // Exact match (shouldn't happen since we check this first)
            if ($commandName === $input) {
                return [$commandName];
            }

            // Starts with input
            if (str_starts_with($commandName, $input)) {
                $matches[] = $commandName;
            }
        }

        // If no starts-with matches, try contains
        if ($matches === []) {
            foreach (array_keys($this->commands) as $commandName) {
                if (str_contains($commandName, $input)) {
                    $matches[] = $commandName;
                }
            }
        }

        return $matches;
    }

    private function showUsage(): void
    {
        echo ColorHelper::header("Mason | BaseAPI CLI | by Tim Anthony Alexander") . "\n\n";

        $groupedCommands = $this->groupCommands();

        // Define representative commands to show for each namespace (max 3 per group)
        $representativeCommands = [
            'general' => ['serve'],
            'make' => ['make:controller', 'make:model', 'make:job'],
            'migrate' => ['migrate:generate', 'migrate:apply'],
            'types' => ['types:generate'],
            'i18n' => ['i18n:scan', 'i18n:add-lang', 'i18n:fill'],
            'queue' => ['queue:work'],
            'route' => ['route:list'],
            'cache' => ['cache:clear', 'cache:flush'],
            'storage' => ['storage:link'],
            'perm' => ['perm:grant', 'perm:check', 'perm:user:set-role']
        ];

        foreach ($groupedCommands as $group => $commands) {
            if ($group !== 'general') {
                $totalCommands = count($commands);
                echo ColorHelper::success(sprintf('%s (%d)', $group, $totalCommands)) . "\n";
            }

            // Show only representative commands
            $commandsToShow = $representativeCommands[$group] ?? array_keys($commands);
            $shownCount = 0;
            $hasMore = false;

            foreach ($commands as $name => $command) {
                // Only show representative commands or first 3 if not specified
                if (
                    in_array($name, $commandsToShow) ||
                    (empty($representativeCommands[$group]) && $shownCount < 3)
                ) {
                    $padding = str_repeat(' ', max(0, 20 - strlen((string) $name)));
                    echo "  " . ColorHelper::colorize($name, ColorHelper::BRIGHT_CYAN) .
                        $padding . ColorHelper::comment($command->description()) . "\n";
                    $shownCount++;
                } else {
                    $hasMore = true;
                }
            }

            // Show hint if there are more commands
            if ($hasMore && $group !== 'general') {
                $remaining = count($commands) - $shownCount;
                echo "  " . ColorHelper::colorize("...", ColorHelper::BRIGHT_BLACK) .
                    ColorHelper::comment(sprintf(' and %d more', $remaining)) . "\n";
            }

            $isFirst = false;
        }

        echo "\n" . ColorHelper::comment('Tip: Run "./mason <namespace>" to view all commands in that group.') . "\n";
    }

    private function groupCommands(): array
    {
        $grouped = [
            'general' => [],
            'make' => [],
            'migrate' => [],
            'types' => [],
            'i18n' => [],
            'queue' => [],
            'route' => [],
            'cache' => [],
            'storage' => [],
            'perm' => []
        ];

        foreach ($this->commands as $name => $command) {
            if (!str_contains($name, ':')) {
                $grouped['general'][$name] = $command;
            } else {
                $prefix = explode(':', $name)[0];
                if (isset($grouped[$prefix])) {
                    $grouped[$prefix][$name] = $command;
                } else {
                    $grouped['general'][$name] = $command;
                }
            }
        }

        // Remove empty groups and sort commands within each group
        foreach ($grouped as $group => $commands) {
            if ($commands === []) {
                unset($grouped[$group]);
            } else {
                ksort($grouped[$group]);
            }
        }

        return $grouped;
    }
}
