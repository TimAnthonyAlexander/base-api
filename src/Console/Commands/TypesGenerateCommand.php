<?php

namespace BaseApi\Console\Commands;

use Override;
use BaseApi\Console\Application;
use Exception;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\OpenApi\Builders\IRBuilder;
use BaseApi\OpenApi\Emitters\OpenAPIEmitter;
use BaseApi\OpenApi\Emitters\TypeScriptTypesEmitter;
use BaseApi\OpenApi\Emitters\RoutesEmitter;
use BaseApi\OpenApi\Emitters\ClientEmitter;

class TypesGenerateCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'types:generate';
    }

    #[Override]
    public function description(): string
    {
        return 'Generate OpenAPI spec and TypeScript definitions from controllers and routes';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        $options = $this->parseArgs($args);

        if (isset($options['help'])) {
            $this->showHelp();
            return 0;
        }

        echo ColorHelper::header("🔧 Generating types from BaseApi controllers and routes...") . "\n";

        try {
            // Step 1: Build IR from routes and controllers
            echo ColorHelper::info("📖 Analyzing routes and controllers...") . "\n";
            $builder = new IRBuilder();
            $ir = $builder->build();
            
            // Step 2: Generate OpenAPI if requested
            if (isset($options['out-openapi'])) {
                echo ColorHelper::info("🌐 Generating OpenAPI spec...") . "\n";
                $emitter = new OpenAPIEmitter();
                $spec = $emitter->emit($ir);
                $this->writeFile($options['out-openapi'], json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                echo ColorHelper::success('   📄 OpenAPI spec written to ' . $options['out-openapi']) . "\n";
            }

            // Step 3: Generate TypeScript types
            if (isset($options['out-ts'])) {
                echo ColorHelper::info("🔷 Generating TypeScript types...") . "\n";
                $emitter = new TypeScriptTypesEmitter();
                $types = $emitter->emit($ir);
                $this->writeFile($options['out-ts'], $types);
                echo ColorHelper::success('   📘 TypeScript types written to ' . $options['out-ts']) . "\n";
            }

            // Step 4: Generate routes
            if (isset($options['out-routes'])) {
                echo ColorHelper::info("🛣️  Generating route constants...") . "\n";
                $emitter = new RoutesEmitter();
                $routes = $emitter->emit($ir);
                $this->writeFile($options['out-routes'], $routes);
                echo ColorHelper::success('   📘 Route constants written to ' . $options['out-routes']) . "\n";
            }

            // Step 5: Generate HTTP client
            if (isset($options['out-http'])) {
                echo ColorHelper::info("🌐 Generating HTTP client...") . "\n";
                $emitter = new ClientEmitter();
                $http = $emitter->emitHttp($ir);
                $this->writeFile($options['out-http'], $http);
                echo ColorHelper::success('   📘 HTTP client written to ' . $options['out-http']) . "\n";
            }

            // Step 6: Generate API client
            if (isset($options['out-client'])) {
                echo ColorHelper::info("📡 Generating API client functions...") . "\n";
                $emitter = new ClientEmitter();
                $client = $emitter->emitClient($ir);
                $this->writeFile($options['out-client'], $client);
                echo ColorHelper::success('   📘 API client written to ' . $options['out-client']) . "\n";
            }

            // Step 7: Generate React hooks
            if (isset($options['out-hooks'])) {
                echo ColorHelper::info("⚛️  Generating React hooks...") . "\n";
                $emitter = new ClientEmitter();
                $hooks = $emitter->emitHooks($ir);
                $this->writeFile($options['out-hooks'], $hooks);
                echo ColorHelper::success('   📘 React hooks written to ' . $options['out-hooks']) . "\n";
            }

            echo ColorHelper::success("✨ Type generation completed!") . "\n";
            
            // Print summary table
            echo "\n" . ColorHelper::header("Generated Files:") . "\n";
            $this->printFileSummary($options);
            
            return 0;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error: " . $exception->getMessage()) . "\n";
            echo ColorHelper::error('   ' . $exception->getTraceAsString()) . "\n";
            return 1;
        }
    }

    private function parseArgs(array $args): array
    {
        $options = [];
        $bundleDir = null;

        foreach ($args as $arg) {
            if ($arg === '--help' || $arg === '-h') {
                $options['help'] = true;
            } elseif (str_starts_with((string) $arg, '--bundle-dir=')) {
                $bundleDir = rtrim(substr((string) $arg, 13), '/') . '/';
            } elseif (str_starts_with((string) $arg, '--out-ts=')) {
                $options['out-ts'] = substr((string) $arg, 9);
            } elseif (str_starts_with((string) $arg, '--out-openapi=')) {
                $options['out-openapi'] = substr((string) $arg, 14);
            } elseif (str_starts_with((string) $arg, '--out-routes=')) {
                $options['out-routes'] = substr((string) $arg, 13);
            } elseif (str_starts_with((string) $arg, '--out-http=')) {
                $options['out-http'] = substr((string) $arg, 11);
            } elseif (str_starts_with((string) $arg, '--out-client=')) {
                $options['out-client'] = substr((string) $arg, 13);
            } elseif (str_starts_with((string) $arg, '--out-hooks=')) {
                $options['out-hooks'] = substr((string) $arg, 12);
            } elseif ($arg === '--all') {
                $options['generate-all'] = true;
            }
        }

        // Handle --bundle-dir: co-locate all outputs
        if ($bundleDir) {
            $options['out-ts'] = $bundleDir . 'types.ts';
            $options['out-openapi'] = $bundleDir . 'openapi.json';
            $options['out-routes'] = $bundleDir . 'routes.ts';
            $options['out-http'] = $bundleDir . 'http.ts';
            $options['out-client'] = $bundleDir . 'client.ts';
            $options['out-hooks'] = $bundleDir . 'hooks.ts';
        }
        // Handle --all: generate all with default names
        elseif (isset($options['generate-all'])) {
            $options['out-ts'] = 'types.ts';
            $options['out-openapi'] = 'openapi.json';
            $options['out-routes'] = 'routes.ts';
            $options['out-http'] = 'http.ts';
            $options['out-client'] = 'client.ts';
            $options['out-hooks'] = 'hooks.ts';
        }
        // Default: generate at least types and openapi
        elseif (!isset($options['out-ts']) && !isset($options['out-openapi']) && 
            !isset($options['out-routes']) && !isset($options['out-http']) && 
            !isset($options['out-client']) && !isset($options['out-hooks'])) {
            $options['out-ts'] = 'types.ts';
            $options['out-openapi'] = 'openapi.json';
        }

        return $options;
    }

    private function showHelp(): void
    {
        echo <<<HELP
Generate OpenAPI spec and TypeScript definitions

Usage:
  ./mason types:generate [options]

Options:
  --bundle-dir=DIR       Output directory for all generated files (recommended)
  --out-ts=PATH          Output path for TypeScript type definitions
  --out-openapi=PATH     Output path for OpenAPI specification
  --out-routes=PATH      Output path for route constants and path builder
  --out-http=PATH        Output path for HTTP client
  --out-client=PATH      Output path for API client functions
  --out-hooks=PATH       Output path for React hooks
  --all                  Generate all outputs with default names in current directory
  --help, -h             Show this help message

Examples:
  # Generate types and OpenAPI (default)
  ./mason types:generate
  
  # Generate all outputs in a directory (recommended for SDK)
  ./mason types:generate --bundle-dir=web/src/api
  
  # Generate all outputs in current directory
  ./mason types:generate --all
  
  # Generate specific outputs
  ./mason types:generate --out-ts=types.ts --out-client=client.ts
  
  # Custom paths (imports may break if not co-located)
  ./mason types:generate --out-ts=sdk/types.ts --out-routes=sdk/routes.ts \\
    --out-http=sdk/http.ts --out-client=sdk/client.ts --out-hooks=sdk/hooks.ts

Note: Use --bundle-dir to ensure all files are co-located and imports work correctly.

HELP;
    }

    private function writeFile(string $path, string $content): void
    {
        $directory = dirname($path);
        
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new Exception('Failed to create directory: ' . $directory);
        }

        if (file_put_contents($path, $content) === false) {
            throw new Exception('Failed to write file to ' . $path);
        }
    }
    
    private function printFileSummary(array $options): void
    {
        $files = [
            'Types' => $options['out-ts'] ?? null,
            'Routes' => $options['out-routes'] ?? null,
            'HTTP' => $options['out-http'] ?? null,
            'Client' => $options['out-client'] ?? null,
            'Hooks' => $options['out-hooks'] ?? null,
            'OpenAPI' => $options['out-openapi'] ?? null,
        ];
        
        foreach ($files as $label => $path) {
            if ($path) {
                $absolutePath = realpath($path) ?: $path;
                echo sprintf("  %-10s %s\n", $label . ':', ColorHelper::success($absolutePath));
            }
        }
    }
}