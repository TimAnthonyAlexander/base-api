import { Box, Typography, Alert, List, ListItem, ListItemText, Divider } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

export default function Debugging() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Debug & Profiling
      </Typography>
      <Typography variant="h5" color="text.secondary" paragraph>
        Comprehensive debugging tools for development and performance analysis
      </Typography>

      <Typography paragraph>
        BaseAPI includes powerful debugging and profiling tools to help you understand your application's 
        performance, track SQL queries, monitor memory usage, and debug issues during development. 
        These tools are designed to be zero-overhead in production and easy to enable during development.
      </Typography>

      <Alert severity="info" sx={{ my: 3 }}>
        Debug features are automatically disabled in production environments for security and performance. 
        All debugging is controlled by a single <code>APP_DEBUG</code> environment variable.
      </Alert>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Quick Start
      </Typography>

      <Typography paragraph>
        Enable debugging with a single environment variable:
      </Typography>

      <CodeBlock language="bash" code={`# In your .env file
APP_DEBUG=true`} />

      <Typography paragraph sx={{ mt: 2 }}>
        That's it! All debug features are now enabled automatically.
      </Typography>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Debug Features
      </Typography>

      <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
        Automatic SQL Query Logging
      </Typography>

      <Typography paragraph>
        Every database query is automatically tracked with timing, parameters, and memory usage:
      </Typography>

      <CodeBlock language="php" code={`<?php
// All queries are automatically logged when APP_DEBUG=true
$users = User::where('active', true)->limit(10)->get();

// Debug output will show:
// - SQL: SELECT * FROM users WHERE active = ? LIMIT 10
// - Bindings: [true]
// - Execution time: 12.3ms
// - Memory usage: 2.1MB`} />

      <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
        Performance Profiling
      </Typography>

      <Typography paragraph>
        Profile specific operations to understand performance bottlenecks:
      </Typography>

      <CodeBlock language="php" code={`<?php
use BaseApi\\App;

// Profile a specific operation
$result = App::profiler()->profile('expensive_operation', function() {
    return $this->complexCalculation();
});

// Take memory snapshots at key points
App::profiler()->trackMemory('before_heavy_processing');
$data = $this->processLargeDataset();
App::profiler()->trackMemory('after_processing');`} />

      <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
        Exception Tracking
      </Typography>

      <Typography paragraph>
        All exceptions are automatically captured with context information:
      </Typography>

      <CodeBlock language="php" code={`<?php
try {
    $result = $this->riskyOperation();
} catch (\\Exception $e) {
    // Exception is automatically logged with:
    // - Stack trace
    // - Request context
    // - Memory usage at time of error
    // - Sensitive data automatically filtered
    
    // Add custom context if needed
    App::profiler()->logException($e, [
        'user_id' => $userId,
        'operation' => 'data_import'
    ]);
}`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Debug Middleware
      </Typography>

      <Typography paragraph>
        Add the <code>DebugMiddleware</code> to routes you want to debug:
      </Typography>

      <CodeBlock language="php" code={`<?php
use BaseApi\\Http\\DebugMiddleware;

// In routes/api.php
$router->get('/users', [
    DebugMiddleware::class,
    UserController::class,
]);`} />

      <Typography paragraph>
        The middleware automatically:
      </Typography>

      <List>
        <ListItem>
          <ListItemText
            primary="Enables profiling for the request"
            secondary="Tracks timing, memory usage, and query execution"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Logs request and response details"
            secondary="Method, URL, headers, and response information"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Injects debug information"
            secondary="Adds debug data to JSON responses or HTML debug panel"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Captures exceptions"
            secondary="Logs any exceptions that occur during request processing"
          />
        </ListItem>
      </List>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Debug Output
      </Typography>

      <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
        JSON API Responses
      </Typography>

      <Typography paragraph>
        When debugging is enabled, JSON responses include a comprehensive <code>debug</code> section:
      </Typography>

      <CodeBlock language="json" code={`{
  "data": {
    "users": [...]
  },
  "debug": {
    "request": {
      "total_time_ms": 145.7,
      "query_time_ms": 89.2,
      "memory_peak_mb": 12.4,
      "query_count": 3
    },
    "queries": [
      {
        "sql": "SELECT * FROM users WHERE active = ?",
        "bindings": [true],
        "time_ms": 89.2,
        "slow": true
      }
    ],
    "memory_snapshots": [
      {
        "label": "after_query",
        "memory_mb": 11.2,
        "peak_memory_mb": 12.4
      }
    ],
    "warnings": [
      "Found 1 slow queries (>100ms)",
      "High query count: 3 queries executed"
    ]
  }
}`} />

      <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
        HTML Debug Panel
      </Typography>

      <Typography paragraph>
        For HTML responses, a visual debug panel is automatically injected at the bottom of the page 
        showing performance metrics, query details, and warnings in a developer-friendly interface.
      </Typography>

      <Callout type="tip" title="Debug Panel Features">
        The debug panel includes expandable sections for queries, memory usage, exceptions, 
        and performance warnings with syntax highlighting and easy-to-read metrics.
      </Callout>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Performance Warnings
      </Typography>

      <Typography paragraph>
        BaseAPI automatically detects common performance issues and provides warnings:
      </Typography>

      <List>
        <ListItem>
          <ListItemText
            primary="Slow Query Detection"
            secondary="Queries taking longer than 100ms are flagged automatically"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="High Query Count"
            secondary="Requests with more than 20 queries suggest N+1 query problems"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Memory Usage Warnings"
            secondary="Memory usage above 128MB is flagged for optimization"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Memory Growth Tracking"
            secondary="Significant memory growth between snapshots is detected"
          />
        </ListItem>
      </List>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Configuration
      </Typography>

      <Typography paragraph>
        All debug features use the single <code>APP_DEBUG</code> environment variable, but you can 
        fine-tune specific aspects if needed:
      </Typography>

      <CodeBlock language="bash" code={`# Primary debug control (this enables everything)
APP_DEBUG=true

# Optional: Fine-tune specific features
SLOW_QUERY_THRESHOLD=100  # Milliseconds for slow query detection`} />

      <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
        Debug Configuration File
      </Typography>

      <Typography paragraph>
        Advanced configuration is available in <code>config/debug.php</code>:
      </Typography>

      <CodeBlock language="php" code={`<?php
// config/debug.php
return [
    'enabled' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
    
    'profiler' => [
        'enabled' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
        'memory_tracking' => true,
        'query_logging' => true,
        'exception_tracking' => true,
    ],
    
    'queries' => [
        'slow_query_threshold' => (int) ($_ENV['SLOW_QUERY_THRESHOLD'] ?? 100),
        'log_bindings' => true,
    ],
    
    'memory' => [
        'warning_threshold' => 128, // MB
        'track_growth' => true,
    ],
];`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Security & Production
      </Typography>

      <Alert severity="warning" sx={{ my: 3 }}>
        <Typography variant="h6" gutterBottom>
          Production Safety
        </Typography>
        <Typography>
          Debug features are automatically disabled when <code>APP_ENV</code> is not set to 
          "local" or "development", regardless of the <code>APP_DEBUG</code> setting. This ensures 
          no debug information is leaked in production environments.
        </Typography>
      </Alert>

      <List>
        <ListItem>
          <ListItemText
            primary="Zero Overhead When Disabled"
            secondary="Debug code paths are completely bypassed in production"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Sensitive Data Protection"
            secondary="Passwords, tokens, and API keys are automatically filtered from logs"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Environment Isolation"
            secondary="Debug features only activate in local/development environments"
          />
        </ListItem>
      </List>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Example Debug Endpoints
      </Typography>

      <Typography paragraph>
        The BaseAPI template includes example debug endpoints to demonstrate the debugging features:
      </Typography>

      <CodeBlock language="bash" code={`# Test the debugging features (local environment only)
curl "http://localhost:8000/debug/query"      # Query logging demo
curl "http://localhost:8000/debug/profiling"  # Manual profiling demo
curl "http://localhost:8000/debug/exception"  # Exception tracking demo
curl "http://localhost:8000/debug/slow-query" # Slow query detection demo
curl "http://localhost:8000/debug/info"       # Debug configuration status`} />

      <Divider sx={{ my: 4 }} />

      <Alert severity="success">
        <Typography variant="h6" gutterBottom>
          Debugging Best Practices
        </Typography>
        <Typography component="div">
          • Use the debug middleware on routes you're actively developing
          <br />• Monitor the debug output for slow queries and optimize them
          <br />• Track memory usage in operations that process large datasets
          <br />• Use manual profiling to identify performance bottlenecks
          <br />• Check debug warnings regularly during development
          <br />• Remember that debug features are automatically disabled in production
        </Typography>
      </Alert>
    </Box>
  );
}
