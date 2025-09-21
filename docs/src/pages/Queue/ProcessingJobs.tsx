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

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Starting Workers
            </Typography>

            <Typography>
                Use the <code>queue:work</code> command to start processing jobs:
            </Typography>

            <CodeBlock language="bash" code={`# Basic worker - processes default queue
./bin/console queue:work

# Process specific queue
./bin/console queue:work --queue=emails

# Custom sleep time when no jobs available
./bin/console queue:work --sleep=5

# Limit memory usage and job count
./bin/console queue:work --max-jobs=1000 --memory=128`} />

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
./bin/console queue:work --queue=high --sleep=1

# Process email queue
./bin/console queue:work --queue=emails --sleep=3

# Process default queue
./bin/console queue:work --queue=default --sleep=5`} />

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
./bin/console queue:status

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
./bin/console queue:retry --id=job_uuid_here

# View failed jobs (would require additional implementation)
./bin/console queue:failed`} />

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
        dispatch(new NotifyAdminsJob('Job Failed', $exception->getMessage()));
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Production Deployment
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Process Management with Supervisor
            </Typography>

            <Typography>
                Use Supervisor to keep queue workers running in production:
            </Typography>

            <CodeBlock language="ini" code={`[program:baseapi-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/app/bin/console queue:work --sleep=3 --max-jobs=1000
directory=/path/to/your/app
autostart=true
autorestart=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/path/to/your/app/storage/logs/worker.log
stopwaitsecs=3600`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Multiple Worker Configuration
            </Typography>

            <Typography>
                Run different workers for different queue priorities:
            </Typography>

            <CodeBlock language="ini" code={`# High priority worker
[program:baseapi-high-queue]
command=php /path/to/app/bin/console queue:work --queue=high --sleep=1
numprocs=2

# Email worker
[program:baseapi-email-queue]
command=php /path/to/app/bin/console queue:work --queue=emails --sleep=3
numprocs=2

# Default queue worker
[program:baseapi-default-queue]
command=php /path/to/app/bin/console queue:work --queue=default --sleep=5
numprocs=1`} />

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

            <CodeBlock language="bash" code={`# Run worker with verbose output
./bin/console queue:work -v

# Test job processing manually
./bin/console queue:work --max-jobs=1

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
watch -n 30 "./bin/console queue:status"

# Monitor failed jobs
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
