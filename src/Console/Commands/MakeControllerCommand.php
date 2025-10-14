<?php

namespace BaseApi\Console\Commands;

use Override;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\App;

class MakeControllerCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'make:controller';
    }

    #[Override]
    public function description(): string
    {
        return 'Create a new controller class';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        if ($args === []) {
            echo ColorHelper::error("❌ Error: Controller name is required") . "\n";
            echo ColorHelper::info("📊 Usage: console make:controller <Name>") . "\n";
            return 1;
        }

        $name = $this->sanitizeName($args[0]);

        if ($name === '' || $name === '0') {
            echo ColorHelper::error("❌ Error: Invalid controller name. Use only letters, numbers, and underscores.") . "\n";
            return 1;
        }

        // Ensure the name ends with "Controller" (only add once)
        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $filePath = App::basePath(sprintf('app/Controllers/%s.php', $name));

        // Create Controllers directory if it doesn't exist
        $controllersDir = dirname($filePath);
        if (!is_dir($controllersDir)) {
            mkdir($controllersDir, 0755, true);
        }

        if (file_exists($filePath)) {
            echo ColorHelper::error(sprintf('❌ Error: Controller %s already exists', $name)) . "\n";
            return 1;
        }

        $template = $this->getControllerTemplate($name);

        if (!file_put_contents($filePath, $template)) {
            echo ColorHelper::error("❌ Error: Could not create controller file") . "\n";
            return 1;
        }

        echo ColorHelper::success(sprintf('Controller created: app/Controllers/%s.php', $name)) . "\n";
        echo ColorHelper::info("📊 Remember to register routes in routes/api.php") . "\n";

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

namespace App\Controllers;

use BaseApi\Controllers\Controller;
use BaseApi\Http\JsonResponse;

/**
 * {$name}
 * 
 * Add your controller description here.
 */
class {$name} extends Controller
{
    // Define public properties to auto-populate from request data
    // Example:
    // public string \$name = '';
    // public ?string \$email = null;
    // public string \$id = '';
    
    public function get(): JsonResponse
    {
        // Example GET handler
        return JsonResponse::ok([
            'message' => 'Hello from {$name}',
            'timestamp' => date('c')
        ]);
    }

    public function post(): JsonResponse
    {
        // Example POST handler with validation
        // \$this->validate([
        //     'name' => 'required|string|max:255',
        //     'email' => 'required|email'
        // ]);
        
        // Process the request...
        
        return JsonResponse::created([
            'message' => 'POST request handled by {$name}',
            'timestamp' => date('c')
        ]);
    }
}
PHP;
    }
}
