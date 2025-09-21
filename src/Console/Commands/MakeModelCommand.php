<?php

namespace BaseApi\Console\Commands;

use Override;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\App;

class MakeModelCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'make:model';
    }

    #[Override]
    public function description(): string
    {
        return 'Create a new model class';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        if ($args === []) {
            echo ColorHelper::error("❌ Error: Model name is required") . "\n";
            echo ColorHelper::info("📊 Usage: console make:model <Name>") . "\n";
            return 1;
        }

        $name = $this->sanitizeName($args[0]);

        if ($name === '' || $name === '0') {
            echo ColorHelper::error("❌ Error: Invalid model name. Use only letters, numbers, and underscores.") . "\n";
            return 1;
        }

        $filePath = App::basePath(sprintf('app/Models/%s.php', $name));
        $modelsDir = dirname($filePath);

        // Create Models directory if it doesn't exist
        if (!is_dir($modelsDir)) {
            mkdir($modelsDir, 0755, true);
        }

        if (file_exists($filePath)) {
            echo ColorHelper::error(sprintf('❌ Error: Model %s already exists', $name)) . "\n";
            return 1;
        }

        $template = $this->getModelTemplate($name);

        if (!file_put_contents($filePath, $template)) {
            echo ColorHelper::error("❌ Error: Could not create model file") . "\n";
            return 1;
        }

        echo ColorHelper::success(sprintf('Model created: app/Models/%s.php', $name)) . "\n";

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

use BaseApi\Models\BaseModel;

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
    
    // Optional: Define indexes (used by migrations)
    // public static array \$indexes = [
    //     'email' => 'unique',        // Creates unique index
    //     'created_at' => 'index',    // Creates regular index
    //     'status' => 'index'
    // ];
    
    // Optional: Define column overrides (used by migrations)
    // public static array \$columns = [
    //     'name' => ['type' => 'VARCHAR(120)', 'null' => false],
    //     'description' => ['type' => 'TEXT', 'null' => true],
    //     'price' => ['type' => 'DECIMAL(10,2)', 'default' => '0.00']
    // ];
    
    // Relations Examples:
    
    // belongsTo (many-to-one) - this model belongs to another
    // Example: Post belongs to User
    // public ?User \$user = null;  // Add this property for the relation
    // 
    // public function user(): BelongsTo
    // {
    //     return \$this->belongsTo(User::class);
    // }
    
    // hasMany (one-to-many) - this model has many of another
    // Example: User has many Posts
    // /** @var Post[] */
    // public array \$posts = [];  // Add this property for the relation
    //
    // public function posts(): HasMany  
    // {
    //     return \$this->hasMany(Post::class);
    // }
    
    // Usage examples:
    // \$model = {$name}::find('some-id');
    // \$relatedModel = \$model->user()->get();  // Get related model
    // \$relatedModels = \$model->posts()->get(); // Get array of related models
    // 
    // Eager loading:
    // \$modelsWithRelations = {$name}::with(['user', 'posts'])->get();
}
PHP;
    }
}
