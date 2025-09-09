<?php

namespace BaseApi\Console\Commands;

use BaseApi\Console\Command;

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

    public function execute(array $args): int
    {
        if (empty($args)) {
            echo "Error: Model name is required\n";
            echo "Usage: console make:model <Name>\n";
            return 1;
        }

        $name = $args[0];
        $modelsDir = __DIR__ . '/../../Models';
        $filePath = "{$modelsDir}/{$name}.php";
        
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
        echo "Note: DB & migrator functionality will be added in a later milestone\n";
        
        return 0;
    }

    private function getModelTemplate(string $name): string
    {
        return <<<PHP
<?php

namespace BaseApi\Models;

/**
 * {$name} Model
 * 
 * Note: DB & migrator functionality will be added in a later milestone.
 * For now, this is a simple class with typed properties.
 */
class {$name}
{
    public string \$id;
    public string \$created_at;
    public string \$updated_at;
    
    // Add your model properties here
    // Example:
    // public string \$name;
    // public ?string \$email = null;
}
PHP;
    }
}
