<?php

namespace BaseApi\Console\Commands;

use BaseApi\Console\Command;
use BaseApi\App;

class MakeModelCommand implements Command
{
    public function name(): string
    {
        return 'make:model';
    }

    public function description(): string
    {
        return 'Create a new model class';
    }

    public function execute(array $args, ?\BaseApi\Console\Application $app = null): int
    {
        if (empty($args)) {
            echo "Error: Model name is required\n";
            echo "Usage: console make:model <Name>\n";
            return 1;
        }

        $name = $this->sanitizeName($args[0]);
        
        if (empty($name)) {
            echo "Error: Invalid model name. Use only letters, numbers, and underscores.\n";
            return 1;
        }
        
        $filePath = App::basePath("app/Models/{$name}.php");
        $modelsDir = dirname($filePath);
        
        // Create Models directory if it doesn't exist
        if (!is_dir($modelsDir)) {
            mkdir($modelsDir, 0755, true);
        }
        
        if (file_exists($filePath)) {
            echo "Error: Model {$name} already exists\n";
            return 1;
        }

        $template = $this->getModelTemplate($name);
        
        if (!file_put_contents($filePath, $template)) {
            echo "Error: Could not create model file\n";
            return 1;
        }

        echo "Model created: app/Models/{$name}.php\n";
        
        return 0;
    }

    private function sanitizeName(string $name): string
    {
        // Remove any characters that aren't letters, numbers, or underscores
        $sanitized = preg_replace('/[^A-Za-z0-9_]/', '', $name);
        
        // Ensure it starts with a letter
        if (!empty($sanitized) && !preg_match('/^[A-Za-z]/', $sanitized)) {
            return '';
        }
        
        return $sanitized;
    }

    private function getModelTemplate(string $name): string
    {
        return <<<PHP
<?php

namespace App\Models;

/**
 * {$name} Model
 */
class {$name} extends BaseModel
{
    // Add your model properties here
    // Example:
    // public string \$name = '';
    // public ?string \$email = null;
    // public bool \$active = true;
    
    // Optional: Define custom table name
    // protected static ?string \$table = '{$name}_table';
    
    // Optional: Define indexes
    // public static array \$indexes = [
    //     'email' => 'unique',
    //     'created_at' => 'index'
    // ];
    
    // Optional: Define column overrides
    // public static array \$columns = [
    //     'name' => ['type' => 'VARCHAR(120)', 'null' => false],
    //     'description' => ['type' => 'TEXT']
    // ];
}
PHP;
    }
}
