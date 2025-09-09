<?php

namespace BaseApi\Console;

class Application
{
    private array $commands = [];

    public function register(string $name, Command $command): void
    {
        $this->commands[$name] = $command;
    }

    public function run(array $argv): int
    {
        // Remove script name
        array_shift($argv);

        if (empty($argv)) {
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
            return $command->execute($argv);
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
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
            echo "  {$name}    {$command->description()}\n";
        }
        
        echo "\n";
    }
}
