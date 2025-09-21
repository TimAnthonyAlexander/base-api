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

        if (!isset($this->commands[$commandName])) {
            echo "Unknown command: {$commandName}\n\n";
            $this->showUsage();
            return 1;
        }

        $command = $this->commands[$commandName];

        try {
            return $command->execute($argv, $this);
        } catch (Exception $exception) {
            echo "Error: " . $exception->getMessage() . "\n";
            return 1;
        }
    }

    private function showUsage(): void
    {
        echo "BaseApi CLI\n\n";
        echo "Usage:\n";
        echo "  console <command> [arguments]\n\n";
        echo "Available commands:\n";

        foreach ($this->commands as $name => $command) {
            echo sprintf('  %s    %s%s', $name, $command->description(), PHP_EOL);
        }

        echo "\n";
    }
}
