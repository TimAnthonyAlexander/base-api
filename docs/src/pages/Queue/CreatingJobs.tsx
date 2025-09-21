import { Box, Typography, Alert, List, ListItem, ListItemText } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

export default function CreatingJobs() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Creating Jobs
            </Typography>

            <Typography variant="h5" color="text.secondary" paragraph>
                Learn how to create, configure, and dispatch background jobs
            </Typography>

            <Typography>
                Jobs are the core of the queue system. They define the work to be performed asynchronously 
                in the background. BaseAPI provides a simple job class structure with built-in retry logic, 
                failure handling, and configuration options.
            </Typography>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Generating Job Classes
            </Typography>

            <Typography>
                Use the <code>make:job</code> command to generate a new job class:
            </Typography>

            <CodeBlock language="bash" code={`# Generate a job class
./bin/console make:job SendWelcomeEmailJob

# Jobs are created in app/Jobs/ directory
ls app/Jobs/
# SendWelcomeEmailJob.php`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Basic Job Structure
            </Typography>

            <Typography>
                All jobs extend the base <code>Job</code> class and implement the <code>handle()</code> method:
            </Typography>

            <CodeBlock language="php" code={`<?php

namespace App\\Jobs;

use BaseApi\\Queue\\Job;

class SendWelcomeEmailJob extends Job
{
    protected int $maxRetries = 3;
    protected int $retryDelay = 30; // seconds
    
    public function __construct(
        private string $userEmail,
        private string $userName
    ) {}
    
    public function handle(): void
    {
        // Your job logic goes here
        $emailService = new EmailService();
        $emailService->send(
            to: $this->userEmail,
            subject: 'Welcome!',
            body: "Hello {$this->userName}, welcome to our application!"
        );
    }
    
    public function failed(\\Throwable $exception): void
    {
        // Handle job failure
        error_log("Failed to send welcome email to {$this->userEmail}: " . $exception->getMessage());
        
        // Optional: Send notification to admin, log to monitoring service, etc.
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Job Configuration Options
            </Typography>

            <Typography>
                Configure job behavior by setting protected properties:
            </Typography>

            <CodeBlock language="php" code={`<?php

class ProcessImageJob extends Job
{
    // Maximum number of retry attempts before marking as failed
    protected int $maxRetries = 5;
    
    // Delay in seconds between retry attempts
    protected int $retryDelay = 60; // 1 minute
    
    public function __construct(
        private string $imagePath,
        private array $transformations
    ) {}
    
    public function handle(): void
    {
        foreach ($this->transformations as $transformation) {
            $this->applyTransformation($this->imagePath, $transformation);
        }
    }
    
    private function applyTransformation(string $path, array $transformation): void
    {
        // Image processing logic
        switch ($transformation['type']) {
            case 'resize':
                $this->resizeImage($path, $transformation['width'], $transformation['height']);
                break;
            case 'crop':
                $this->cropImage($path, $transformation['x'], $transformation['y'], 
                                $transformation['width'], $transformation['height']);
                break;
            // Add more transformations as needed
        }
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Dispatching Jobs
            </Typography>

            <Typography>
                BaseAPI provides two ways to dispatch jobs: simple dispatch and fluent dispatch.
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Simple Dispatch
            </Typography>

            <Typography>
                Use the <code>dispatch()</code> helper for immediate job queuing:
            </Typography>

            <CodeBlock language="php" code={`<?php

// In your controllers or services
dispatch(new SendWelcomeEmailJob('user@example.com', 'John Doe'));

// The job is immediately added to the default queue
// Returns the job ID as a string`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Fluent Dispatch
            </Typography>

            <Typography>
                Use the <code>dispatch_later()</code> helper for more control over job options:
            </Typography>

            <CodeBlock language="php" code={`<?php

// Dispatch with options
dispatch_later(new SendWelcomeEmailJob('user@example.com', 'John'))
    ->onQueue('emails')     // Specify queue name
    ->delay(300)            // Delay 5 minutes
    ->dispatch();           // Must call dispatch() to actually queue

// High priority queue
dispatch_later(new UrgentNotificationJob($message))
    ->onQueue('high')
    ->dispatch();

// Background processing queue
dispatch_later(new ProcessDataJob($data))
    ->onQueue('processing')
    ->delay(3600)           // Process in 1 hour
    ->dispatch();`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Job Examples
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Email Job
            </Typography>

            <CodeBlock language="php" code={`<?php

namespace App\\Jobs;

use BaseApi\\Queue\\Job;
use App\\Services\\EmailService;

class SendEmailJob extends Job
{
    public function __construct(
        private string $to,
        private string $subject,
        private string $body,
        private array $attachments = []
    ) {}
    
    public function handle(): void
    {
        $emailService = new EmailService();
        
        $result = $emailService->send([
            'to' => $this->to,
            'subject' => $this->subject,
            'body' => $this->body,
            'attachments' => $this->attachments
        ]);
        
        if (!$result) {
            throw new \\Exception('Failed to send email via email service');
        }
    }
    
    public function failed(\\Throwable $exception): void
    {
        // Log the failure
        error_log("Email job failed for {$this->to}: " . $exception->getMessage());
        
        // Could dispatch a notification job to admins
        dispatch(new NotifyAdminsJob('Email Failed', $exception->getMessage()));
    }
}`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                API Call Job
            </Typography>

            <CodeBlock language="php" code={`<?php

namespace App\\Jobs;

use BaseApi\\Queue\\Job;

class CallExternalApiJob extends Job
{
    protected int $maxRetries = 3;
    protected int $retryDelay = 30;
    
    public function __construct(
        private string $endpoint,
        private array $data,
        private string $method = 'POST'
    ) {}
    
    public function handle(): void
    {
        $response = $this->makeHttpRequest($this->endpoint, $this->data, $this->method);
        
        if ($response['status'] >= 400) {
            throw new \\Exception("API call failed with status {$response['status']}: {$response['body']}");
        }
        
        $this->processResponse($response);
    }
    
    private function makeHttpRequest(string $endpoint, array $data, string $method): array
    {
        // Use cURL or HTTP client library
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: BaseAPI/1.0'
            ],
        ]);
        
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ['status' => $status, 'body' => $body];
    }
    
    private function processResponse(array $response): void
    {
        // Process the successful API response
        $data = json_decode($response['body'], true);
        
        // Store results, update database, etc.
        error_log("API call successful: " . print_r($data, true));
    }
}`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Database Backup Job
            </Typography>

            <CodeBlock language="php" code={`<?php

namespace App\\Jobs;

use BaseApi\\Queue\\Job;
use BaseApi\\App;

class BackupDatabaseJob extends Job
{
    public function __construct(private string $backupPath = 'backups')
    {
        //
    }
    
    public function handle(): void
    {
        $dbName = App::config('database.name');
        $timestamp = date('Ymd_His');
        $filename = "{$dbName}_backup_{$timestamp}.sql";
        $fullBackupPath = App::storagePath("{$this->backupPath}/{$filename}");
        
        // Ensure backup directory exists
        $dir = dirname($fullBackupPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // This example uses SQLite - adapt for your database
        $dbConnection = App::db()->getConnection();
        if ($dbConnection->getDriver()->getName() === 'sqlite') {
            $sourceDbPath = App::basePath(App::config('database.name'));
            if (file_exists($sourceDbPath)) {
                copy($sourceDbPath, $fullBackupPath);
                error_log("Database backed up to: {$fullBackupPath}");
            } else {
                throw new \\Exception("Database file not found at {$sourceDbPath}");
            }
        } else {
            // For MySQL/PostgreSQL, use command-line tools
            $this->backupWithCommand($fullBackupPath);
        }
    }
    
    private function backupWithCommand(string $backupPath): void
    {
        // Example for MySQL
        $command = sprintf(
            'mysqldump -u%s -p%s %s > %s',
            escapeshellarg(App::config('database.user')),
            escapeshellarg(App::config('database.password')),
            escapeshellarg(App::config('database.name')),
            escapeshellarg($backupPath)
        );
        
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            throw new \\Exception("Database backup failed: " . implode("\\n", $output));
        }
        
        error_log("Database backed up to: {$backupPath}");
    }
    
    public function failed(\\Throwable $exception): void
    {
        error_log("Database backup failed: " . $exception->getMessage());
        // Notify administrators about backup failure
        dispatch(new NotifyAdminsJob('Database Backup Failed', $exception->getMessage()));
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Best Practices
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Keep Jobs Focused"
                        secondary="Each job should handle a single responsibility"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Make Jobs Idempotent"
                        secondary="Jobs should be safe to run multiple times with the same result"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Handle Failures Gracefully"
                        secondary="Always implement the failed() method to handle job failures"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Use Appropriate Retry Settings"
                        secondary="Don't retry jobs that will consistently fail (e.g., invalid email addresses)"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Minimize Job Data"
                        secondary="Only store necessary data in job constructors to reduce serialization overhead"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Use Type Hints"
                        secondary="Always use type hints for better IDE support and runtime safety"
                    />
                </ListItem>
            </List>

            <Callout type="warning" title="Serialization Considerations">
                Job objects are serialized when stored in the queue. Avoid storing large objects or resources 
                (like database connections) as job properties. Instead, resolve them in the <code>handle()</code> method.
            </Callout>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Testing Jobs
            </Typography>

            <Typography>
                Use the sync driver for testing to execute jobs immediately:
            </Typography>

            <CodeBlock language="php" code={`<?php

// In your test setup
putenv('QUEUE_DRIVER=sync');

// Jobs will execute immediately during tests
dispatch(new TestJob());

// Or with options (still executes immediately with sync driver)
dispatch_later(new TestJob())->onQueue('test')->dispatch();

class TestJobTest extends TestCase
{
    public function testJobExecution()
    {
        // Job executes immediately in sync mode
        $result = dispatch(new ProcessDataJob($testData));
        
        // Assert the job's side effects
        $this->assertTrue(SomeModel::where('status', 'processed')->exists());
    }
}`} />

            <Alert severity="success" sx={{ mt: 4 }}>
                <strong>Job Creation Checklist:</strong>
                <br />• Generate with <code>make:job</code> command
                <br />• Implement <code>handle()</code> method with job logic
                <br />• Configure <code>maxRetries</code> and <code>retryDelay</code>
                <br />• Implement <code>failed()</code> method for error handling
                <br />• Use appropriate dispatch method based on requirements
                <br />• Test with sync driver during development
            </Alert>
        </Box>
    );
}
