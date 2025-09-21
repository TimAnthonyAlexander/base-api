import { Box, Typography, Alert, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper, List, ListItem, ListItemText } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';
import EnvTable from '../../components/EnvTable';

const queueEnvVars = [
    {
        key: 'QUEUE_DRIVER',
        default: 'database',
        description: 'Default queue driver',
        type: 'enum' as const,
        options: ['sync', 'database'],
    },
    {
        key: 'QUEUE_DB_CONNECTION',
        default: 'default',
        description: 'Database connection for database queue driver',
    },
    {
        key: 'QUEUE_WORKER_SLEEP',
        default: '3',
        description: 'Seconds to sleep when no jobs are available',
        type: 'number' as const,
    },
    {
        key: 'QUEUE_WORKER_MAX_JOBS',
        default: '1000',
        description: 'Maximum jobs to process before worker restart',
        type: 'number' as const,
    },
    {
        key: 'QUEUE_WORKER_MAX_TIME',
        default: '3600',
        description: 'Maximum seconds before worker restart',
        type: 'number' as const,
    },
    {
        key: 'QUEUE_WORKER_MEMORY',
        default: '128',
        description: 'Memory limit in MB before worker restart',
        type: 'number' as const,
    },
    {
        key: 'QUEUE_FAILED_RETENTION',
        default: '30',
        description: 'Days to retain failed jobs before cleanup',
        type: 'number' as const,
    },
    {
        key: 'QUEUE_FAILED_CLEANUP',
        default: 'true',
        description: 'Enable automatic cleanup of old failed jobs',
        type: 'boolean' as const,
    },
];

