import { Box, Typography, Alert, List, ListItem, ListItemText, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

const queueCommands = [
    { command: 'queue:work', description: 'Start processing queue jobs', example: './bin/console queue:work' },
    { command: 'queue:status', description: 'Display queue statistics', example: './bin/console queue:status' },
    { command: 'queue:retry', description: 'Retry failed jobs', example: './bin/console queue:retry' },
    { command: 'queue:install', description: 'Create the jobs table', example: './bin/console queue:install' },
    { command: 'make:job', description: 'Generate a new job class', example: './bin/console make:job SendEmailJob' },
];

export default function QueueOverview() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Queue System Overview
            </Typography>

            <Typography variant="h5" color="text.secondary" paragraph>
                BaseAPI's robust background job processing system for asynchronous task handling
            </Typography>

            <Typography>
                The BaseAPI Queue System provides a powerful background job processing capability that allows you to handle
                time-consuming tasks asynchronously, improving your application's performance and user experience. Jobs can
                include sending emails, processing images, making API calls, generating reports, and any other tasks that
                don't need to block the user's request.
            </Typography>

            <Alert severity="info" sx={{ my: 3 }}>
                The queue system supports multiple drivers (database, sync), automatic retries, failed job handling,
                and includes a comprehensive CLI for managing workers and monitoring job status.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Quick Start
            </Typography>

            <Typography>
                Get started with the queue system in just a few steps:
            </Typography>

            <CodeBlock language="bash" code={`# 1. Install the queue system (creates jobs table)
./bin/console queue:install
./bin/console migrate:apply`} />

            <CodeBlock language="bash" code={`# 2. Create your first job
./bin/console make:job SendWelcomeEmailJob`} />

            <CodeBlock language="bash" code={`# 3. Start processing jobs
./bin/console queue:work`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Basic Job Example
            </Typography>

            <Typography>
                Here's a simple example of creating and dispatching a job:
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
        private string $userName,
    ) {}
    
    public function handle(): void
    {
        // Send welcome email logic here
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
    }
}`} />

            <Typography>
                Dispatch the job in your controllers:
            </Typography>

            <CodeBlock language="php" code={`<?php

// Simple dispatch (immediate queuing)
dispatch(new SendWelcomeEmailJob('user@example.com', 'John'));

// Dispatch with options (fluent interface)
dispatch_later(new SendWelcomeEmailJob('user@example.com', 'John'))
    ->onQueue('emails')
    ->delay(60) // Delay 60 seconds
    ->dispatch(); // Must call dispatch() to actually queue the job`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Queue Drivers
            </Typography>

            <Typography>
                BaseAPI supports multiple queue drivers for different deployment scenarios:
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Driver</strong></TableCell>
                            <TableCell><strong>Use Case</strong></TableCell>
                            <TableCell><strong>Persistence</strong></TableCell>
                            <TableCell><strong>Performance</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            <TableCell><code>sync</code></TableCell>
                            <TableCell>Development, testing</TableCell>
                            <TableCell>None (immediate execution)</TableCell>
                            <TableCell>N/A</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>database</code></TableCell>
                            <TableCell>Production, single server</TableCell>
                            <TableCell>Database table</TableCell>
                            <TableCell>Good</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Available Commands
            </Typography>

            <Typography>
                The queue system provides a comprehensive set of CLI commands:
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Command</strong></TableCell>
                            <TableCell><strong>Description</strong></TableCell>
                            <TableCell><strong>Example</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {queueCommands.map((cmd, index) => (
                            <TableRow key={index}>
                                <TableCell><code>{cmd.command}</code></TableCell>
                                <TableCell>{cmd.description}</TableCell>
                                <TableCell><code>{cmd.example}</code></TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </TableContainer>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Architecture Overview
            </Typography>

            <Typography>
                The queue system consists of several key components working together:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Job Interface & Base Classes"
                        secondary="Define the contract and common behavior for all jobs"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Queue Drivers"
                        secondary="Handle job storage and retrieval from different backends"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Queue Manager"
                        secondary="Central coordination point for dispatching and managing jobs"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Queue Workers"
                        secondary="Background processes that pull and execute jobs from queues"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Console Commands"
                        secondary="CLI tools for worker management, monitoring, and job generation"
                    />
                </ListItem>
            </List>

            <Callout type="tip" title="Development vs Production">
                Use the <code>sync</code> driver during development for immediate job execution and debugging.
                Switch to <code>database</code> driver in production for persistent, reliable job processing.
            </Callout>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Common Use Cases
            </Typography>

            <Typography>
                The queue system is perfect for handling these types of tasks:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Email Processing"
                        secondary="Send welcome emails, newsletters, and notifications without blocking requests"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Image & File Processing"
                        secondary="Resize images, generate thumbnails, process uploads, and convert files"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="External API Calls"
                        secondary="Make third-party API requests with retry logic and timeout handling"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Data Processing"
                        secondary="Generate reports, sync data, and perform batch operations"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Background Maintenance"
                        secondary="Database cleanup, cache warming, and system maintenance tasks"
                    />
                </ListItem>
            </List>

            <Alert severity="success" sx={{ mt: 4 }}>
                <strong>Ready to Get Started?</strong>
                <br />• Install the queue system with <code>queue:install</code>
                <br />• Create your first job with <code>make:job</code>
                <br />• Process jobs with <code>queue:work</code>
                <br />• Monitor progress with <code>queue:status</code>
            </Alert>
        </Box>
    );
}
