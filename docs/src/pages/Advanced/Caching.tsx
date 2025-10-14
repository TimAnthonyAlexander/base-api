
import { Box, Typography, Alert, List, ListItem, ListItemText, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

export default function CachingConfiguration() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Caching Configuration
            </Typography>
            <Typography variant="h5" color="text.secondary" paragraph>
                Configure BaseAPI's unified caching system for optimal performance
            </Typography>

            <Typography>
                BaseAPI includes a unified caching system that supports multiple drivers and HTTP 
                response caching. Proper caching configuration can dramatically improve your API's performance.
            </Typography>

            <Alert severity="info" sx={{ my: 3 }}>
                BaseAPI's caching system can cache HTTP responses, computed values, and provides
                significant performance improvements for frequently accessed data.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Environment Configuration
            </Typography>

            <Typography>
                Configure caching in your <code>.env</code> file:
            </Typography>

            <CodeBlock language="bash" code={`########################################
# Cache Configuration
########################################

# Default cache driver: array, file, redis
CACHE_DRIVER=file

# Cache key prefix (prevents collisions in shared environments)
CACHE_PREFIX=baseapi_cache

# Default cache TTL in seconds (3600 = 1 hour)
CACHE_DEFAULT_TTL=3600

# File cache path (defaults to storage/cache)
CACHE_PATH=

# Enable HTTP response caching middleware
CACHE_RESPONSES=true
CACHE_RESPONSE_TTL=600

########################################
# Redis Cache Configuration (if using redis driver)
########################################

# Redis connection details for caching
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_CACHE_DB=1`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Cache Drivers
            </Typography>

            <Typography>
                BaseAPI supports three cache drivers for different deployment scenarios:
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Driver</strong></TableCell>
                            <TableCell><strong>Use Case</strong></TableCell>
                            <TableCell><strong>Performance</strong></TableCell>
                            <TableCell><strong>Persistence</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            <TableCell><code>array</code></TableCell>
                            <TableCell>Development, testing</TableCell>
                            <TableCell>Fastest</TableCell>
                            <TableCell>Request only</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>file</code></TableCell>
                            <TableCell>Single server deployment</TableCell>
                            <TableCell>Fast</TableCell>
                            <TableCell>Persistent</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>redis</code></TableCell>
                            <TableCell>Production, multi-server</TableCell>
                            <TableCell>Very fast</TableCell>
                            <TableCell>Persistent, distributed</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Array Driver (Development)
            </Typography>

            <Typography>
                In-memory caching that's perfect for development and testing:
            </Typography>

            <CodeBlock language="bash" code={`# .env for development
CACHE_DRIVER=array
CACHE_RESPONSES=false  # Usually disabled in development`} />

            <List>
                <ListItem>
                    <ListItemText
                        primary="Fastest Performance"
                        secondary="No I/O overhead - data stored in PHP memory"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Request-scoped"
                        secondary="Cache is cleared between requests"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Zero Configuration"
                        secondary="No external dependencies required"
                    />
                </ListItem>
            </List>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                File Driver (Single Server)
            </Typography>

            <Typography>
                File-based caching that persists between requests:
            </Typography>

            <CodeBlock language="bash" code={`# .env for single server deployment
CACHE_DRIVER=file
CACHE_PATH=storage/cache  # Optional custom path
CACHE_RESPONSES=true`} />

            <List>
                <ListItem>
                    <ListItemText
                        primary="Persistent Storage"
                        secondary="Cache survives between requests and server restarts"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="No Dependencies"
                        secondary="No external services required"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Automatic Cleanup"
                        secondary="Expired entries are automatically removed"
                    />
                </ListItem>
            </List>

            <Callout type="warning" title="File Permissions">
                The <code>storage/cache</code> directory is created automatically when needed. Ensure the parent <code>storage/</code> directory is writable by your web server.
                BaseAPI needs write permissions to store and manage cache files.
            </Callout>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Redis Driver (Production)
            </Typography>

            <Typography>
                High-performance distributed caching with Redis:
            </Typography>

            <CodeBlock language="bash" code={`# .env for production with Redis
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=your_redis_password
REDIS_CACHE_DB=1
CACHE_RESPONSES=true`} />

            <List>
                <ListItem>
                    <ListItemText
                        primary="Distributed Caching"
                        secondary="Share cache between multiple application servers"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="High Performance"
                        secondary="In-memory storage with optional persistence"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Advanced Features"
                        secondary="Atomic operations, pub/sub, and complex data structures"
                    />
                </ListItem>
            </List>


            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Response Caching Configuration
            </Typography>

            <Typography>
                Cache entire HTTP responses for maximum performance:
            </Typography>

            <CodeBlock language="bash" code={`# Enable response caching
CACHE_RESPONSES=true
CACHE_RESPONSE_TTL=600  # 10 minutes default for responses

# Disable in development
CACHE_RESPONSES=false`} />

            <Typography>
                Use response caching middleware in your routes:
            </Typography>

            <CodeBlock language="php" code={`<?php
// routes/api.php

use BaseApi\\Cache\\Middleware\\CacheResponse;

// Cache responses for 5 minutes
$router->get('/api/posts', [
    CacheResponse::class => ['ttl' => 300],
    PostController::class
]);

// Cache with tags for easy invalidation
$router->get('/api/user/{id}', [
    CacheResponse::class => ['ttl' => 600, 'tags' => ['users']],
    UserController::class
]);`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Cache Management Commands
            </Typography>

            <Typography>
                BaseAPI provides powerful CLI commands for cache management and monitoring:
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                cache:stats - Cache Statistics
            </Typography>

            <Typography>
                Display detailed statistics for all configured cache drivers:
            </Typography>

            <CodeBlock language="bash" code={`# Show stats for all drivers
./mason cache:stats

# Show stats for specific driver
./mason cache:stats redis
./mason cache:stats file`} />

            <Typography sx={{ mt: 2 }}>
                The command provides driver-specific metrics:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Array Driver"
                        secondary="Total/Active/Expired items count, estimated memory usage (calculated by serializing all items)"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="File Driver"
                        secondary="Total/Active/Expired file count, total disk size in bytes, scan of cache directory"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Redis Driver"
                        secondary="Connected clients, memory usage, cache hit rate (hits vs misses), total commands processed"
                    />
                </ListItem>
            </List>

            <Callout type="info" title="Use Cases for cache:stats">
                Monitor cache memory consumption • Identify expired entries needing cleanup • 
                Debug cache hit rates in production • Verify Redis connection health • 
                Performance tuning based on actual usage patterns
            </Callout>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                cache:clear - Flush Cache
            </Typography>

            <Typography>
                Completely empty cache stores, removing all entries regardless of expiration:
            </Typography>

            <CodeBlock language="bash" code={`# Clear all configured cache drivers
./mason cache:clear

# Clear specific driver only
./mason cache:clear array
./mason cache:clear file
./mason cache:clear redis

# Clear by tags (tagged cache entries only)
./mason cache:clear --tags=users,posts`} />

            <Typography sx={{ mt: 2 }}>
                Important behaviors:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Error Resilience"
                        secondary="The command continues processing all drivers even if one fails, tracking successes and failures"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Driver-Specific Clearing"
                        secondary="Array: instant memory clear • File: glob + unlink (can be slow with many files) • Redis: KEYS pattern + DEL or FLUSHDB"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Redis Safety"
                        secondary="If prefix is set, only keys matching prefix are deleted. Without prefix, FLUSHDB drops the entire Redis database"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Exit Codes"
                        secondary="Returns exit code 1 if any driver fails, useful for CI/CD pipelines"
                    />
                </ListItem>
            </List>

            <Alert severity="warning" sx={{ my: 3 }}>
                <strong>Production Warning:</strong> cache:clear removes ALL cache entries, forcing expensive 
                recomputations. Use cache:cleanup instead to remove only expired entries, or use tagged clearing 
                for selective invalidation.
            </Alert>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                cache:cleanup - Garbage Collection
            </Typography>

            <Typography>
                Remove only expired cache entries, preserving valid cached data:
            </Typography>

            <CodeBlock language="bash" code={`# Clean up all drivers
./mason cache:cleanup

# Clean up specific driver
./mason cache:cleanup file
./mason cache:cleanup array

# Schedule automatic cleanup (crontab example)
0 */6 * * * cd /path/to/app && ./mason cache:cleanup`} />

            <Typography sx={{ mt: 2 }}>
                Understanding lazy expiration:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Lazy Expiration Model"
                        secondary="Cache entries aren't automatically removed when TTL expires. On get(), expired entries return null and are deleted. cleanup() proactively removes all expired entries."
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Array Store Cleanup"
                        secondary="Iterates in-memory storage, compares expires_at timestamp with current time, unsets expired entries"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="File Store Cleanup"
                        secondary="Uses glob() to find cache files, reads and deserializes each, checks expiration, unlinks expired files. Also removes corrupted files."
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Redis Store Cleanup"
                        secondary="Redis handles expiration automatically via EXPIRE/SETEX. Expired keys are purged by Redis background job. Returns 0 (no manual cleanup needed)."
                    />
                </ListItem>
            </List>

            <Callout type="success" title="Cleanup vs Clear">
                <strong>cache:cleanup</strong> is selective and non-destructive - only removes expired entries, 
                keeps valid cache, safe for production cron jobs.
                <br /><br />
                <strong>cache:clear</strong> is complete flush - removes everything, requires cache rebuilding, 
                use for deployments or data structure changes.
            </Callout>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Production Recommendations
            </Typography>

            <CodeBlock language="bash" code={`# Add to crontab for automatic maintenance
# Cleanup expired entries every 6 hours
0 */6 * * * cd /var/www/api && ./mason cache:cleanup

# Clear all cache on deployment (after code update)
./mason cache:clear

# Monitor cache health daily
0 9 * * * cd /var/www/api && ./mason cache:stats > /var/log/cache-stats.log`} />

            <Typography sx={{ mt: 2 }}>
                Command best practices:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Development"
                        secondary="Run cache:clear between tests to ensure fresh state"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Staging"
                        secondary="Use cache:stats to monitor hit rates and tune TTL values before production"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Production"
                        secondary="Schedule cache:cleanup as cron job to prevent disk/memory bloat. Run cache:clear after deployments."
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="CI/CD Pipeline"
                        secondary="Clear cache as part of deployment script: ./mason cache:clear || true (ignore failures)"
                    />
                </ListItem>
            </List>


            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Performance Tuning
            </Typography>

            <Typography>
                Optimize caching for your specific use case:
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Development Settings
            </Typography>

            <CodeBlock language="bash" code={`# Development - minimal caching for fresh data
CACHE_DRIVER=array
CACHE_RESPONSES=false
CACHE_DEFAULT_TTL=60`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Production Settings
            </Typography>

            <CodeBlock language="bash" code={`# Production - aggressive caching for performance
CACHE_DRIVER=redis
CACHE_RESPONSES=true
CACHE_RESPONSE_TTL=1800    # 30 minutes for responses
CACHE_DEFAULT_TTL=3600     # 1 hour default`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                High-Traffic Settings
            </Typography>

            <CodeBlock language="bash" code={`# High-traffic - maximum caching
CACHE_DRIVER=redis
CACHE_RESPONSES=true
CACHE_RESPONSE_TTL=7200    # 2 hours for responses
CACHE_DEFAULT_TTL=14400    # 4 hours default

# Redis optimizations
REDIS_PASSWORD=strong_password
REDIS_CACHE_DB=2           # Dedicated database for cache`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Troubleshooting Cache Issues
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Cache Not Working"
                        secondary="Check CACHE_DRIVER is set and storage/ directory is writable"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Redis Connection Issues"
                        secondary="Verify REDIS_HOST, REDIS_PORT, and authentication settings"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Stale Data Issues"
                        secondary="Use cache:clear command or reduce TTL values"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Memory Issues"
                        secondary="Monitor Redis memory usage and configure eviction policies"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Performance Not Improved"
                        secondary="Profile your application and ensure response caching is applied to expensive operations"
                    />
                </ListItem>
            </List>

            <Alert severity="success" sx={{ mt: 4 }}>
                <strong>Best Practices:</strong>
                <br />• Use Redis for production deployments
                <br />• Enable response caching for frequently accessed endpoints
                <br />• Set appropriate TTL values based on data volatility
                <br />• Monitor cache hit rates and adjust strategies
                <br />• Clear cache after deployments
            </Alert>
        </Box>
    );
}

