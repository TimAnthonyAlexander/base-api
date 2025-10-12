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
                echo ColorHelper::error(sprintf('Command "%s" is ambiguous.', $commandName)) . "\n";
                echo ColorHelper::info("Did you mean one of these?") . "\n";
                foreach ($matches as $match) {
                    echo ColorHelper::colorize('  ' . $match, ColorHelper::YELLOW) . "\n";
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
        echo ColorHelper::header("Mason | BaseAPI Cli | by Tim Anthony Alexander") . "\n\n";
        echo ColorHelper::info("Usage:") . "\n";
        echo "  " . ColorHelper::colorize("./mason", ColorHelper::BRIGHT_WHITE) . " " .
            ColorHelper::colorize("<command>", ColorHelper::YELLOW) . " " .
            ColorHelper::colorize("[arguments]", ColorHelper::BRIGHT_BLACK) . "\n\n";

        $groupedCommands = $this->groupCommands();

        foreach ($groupedCommands as $group => $commands) {
            if ($group === 'general') {
                echo ColorHelper::success("Available commands:") . "\n";
            } else {
                echo ColorHelper::success($group) . "\n";
            }

            foreach ($commands as $name => $command) {
                $padding = str_repeat(' ', max(0, 20 - strlen((string) $name)));
                echo "  " . ColorHelper::colorize($name, ColorHelper::BRIGHT_CYAN) .
                    $padding . ColorHelper::comment($command->description()) . "\n";
            }

            echo "\n";
        }
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
