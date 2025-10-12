import { Box, Typography, Alert, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper, List, ListItem, ListItemText } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';
import EnvTable from '../../components/EnvTable';

const queueEnvVars = [
    {
        key: 'QUEUE_DRIVER',
        default: 'sync',
        description: 'Default queue driver',
        type: 'enum' as const,
        options: ['sync', 'database'],
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
                Sync Driver (Development - Default)
            </Typography>

            <Typography>
                The default driver that executes jobs immediately for development and testing:
            </Typography>

            <CodeBlock language="bash" code={`# .env for development (default behavior)
QUEUE_DRIVER=sync

# Jobs execute immediately - no background processing
# This is the default if no QUEUE_DRIVER is set`} />

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
        'default' => $_ENV['QUEUE_DRIVER'] ?? 'sync',

        // Driver configurations
        'drivers' => [
            'sync' => [
                'driver' => 'sync',
            ],

            'database' => [
                'driver' => 'database',
                'table' => 'jobs',
            ],
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
# Jobs execute immediately - no background processing needed`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Production Settings
            </Typography>

            <CodeBlock language="bash" code={`# .env.production - Production
QUEUE_DRIVER=database
# Worker settings are configured via command-line options when running workers`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Database Schema
            </Typography>

            <Typography>
                The <code>queue:install</code> command creates the necessary database table:
            </Typography>

            <CodeBlock language="sql" code={`CREATE TABLE jobs (
    id TEXT PRIMARY KEY,
    queue TEXT NOT NULL DEFAULT 'default',
    payload TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
    attempts INTEGER NOT NULL DEFAULT 0,
    error TEXT,
    run_at DATETIME NOT NULL,
    started_at DATETIME,
    completed_at DATETIME,
    failed_at DATETIME,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Performance indexes (automatically created)
CREATE INDEX jobs_queue_status_run_at_index ON jobs (queue, status, run_at);
CREATE INDEX jobs_status_run_at_index ON jobs (status, run_at);
CREATE INDEX jobs_status_index ON jobs (status);`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Worker Performance Tuning
            </Typography>

            <Typography>
                Configure worker performance using command-line options when running <code>./mason queue:work</code>:
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Option</strong></TableCell>
                            <TableCell><strong>Low Traffic</strong></TableCell>
                            <TableCell><strong>Medium Traffic</strong></TableCell>
                            <TableCell><strong>High Traffic</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            <TableCell><code>--sleep</code></TableCell>
                            <TableCell>5-10 seconds</TableCell>
                            <TableCell>3-5 seconds</TableCell>
                            <TableCell>1-2 seconds</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>--max-jobs</code></TableCell>
                            <TableCell>100-500</TableCell>
                            <TableCell>500-1000</TableCell>
                            <TableCell>1000-2000</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>--memory</code></TableCell>
                            <TableCell>64-128 MB</TableCell>
                            <TableCell>128-256 MB</TableCell>
                            <TableCell>256-512 MB</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>--max-time</code></TableCell>
                            <TableCell>1800-3600 seconds</TableCell>
                            <TableCell>3600-7200 seconds</TableCell>
                            <TableCell>3600-14400 seconds</TableCell>
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
                Worker Command Usage
            </Typography>

            <Typography>
                Start a queue worker with custom options:
            </Typography>

            <CodeBlock language="bash" code={`# Basic worker
./mason queue:work

# Worker with custom settings
./mason queue:work --queue=emails --sleep=5 --max-jobs=500 --memory=256

# High-performance worker
./mason queue:work --sleep=1 --max-jobs=2000 --max-time=7200 --memory=512`} />

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
                        secondary="Reduce --memory or --max-jobs options when starting workers"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Workers Running Too Long"
                        secondary="Use --max-time option to automatically restart workers"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Database Lock Contention"
                        secondary="Reduce number of workers or increase --sleep interval"
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

            <CodeBlock language="bash" code={`# Install queue tables
./mason queue:install

# Apply database migration
./mason migrate:apply

# Test worker (process 1 job then stop)
./mason queue:work --max-jobs=1

# Check queue status
./mason queue:status`} />

            <Alert severity="success" sx={{ mt: 4 }}>
                <strong>Configuration Best Practices:</strong>
                <br />• Use <code>sync</code> driver for development and testing (default)
                <br />• Use <code>database</code> driver for production deployments
                <br />• Configure worker options via command-line for your server resources
                <br />• Monitor worker performance and adjust command-line options accordingly
                <br />• Test configuration in staging before production deployment
                <br />• Essential database indexes are created automatically by <code>queue:install</code>
            </Alert>

            <Callout type="tip" title="Simple Configuration">
                BaseAPI's queue system has minimal configuration - just set QUEUE_DRIVER to 'sync' or 'database'.
                Worker performance is controlled through command-line options for maximum flexibility.
            </Callout>
        </Box>
    );
}
