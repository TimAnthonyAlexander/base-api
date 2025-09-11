<?php

namespace BaseApi\Console\Commands;

use BaseApi\Console\Command;
use BaseApi\Database\Migrations\ModelScanner;
use BaseApi\Database\Migrations\DatabaseIntrospector;
use BaseApi\Database\Migrations\DiffEngine;
use BaseApi\Database\Migrations\MigrationsFile;
use BaseApi\App;

class MigrateGenerateCommand implements Command
{
    public function name(): string
    {
        return 'migrate:generate';
    }

    public function description(): string
    {
        return 'Generate migration plan from model changes';
    }

    public function execute(array $args, ?\BaseApi\Console\Application $app = null): int
    {
        try {
            echo "Scanning models...\n";
            
            // Scan models
            $scanner = new ModelScanner();
            $modelSchema = $scanner->scan(__DIR__ . '/../../Models');
            
            echo "Introspecting database...\n";
            
            // Introspect database
            $introspector = new DatabaseIntrospector();
            $dbSchema = $introspector->snapshot();
            
            echo "Generating migration plan...\n";
            
            // Generate diff
            $diffEngine = new DiffEngine();
            $plan = $diffEngine->diff($modelSchema, $dbSchema);
            
            // Get migrations file path
            $migrationsFile = App::config()->get('MIGRATIONS_FILE', 'storage/migrations.json');
            $fullPath = __DIR__ . '/../../../' . $migrationsFile;
            
            // Write plan to file
            MigrationsFile::write($fullPath, $plan->toArray());
            
            // Print summary
            $this->printSummary($plan);
            
            if ($plan->isEmpty()) {
                echo "\nNo changes detected. Database is up to date.\n";
            } else {
                echo "\nMigration plan written to: {$migrationsFile}\n";
                echo "Review the plan and run 'migrate:apply' to execute changes.\n";
            }
            
            return 0;
            
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return 1;
        }
    }

    private function printSummary($plan): void
    {
        $operations = $plan->operations;
        
        if (empty($operations)) {
            return;
        }
        
        $counts = [
            'create_table' => 0,
            'add_column' => 0,
            'modify_column' => 0,
            'add_index' => 0,
            'add_fk' => 0,
            'drop_table' => 0,
            'drop_column' => 0,
            'drop_index' => 0,
            'drop_fk' => 0,
        ];
        
        $destructiveCount = 0;
        
        foreach ($operations as $op) {
            $opType = $op['op'];
            if (isset($counts[$opType])) {
                $counts[$opType]++;
            }
            
            if ($op['destructive'] ?? false) {
                $destructiveCount++;
            }
        }
        
        echo "\nMigration Plan Summary:\n";
        echo "======================\n";
        
        if ($counts['create_table'] > 0) {
            echo "Create tables: {$counts['create_table']}\n";
        }
        
        if ($counts['add_column'] > 0) {
            echo "Add columns: {$counts['add_column']}\n";
        }
        
        if ($counts['modify_column'] > 0) {
            echo "Modify columns: {$counts['modify_column']}\n";
        }
        
        if ($counts['add_index'] > 0) {
            echo "Add indexes: {$counts['add_index']}\n";
        }
        
        if ($counts['add_fk'] > 0) {
            echo "Add foreign keys: {$counts['add_fk']}\n";
        }
        
        if ($counts['drop_table'] > 0) {
            echo "Drop tables: {$counts['drop_table']} [DESTRUCTIVE]\n";
        }
        
        if ($counts['drop_column'] > 0) {
            echo "Drop columns: {$counts['drop_column']} [DESTRUCTIVE]\n";
        }
        
        if ($counts['drop_index'] > 0) {
            echo "Drop indexes: {$counts['drop_index']}\n";
        }
        
        if ($counts['drop_fk'] > 0) {
            echo "Drop foreign keys: {$counts['drop_fk']}\n";
        }
        
        if ($destructiveCount > 0) {
            echo "\nWARNING: {$destructiveCount} destructive operations detected!\n";
            echo "Use 'migrate:apply --safe' to skip destructive changes.\n";
        }
    }
}
