import { Box, Typography, Alert, List, ListItem, ListItemText, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

const workerOptions = [
    { option: '--queue=emails', description: 'Process only the emails queue' },
    { option: '--sleep=5', description: 'Sleep 5 seconds when no jobs are available' },
    { option: '--max-jobs=500', description: 'Process max 500 jobs before restarting' },
    { option: '--memory=256', description: 'Restart worker when memory exceeds 256MB' },
    { option: '--max-time=7200', description: 'Restart worker after 2 hours' },
];

export default function ProcessingJobs() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Processing Jobs
            </Typography>

            <Typography variant="h5" color="text.secondary" paragraph>
                Learn how to run queue workers and monitor job processing
            </Typography>

            <Typography>
                Queue workers are background processes that continuously pull jobs from queues and execute them.
                BaseAPI provides robust worker management with memory limits, graceful shutdown, and comprehensive
                monitoring capabilities.
            </Typography>

            <Callout type="info" title="Available Queue Commands">
                BaseAPI provides the following queue commands: <code>queue:work</code> (process jobs), 
                <code>queue:status</code> (view queue statistics), <code>queue:retry</code> (retry failed jobs), 
                and <code>queue:install</code> (create jobs table).
            </Callout>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Starting Workers
            </Typography>

            <Typography>
                Use the <code>queue:work</code> command to start processing jobs:
            </Typography>

            <CodeBlock language="bash" code={`# Basic worker - processes default queue (sleeps 3s when no jobs)
./mason queue:work`} />

            <CodeBlock language="bash" code={`# Process specific queue
./mason queue:work --queue=emails`} />

            <CodeBlock language="bash" code={`# Custom sleep time when no jobs available
./mason queue:work --sleep=5`} />

            <CodeBlock language="bash" code={`# Limit memory usage and job count
./mason queue:work --max-jobs=1000 --memory=128`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Worker Options
            </Typography>

            <Typography>
                Configure worker behavior with command-line options:
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Option</strong></TableCell>
                            <TableCell><strong>Description</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {workerOptions.map((opt, index) => (
                            <TableRow key={index}>
                                <TableCell><code>{opt.option}</code></TableCell>
                                <TableCell>{opt.description}</TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </TableContainer>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Multiple Queues
            </Typography>

            <Typography>
                Organize jobs into different queues based on priority or type:
            </Typography>

            <CodeBlock language="bash" code={`# Process high priority queue
./mason queue:work --queue=high --sleep=1`} />

            <CodeBlock language="bash" code={`# Process email queue
./mason queue:work --queue=emails --sleep=3`} />

            <CodeBlock language="bash" code={`# Process default queue
./mason queue:work --queue=default --sleep=5`} />

            <Typography>
                Example of dispatching to different queues:
            </Typography>

            <CodeBlock language="php" code={`<?php

// High priority notifications
dispatch_later(new UrgentNotificationJob($alert))
    ->onQueue('high')
    ->dispatch();

// Email processing
dispatch_later(new SendEmailJob($to, $subject, $body))
    ->onQueue('emails')
    ->dispatch();

// Background data processing
dispatch_later(new ProcessDataJob($data))
    ->onQueue('processing')
    ->dispatch();`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Monitoring Job Status
            </Typography>

            <Typography>
                Check the status of your queues and monitor job processing:
            </Typography>

            <CodeBlock language="bash" code={`# Check queue status
./mason queue:status

# Output example:
# Queue Status:
# =============
#   default: 15 jobs pending
#   emails: 3 jobs pending
#   high: 0 jobs pending`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Worker Management
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Resource Limits
            </Typography>

            <Typography>
                Workers automatically restart when resource limits are reached:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Memory Limit"
                        secondary="Worker restarts when memory usage exceeds the configured limit (default: 128MB)"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Time Limit"
                        secondary="Worker restarts after running for the maximum time (default: 1 hour)"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Job Limit"
                        secondary="Worker restarts after processing the maximum number of jobs (default: 1000)"
                    />
                </ListItem>
            </List>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Graceful Shutdown
            </Typography>

            <Typography>
                Workers handle shutdown signals gracefully:
            </Typography>

            <CodeBlock language="bash" code={`# Graceful shutdown with SIGTERM
kill -TERM <worker_pid>

# Immediate shutdown with SIGINT (Ctrl+C)
kill -INT <worker_pid>`} />

            <Callout type="info" title="Signal Handling">
                Workers complete the current job before shutting down when receiving SIGTERM or SIGINT signals.
                This ensures jobs aren't interrupted mid-execution.
            </Callout>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Failed Job Handling
            </Typography>

            <Typography>
                Jobs that fail permanently (after all retry attempts) can be managed:
            </Typography>

            <CodeBlock language="bash" code={`# Retry specific failed job by ID
./mason queue:retry --id=job_uuid_here

# Retry all failed jobs (interactive)
./mason queue:retry`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Viewing Failed Jobs
            </Typography>

            <Typography>
                Use queue:status to see job statistics, or query the database directly for detailed failed job information:
            </Typography>

            <CodeBlock language="bash" code={`# View overall queue statistics (includes failed job count)
./mason queue:status`} />

            <CodeBlock language="sql" code={`-- Query failed jobs directly from database
SELECT id, queue, error, failed_at, attempts 
FROM jobs 
WHERE status = 'failed' 
ORDER BY failed_at DESC 
LIMIT 10;`} />

            <Typography>
                Jobs are automatically retried based on their configuration:
            </Typography>

            <CodeBlock language="php" code={`<?php

class RetryableJob extends Job
{
    protected int $maxRetries = 5;      // Retry up to 5 times
    protected int $retryDelay = 60;     // Wait 60 seconds between retries
    
    public function handle(): void
    {
        // Job logic that might fail
        if (rand(1, 10) <= 3) { // 30% chance of failure
            throw new \\Exception('Simulated failure');
        }
        
        echo "Job completed successfully!";
    }
    
    public function failed(\\Throwable $exception): void
    {
        // Called when job permanently fails (after all retries)
        error_log("Job permanently failed: " . $exception->getMessage());
        
        // Send notification to administrators
        dispatch_later(new NotifyAdminsJob('Job Failed', $exception->getMessage()))->dispatch();
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Production Deployment
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Process Management with Supervisor
            </Typography>

            <Typography>
                Supervisor is a process control system for UNIX-like operating systems that monitors and manages 
                processes. In production environments, it's essential for keeping queue workers running continuously, 
                automatically restarting them if they crash or stop unexpectedly.
            </Typography>

            <Callout type="info" title="Why Supervisor?">
                Without a process manager, if your queue worker crashes or the server restarts, your background 
                job processing stops completely. Supervisor ensures your workers stay running 24/7, automatically 
                handles restarts, and can scale workers across multiple processes.
            </Callout>

            <Typography>
                Install Supervisor on Ubuntu/Debian:
            </Typography>

            <CodeBlock language="bash" code={`# Install Supervisor
sudo apt update
sudo apt install supervisor

# Enable and start the service
sudo systemctl enable supervisor
sudo systemctl start supervisor`} />

            <Typography>
                Create a Supervisor configuration file for BaseAPI queue workers:
            </Typography>

            <CodeBlock language="ini" code={`[program:baseapi-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/app/mason queue:work --sleep=3 --max-jobs=1000
directory=/path/to/your/app
autostart=true
autorestart=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/path/to/your/app/storage/logs/worker.log
stopwaitsecs=3600`} />

            <Typography>
                Save this configuration to <code>/etc/supervisor/conf.d/baseapi-worker.conf</code>. Each configuration 
                option serves a specific purpose:
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Option</strong></TableCell>
                            <TableCell><strong>Purpose</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            <TableCell><code>[program:baseapi-queue-worker]</code></TableCell>
                            <TableCell>Defines the program name for Supervisor to manage</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>process_name</code></TableCell>
                            <TableCell>Names each process (e.g., baseapi-queue-worker_00, _01, _02)</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>command</code></TableCell>
                            <TableCell>The exact command to run the queue worker with options</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>directory</code></TableCell>
                            <TableCell>Working directory where the command should execute</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>autostart=true</code></TableCell>
                            <TableCell>Starts workers automatically when Supervisor starts</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>autorestart=true</code></TableCell>
                            <TableCell>Restarts workers if they crash or exit unexpectedly</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>user=www-data</code></TableCell>
                            <TableCell>Runs workers under the web server user (secure permissions)</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>numprocs=3</code></TableCell>
                            <TableCell>Spawns 3 worker processes for parallel job processing</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>redirect_stderr=true</code></TableCell>
                            <TableCell>Sends error output to the same log file as standard output</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>stdout_logfile</code></TableCell>
                            <TableCell>File path where all worker output is logged</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>stopwaitsecs=3600</code></TableCell>
                            <TableCell>Waits 1 hour for graceful shutdown before force killing</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>

            <Typography>
                After creating the configuration file, manage your workers with:
            </Typography>

            <CodeBlock language="bash" code={`# Update Supervisor with new configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start the workers
sudo supervisorctl start baseapi-queue-worker:*

# Check worker status
sudo supervisorctl status

# Restart all workers
sudo supervisorctl restart baseapi-queue-worker:*

# Stop all workers
sudo supervisorctl stop baseapi-queue-worker:*

# View worker logs
sudo supervisorctl tail baseapi-queue-worker:baseapi-queue-worker_00`} />

            <Callout type="tip" title="How Supervisor Works with BaseAPI">
                Here's the complete workflow: 1) You deploy your BaseAPI app, 2) Supervisor starts your configured 
                queue workers automatically, 3) Workers continuously poll the database for jobs using 
                <code>queue:work</code>, 4) If a worker crashes, Supervisor immediately restarts it, 5) Your 
                background jobs keep processing without interruption.
            </Callout>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Multiple Worker Configuration
            </Typography>

            <Typography>
                For high-traffic applications, you can configure different Supervisor programs for different queue 
                priorities and types. This allows you to allocate more resources to critical jobs:
            </Typography>

            <CodeBlock language="ini" code={`# High priority worker - processes urgent notifications
[program:baseapi-high-queue]
command=php /path/to/app/mason queue:work --queue=high --sleep=1
directory=/path/to/app
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/app/storage/logs/high-worker.log

# Email worker - handles email sending
[program:baseapi-email-queue]
command=php /path/to/app/mason queue:work --queue=emails --sleep=3
directory=/path/to/app
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/app/storage/logs/email-worker.log

# Default queue worker - processes general background tasks
[program:baseapi-default-queue]
command=php /path/to/app/mason queue:work --queue=default --sleep=5
directory=/path/to/app
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/app/storage/logs/default-worker.log`} />

            <Typography>
                This configuration creates dedicated workers for different job types:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="High Priority Queue (2 workers)"
                        secondary="Processes urgent jobs with minimal sleep time for fast response"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Email Queue (2 workers)"
                        secondary="Handles email sending with moderate sleep time and separate logging"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Default Queue (1 worker)"
                        secondary="Processes general background tasks with longer sleep time"
                    />
                </ListItem>
            </List>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Supervisor Troubleshooting
            </Typography>

            <Typography>
                Common issues and solutions when using Supervisor with BaseAPI:
            </Typography>

            <CodeBlock language="bash" code={`# Check if Supervisor is running
sudo systemctl status supervisor

# View Supervisor logs
sudo tail -f /var/log/supervisor/supervisord.log

# Check worker status and recent restarts
sudo supervisorctl status

# View detailed worker output
sudo supervisorctl tail -f baseapi-queue-worker:baseapi-queue-worker_00

# If workers aren't starting, check configuration
sudo supervisorctl avail

# Restart Supervisor daemon if needed
sudo systemctl restart supervisor`} />

            <Alert severity="info" sx={{ mt: 3 }}>
                <strong>File Permissions:</strong> Ensure your application files are readable by the <code>www-data</code> user, 
                and that log directories exist with proper write permissions. Common permission issues prevent workers from starting.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Performance Optimization
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Worker Configuration Tips
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Adjust Sleep Time"
                        secondary="Lower sleep times for high-frequency queues, higher for low-frequency ones"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Set Appropriate Memory Limits"
                        secondary="Monitor actual memory usage and set limits accordingly"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Use Multiple Workers"
                        secondary="Run multiple workers for high-throughput processing"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Queue Separation"
                        secondary="Separate critical jobs from bulk processing jobs"
                    />
                </ListItem>
            </List>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Database Optimization
            </Typography>

            <Typography>
                Optimize database performance for queue operations:
            </Typography>

            <CodeBlock language="sql" code={`-- These indexes are automatically created by queue:install
CREATE INDEX jobs_queue_status_run_at_index ON jobs (queue, status, run_at);
CREATE INDEX jobs_status_run_at_index ON jobs (status, run_at);

-- Monitor job table growth
SELECT status, COUNT(*) as count FROM jobs GROUP BY status;

-- Clean up old completed jobs (manual cleanup example)
DELETE FROM jobs WHERE status = 'completed' AND completed_at < DATE_SUB(NOW(), INTERVAL 7 DAY);`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Debugging Workers
            </Typography>

            <Typography>
                Common debugging techniques for queue workers:
            </Typography>

            <CodeBlock language="bash" code={`# Test job processing manually (single job)
./mason queue:work --max-jobs=1

# Process with shorter sleep for testing
./mason queue:work --sleep=1

# Check worker logs
tail -f storage/logs/worker.log

# Monitor system resources
htop
watch "ps aux | grep queue:work"`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Health Monitoring
            </Typography>

            <Typography>
                Monitor your queue system health:
            </Typography>

            <CodeBlock language="bash" code={`# Check queue status regularly
watch -n 30 "./mason queue:status"

# Monitor worker output and job processing
tail -f storage/logs/worker.log

# Monitor application logs for job failures
tail -f storage/logs/baseapi.log | grep "Job failed"

# System monitoring
# Monitor CPU, memory, and disk I/O for queue workers`} />

            <Alert severity="warning" sx={{ mt: 4 }}>
                <strong>Production Considerations:</strong>
                <br />• Always use process managers like Supervisor in production
                <br />• Set appropriate resource limits to prevent memory leaks
                <br />• Monitor queue sizes to detect processing bottlenecks
                <br />• Implement alerting for failed jobs and worker failures
                <br />• Regularly clean up old completed and failed jobs
            </Alert>

            <Callout type="tip" title="Development vs Production">
                During development, run workers manually for debugging. In production, use process managers
                to ensure workers automatically restart and stay running.
            </Callout>

            <Alert severity="success" sx={{ mt: 4 }}>
                <strong>Worker Management Best Practices:</strong>
                <br />• Use multiple workers for high-throughput processing
                <br />• Separate queues by priority and job type
                <br />• Set resource limits appropriate for your server
                <br />• Monitor worker health and queue sizes
                <br />• Implement proper logging and alerting
                <br />• Clean up old jobs regularly
            </Alert>
        </Box>
    );
}