export default function QueueConfiguration() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Queue Configuration
            </Typography>

            <Typography variant="h5" color="text.secondary" paragraph>
                Configure BaseAPI's queue system for your deployment environment
            </Typography>

            <Typography>
                The BaseAPI queue system is highly configurable, supporting different drivers, worker settings,
                and deployment scenarios. Configuration is managed through environment variables and the
                application's config files.
            </Typography>

            <Alert severity="info" sx={{ my: 3 }}>
                Queue configuration follows BaseAPI's unified configuration pattern - environment variables
                override application config which extends framework defaults.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Environment Variables
            </Typography>

            <Typography>
                Configure the queue system using environment variables in your <code>.env</code> file:
            </Typography>

            <EnvTable envVars={queueEnvVars} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Queue Drivers
            </Typography>

            <Typography>
                BaseAPI supports multiple queue drivers for different deployment scenarios:
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Sync Driver (Development)
            </Typography>

            <Typography>
                Executes jobs immediately for development and testing:
            </Typography>

            <CodeBlock language="bash" code={`# .env for development
QUEUE_DRIVER=sync

# Jobs execute immediately - no background processing`} />

            <List>
                <ListItem>
                    <ListItemText
                        primary="Immediate Execution"
                        secondary="Jobs run synchronously during the request"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="No Setup Required"
                        secondary="No database tables or external services needed"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Perfect for Testing"
                        secondary="Ideal for unit tests and development debugging"
                    />
                </ListItem>
            </List>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Database Driver (Production)
            </Typography>

            <Typography>
                Stores jobs in database table for persistent, reliable processing:
            </Typography>

            <CodeBlock language="bash" code={`# .env for production
QUEUE_DRIVER=database
QUEUE_DB_CONNECTION=default

# Worker settings
QUEUE_WORKER_SLEEP=3
QUEUE_WORKER_MAX_JOBS=1000
QUEUE_WORKER_MEMORY=128`} />

            <List>
                <ListItem>
                    <ListItemText
                        primary="Persistent Storage"
                        secondary="Jobs survive server restarts and failures"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="ACID Compliance"
                        secondary="Atomic job operations prevent data corruption"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Built-in Retry Logic"
                        secondary="Automatic retry handling with exponential backoff"
                    />
                </ListItem>
            </List>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Application Configuration
            </Typography>

            <Typography>
                Override default settings in your <code>config/app.php</code>:
            </Typography>

            <CodeBlock language="php" code={`<?php

return [
    // ... other configuration ...

    'queue' => [
        // Default queue driver
        'default' => $_ENV['QUEUE_DRIVER'] ?? 'database',

        // Driver configurations
        'drivers' => [
            'sync' => [
                'driver' => 'sync',
            ],

            'database' => [
                'driver' => 'database',
                'table' => 'jobs',
                'connection' => $_ENV['QUEUE_DB_CONNECTION'] ?? 'default',
            ],

            // Future Redis driver
            'redis' => [
                'driver' => 'redis',
                'connection' => 'queue',
                'prefix' => $_ENV['QUEUE_REDIS_PREFIX'] ?? 'baseapi_queue:',
            ],
        ],

        // Worker configuration
        'worker' => [
            'sleep' => (int)($_ENV['QUEUE_WORKER_SLEEP'] ?? 3),
            'max_jobs' => (int)($_ENV['QUEUE_WORKER_MAX_JOBS'] ?? 1000),
            'max_time' => (int)($_ENV['QUEUE_WORKER_MAX_TIME'] ?? 3600),
            'memory_limit' => (int)($_ENV['QUEUE_WORKER_MEMORY'] ?? 128),
        ],

        // Failed job handling
        'failed' => [
            'retention_days' => (int)($_ENV['QUEUE_FAILED_RETENTION'] ?? 30),
            'cleanup_enabled' => $_ENV['QUEUE_FAILED_CLEANUP'] ?? true,
        ],
    ],
];`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Environment-Specific Configuration
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Development Settings
            </Typography>

            <CodeBlock language="bash" code={`# .env.local - Development
QUEUE_DRIVER=sync
# No worker settings needed - jobs execute immediately`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Staging Settings
            </Typography>

            <CodeBlock language="bash" code={`# .env.staging - Staging
QUEUE_DRIVER=database
QUEUE_WORKER_SLEEP=5
QUEUE_WORKER_MAX_JOBS=100
QUEUE_WORKER_MEMORY=64
QUEUE_FAILED_RETENTION=7`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Production Settings
            </Typography>

            <CodeBlock language="bash" code={`# .env.production - Production
QUEUE_DRIVER=database
QUEUE_WORKER_SLEEP=3
QUEUE_WORKER_MAX_JOBS=1000
QUEUE_WORKER_MAX_TIME=3600
QUEUE_WORKER_MEMORY=128
QUEUE_FAILED_RETENTION=30
QUEUE_FAILED_CLEANUP=true`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Database Schema
            </Typography>

            <Typography>
                The <code>queue:install</code> command creates the necessary database table:
            </Typography>

            <CodeBlock language="sql" code={`CREATE TABLE jobs (
    id VARCHAR(36) PRIMARY KEY,
    queue VARCHAR(255) DEFAULT 'default',
    payload LONGTEXT NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    error TEXT NULL,
    run_at DATETIME NOT NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    failed_at DATETIME NULL,
    created_at DATETIME NOT NULL
);

-- Performance indexes
CREATE INDEX jobs_queue_status_run_at_index ON jobs (queue, status, run_at);
CREATE INDEX jobs_status_run_at_index ON jobs (status, run_at);`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Worker Performance Tuning
            </Typography>

            <Typography>
                Optimize worker performance for your deployment:
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Setting</strong></TableCell>
                            <TableCell><strong>Low Traffic</strong></TableCell>
                            <TableCell><strong>Medium Traffic</strong></TableCell>
                            <TableCell><strong>High Traffic</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            <TableCell><code>QUEUE_WORKER_SLEEP</code></TableCell>
                            <TableCell>5-10 seconds</TableCell>
                            <TableCell>3-5 seconds</TableCell>
                            <TableCell>1-2 seconds</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>QUEUE_WORKER_MAX_JOBS</code></TableCell>
                            <TableCell>100-500</TableCell>
                            <TableCell>500-1000</TableCell>
                            <TableCell>1000-2000</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>QUEUE_WORKER_MEMORY</code></TableCell>
                            <TableCell>64-128 MB</TableCell>
                            <TableCell>128-256 MB</TableCell>
                            <TableCell>256-512 MB</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell>Number of Workers</TableCell>
                            <TableCell>1-2</TableCell>
                            <TableCell>2-4</TableCell>
                            <TableCell>4-8+</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Monitoring Configuration
            </Typography>

            <Typography>
                Configure logging and monitoring for queue operations:
            </Typography>

            <CodeBlock language="php" code={`<?php

// In config/app.php - extend logging configuration
'logging' => [
    'channels' => [
        'queue' => [
            'driver' => 'file',
            'path' => storage_path('logs/queue.log'),
            'level' => 'info',
        ],
    ],
],`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Security Considerations
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Job Serialization"
                        secondary="Jobs are serialized using PHP's serialize() function - ensure job data is safe"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Database Access"
                        secondary="Queue workers need database access - secure connection credentials"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Failed Job Data"
                        secondary="Failed jobs may contain sensitive data - consider data retention policies"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Worker Permissions"
                        secondary="Run workers with appropriate system user permissions"
                    />
                </ListItem>
            </List>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Troubleshooting Configuration
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Common Issues
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Workers Not Processing Jobs"
                        secondary="Check QUEUE_DRIVER setting and database connectivity"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="High Memory Usage"
                        secondary="Reduce QUEUE_WORKER_MEMORY or QUEUE_WORKER_MAX_JOBS settings"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Jobs Timing Out"
                        secondary="Increase QUEUE_WORKER_MAX_TIME or optimize job logic"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Database Lock Contention"
                        secondary="Reduce number of workers or increase QUEUE_WORKER_SLEEP"
                    />
                </ListItem>
            </List>

            <Callout type="warning" title="Production Deployment">
                Always test queue configuration in staging before deploying to production. Monitor worker
                performance and adjust settings based on actual usage patterns.
            </Callout>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Configuration Validation
            </Typography>

            <Typography>
                Validate your queue configuration:
            </Typography>

            <CodeBlock language="bash" code={`# Test queue installation
./mason queue:install

# Check database migration
./mason migrate:apply

# Verify worker configuration
./mason queue:work --max-jobs=1

# Check queue status
./mason queue:status`} />

            <Alert severity="success" sx={{ mt: 4 }}>
                <strong>Configuration Best Practices:</strong>
                <br />• Use <code>sync</code> driver for development and testing
                <br />• Use <code>database</code> driver for production deployments
                <br />• Set memory and time limits appropriate for your server
                <br />• Monitor worker performance and adjust settings accordingly
                <br />• Use environment-specific configuration files
                <br />• Test configuration changes in staging first
            </Alert>

            <Callout type="tip" title="Framework Configuration">
                BaseAPI's queue system includes sensible defaults in the framework configuration.
                You only need to override settings that differ from the defaults in your application config.
            </Callout>
        </Box>
    );
}
