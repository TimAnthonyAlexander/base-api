<?php

namespace BaseApi\Console\Commands;

use BaseApi\Console\Command;

class MakeControllerCommand implements Command
{
    public function name(): string
    {
        return 'make:controller';
    }

    public function description(): string
    {
        return 'Create a new controller class';
    }

    public function execute(array $args): int
    {
        if (empty($args)) {
            echo "Error: Controller name is required\n";
            echo "Usage: console make:controller <Name>\n";
            return 1;
        }

        $name = $this->sanitizeName($args[0]);
        
        if (empty($name)) {
            echo "Error: Invalid controller name. Use only letters, numbers, and underscores.\n";
            return 1;
        }
        
        // Ensure the name ends with "Controller" (only add once)
        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $filePath = __DIR__ . "/../../Controllers/{$name}.php";
        
        if (file_exists($filePath)) {
            echo "Error: Controller {$name} already exists\n";
            return 1;
        }

        $template = $this->getControllerTemplate($name);
        
        if (!file_put_contents($filePath, $template)) {
            echo "Error: Could not create controller file\n";
            return 1;
        }

        echo "Controller created: app/Controllers/{$name}.php\n";
        echo "Remember to register routes in routes/api.php\n";
        
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

    private function getControllerTemplate(string $name): string
    {
        return <<<PHP
<?php

namespace BaseApi\Controllers;

class {$name} extends Controller
{
    public function get(): array
    {
        return [
            'message' => 'Hello from {$name}'
        ];
    }

    public function post(): array
    {
        return [
            'message' => 'POST request handled by {$name}'
        ];
    }
}
PHP;
    }
}
