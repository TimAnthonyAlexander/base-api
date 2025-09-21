<?php

namespace BaseApi\Console\Commands;

use Override;
use Exception;
use BaseApi\Console\Command;
use BaseApi\Console\Application;
use BaseApi\Console\ColorHelper;
use BaseApi\App;

class JobMakeCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'make:job';
    }

    #[Override]
    public function description(): string
    {
        return 'Create a new job class';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        if ($args === []) {
            echo ColorHelper::error("❌ Error: Job name is required") . "\n";
            echo ColorHelper::info("📊 Usage: console make:job JobName") . "\n";
            return 1;
        }
        
        $basePath = $app?->basePath() ?? getcwd();
        App::boot($basePath);
        
        $name = $args[0];
        
        // Validate job name
        if (!preg_match('/^[A-Z][a-zA-Z0-9]*Job$/', (string) $name)) {
            echo ColorHelper::error("❌ Error: Job name must start with uppercase letter and end with 'Job'") . "\n";
            echo ColorHelper::comment("Example: SendEmailJob, ProcessImageJob") . "\n";
            return 1;
        }
        
        try {
            $this->generateJobClass($basePath, $name);
            echo ColorHelper::success(sprintf('✅ Job %s created successfully at app/Jobs/%s.php', $name, $name)) . "\n";
            return 0;
        } catch (Exception $exception) {
            echo ColorHelper::error("❌ Error creating job: " . $exception->getMessage()) . "\n";
            return 1;
        }
    }
    
    private function generateJobClass(string $basePath, string $name): void
    {
        $jobsDir = $basePath . '/app/Jobs';
        
        // Create Jobs directory if it doesn't exist
        if (!is_dir($jobsDir) && !mkdir($jobsDir, 0755, true)) {
            throw new Exception("Could not create Jobs directory");
        }
        
        $filePath = $jobsDir . '/' . $name . '.php';
        
        if (file_exists($filePath)) {
            throw new Exception(sprintf('Job class %s already exists', $name));
        }
        
        $template = $this->getJobTemplate($name);
        
        if (file_put_contents($filePath, $template) === false) {
            throw new Exception("Could not write job file");
        }
    }
    
    private function getJobTemplate(string $name): string
    {
        return <<<PHP
<?php

namespace App\Jobs;

use BaseApi\Queue\Job;

class {$name} extends Job
{
    protected int \$maxRetries = 3;
    protected int \$retryDelay = 30; // seconds
    
    public function __construct(
        // Add your job parameters here
    ) {
        // Initialize job data
    }
    
    public function handle(): void
    {
        // Implement your job logic here
        // This method will be called when the job is processed
        
        // Example:
        // \$this->processData();
        // \$this->sendNotification();
    }
    
    public function failed(\Throwable \$exception): void
    {
        // Handle job failure (optional)
        // This method is called when the job fails permanently
        
        parent::failed(\$exception);
        
        // Add custom failure handling:
        // - Send notification to administrators
        // - Log to external service
        // - Clean up resources
    }
}
PHP;
    }
}
