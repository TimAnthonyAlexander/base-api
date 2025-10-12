<?php

namespace BaseApi\Console\Commands;

use Override;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;

class TestCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'test';
    }

    #[Override]
    public function description(): string
    {
        return 'Run tests with a beautiful TUI using paratest';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        $basePath = $app?->basePath() ?? getcwd();
        
        // Parse options
        $options = $this->parseOptions($args);
        $filter = $options['filter'] ?? null;
        $testsuite = $options['testsuite'] ?? null;
        $parallel = $options['parallel'] ?? $options['p'] ?? 4;
        $coverage = isset($options['coverage']);
        $verbose = isset($options['verbose']) || isset($options['v']);
        
        // Check if paratest is available
        $paratestPath = $this->findParatest($basePath);
        
        if (!$paratestPath) {
            echo ColorHelper::error("❌ Error: paratest not found.") . "\n";
            echo ColorHelper::info("Please install it with: composer require --dev brianium/paratest") . "\n";
            return 1;
        }
        
        // Print header
        $this->printHeader();
        
        // Build paratest command
        $command = $this->buildCommand($paratestPath, $basePath, $filter, $testsuite, $parallel, $coverage, $verbose);
        
        echo ColorHelper::info("🚀 Running tests with " . $parallel . " parallel processes...") . "\n\n";
        
        // Execute tests
        $exitCode = $this->executeWithTUI($command, $basePath);
        
        // Print footer
        $this->printFooter($exitCode);
        
        return $exitCode;
    }
    
    private function parseOptions(array $args): array
    {
        $options = [];
        $counter = count($args);
        
        for ($i = 0; $i < $counter; $i++) {
            $arg = $args[$i];
            
            if (str_starts_with((string) $arg, '--')) {
                $option = substr((string) $arg, 2);
                
                if (str_contains($option, '=')) {
                    [$key, $value] = explode('=', $option, 2);
                    $options[$key] = $value;
                } elseif (isset($args[$i + 1]) && !str_starts_with((string) $args[$i + 1], '--')) {
                    $options[$option] = $args[$i + 1];
                    $i++;
                } else {
                    $options[$option] = true;
                }
            } elseif (str_starts_with((string) $arg, '-')) {
                $option = substr((string) $arg, 1);
                $options[$option] = true;
            }
        }
        
        return $options;
    }
    
    private function findParatest(string $basePath): ?string
    {
        $paths = [
            $basePath . '/vendor/bin/paratest',
            $basePath . '/../vendor/bin/paratest',
            $basePath . '/../../vendor/bin/paratest',
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    private function buildCommand(
        string $paratestPath,
        string $basePath,
        ?string $filter,
        ?string $testsuite,
        int $parallel,
        bool $coverage,
        bool $verbose
    ): string {
        $phpunitConfig = $basePath . '/phpunit.xml';
        if (!file_exists($phpunitConfig)) {
            $phpunitConfig = $basePath . '/phpunit.xml.dist';
        }
        
        $parts = [
            escapeshellarg($paratestPath),
            '-p' . $parallel,
            '--colors=always',
        ];
        
        if (file_exists($phpunitConfig)) {
            $parts[] = '-c ' . escapeshellarg($phpunitConfig);
        }
        
        if ($filter) {
            $parts[] = '--filter=' . escapeshellarg($filter);
        }
        
        if ($testsuite) {
            $parts[] = '--testsuite=' . escapeshellarg($testsuite);
        }
        
        if ($coverage) {
            $parts[] = '--coverage-html=coverage';
        }
        
        if ($verbose) {
            $parts[] = '-v';
        }
        
        return implode(' ', $parts);
    }
    
    private function executeWithTUI(string $command, string $basePath): int
    {
        // Change to base path for execution
        $originalDir = getcwd();
        chdir($basePath);
        
        // Create process descriptors for real-time output
        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];
        
        $process = proc_open($command, $descriptors, $pipes);
        
        if (!is_resource($process)) {
            chdir($originalDir);
            echo ColorHelper::error("❌ Failed to start test process") . "\n";
            return 1;
        }
        
        // Close stdin
        fclose($pipes[0]);
        
        // Set streams to non-blocking
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        
        // Read output with TUI enhancements
        $output = '';
        $errorOutput = '';
        
        while (true) {
            $stdout = fgets($pipes[1]);
            $stderr = fgets($pipes[2]);

            if ($stdout !== false) {
                $output .= $stdout;
                echo $this->enhanceLine($stdout);
                flush();
            }

            if ($stderr !== false) {
                $errorOutput .= $stderr;
                echo $this->enhanceLine($stderr, true);
                flush();
            }

            // Check if process has finished
            $status = proc_get_status($process);
            if (!$status['running']) {
                // Read any remaining output
                while ($stdout = fgets($pipes[1])) {
                    echo $this->enhanceLine($stdout);
                }

                while ($stderr = fgets($pipes[2])) {
                    echo $this->enhanceLine($stderr, true);
                }

                break;
            }

            usleep(10000); // 10ms
        }
        
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $exitCode = proc_close($process);
        
        chdir($originalDir);
        
        return $exitCode;
    }
    
    private function enhanceLine(string $line, bool $isError = false): string
    {
        // Enhance test output with colors and symbols
        if (preg_match('/^OK \((\d+) tests?, (\d+) assertions?\)/', $line, $matches)) {
            return ColorHelper::success("✓ " . $line);
        }
        
        if (preg_match('/^Tests: (\d+), Assertions: (\d+)/', $line)) {
            return ColorHelper::info("📊 " . $line);
        }
        
        if (preg_match('/FAILURES!/', $line)) {
            return ColorHelper::error("✗ " . $line);
        }
        
        if (preg_match('/^Time: (.+), Memory: (.+)/', $line)) {
            return ColorHelper::comment("⏱️  " . $line);
        }
        
        if (str_contains($line, 'OK (')) {
            return ColorHelper::success($line);
        }
        
        if (str_contains($line, 'FAILURES') || str_contains($line, 'ERRORS')) {
            return ColorHelper::error($line);
        }
        
        if (str_contains($line, 'WARNINGS')) {
            return ColorHelper::warning($line);
        }
        
        if (preg_match('/^\d+\)/', $line)) {
            return ColorHelper::error($line);
        }
        
        if ($isError) {
            return ColorHelper::error($line);
        }
        
        return $line;
    }
    
    private function printHeader(): void
    {
        echo "\n";
        echo ColorHelper::header("╔════════════════════════════════════════════════════════════════════════════╗") . "\n";
        echo ColorHelper::header("║                          🧪 BaseAPI Test Suite                            ║") . "\n";
        echo ColorHelper::header("╚════════════════════════════════════════════════════════════════════════════╝") . "\n";
        echo "\n";
    }
    
    private function printFooter(int $exitCode): void
    {
        echo "\n";
        echo str_repeat('─', 80) . "\n";
        
        if ($exitCode === 0) {
            echo ColorHelper::success("✨ All tests passed! Great work!") . "\n";
        } else {
            echo ColorHelper::error("❌ Some tests failed. Review the output above for details.") . "\n";
        }
        
        echo "\n";
    }
}

