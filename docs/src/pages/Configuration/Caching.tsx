
import { Box, Typography, Alert, List, ListItem, ListItemText, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

export default function Caching() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Caching Configuration
      </Typography>
      <Typography variant="h5" color="text.secondary" paragraph>
        Configure BaseAPI's unified caching system for optimal performance
      </Typography>

      <Typography paragraph>
        BaseAPI includes a powerful unified caching system that supports multiple drivers, 
        tagged cache invalidation, and automatic model query caching. Proper caching configuration 
        can dramatically improve your API's performance.
      </Typography>

      <Alert severity="info" sx={{ my: 3 }}>
        BaseAPI's caching system provides 10x+ performance improvements for database queries 
        and can cache HTTP responses, computed values, and more.
      </Alert>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Environment Configuration
      </Typography>

      <Typography paragraph>
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

# Enable query result caching for models
CACHE_QUERIES=true
CACHE_QUERY_TTL=300

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

      <Typography paragraph>
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

      <Typography paragraph>
        In-memory caching that's perfect for development and testing:
      </Typography>

      <CodeBlock language="bash" code={`# .env for development
CACHE_DRIVER=array
CACHE_QUERIES=true
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

      <Typography paragraph>
        File-based caching that persists between requests:
      </Typography>

      <CodeBlock language="bash" code={`# .env for single server deployment
CACHE_DRIVER=file
CACHE_PATH=storage/cache  # Optional custom path
CACHE_QUERIES=true
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

      <Typography paragraph>
        High-performance distributed caching with Redis:
      </Typography>

      <CodeBlock language="bash" code={`# .env for production with Redis
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=your_redis_password
REDIS_CACHE_DB=1
CACHE_QUERIES=true
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
        Query Caching Configuration
      </Typography>

      <Typography paragraph>
        Automatically cache database query results to reduce database load:
      </Typography>

      <CodeBlock language="bash" code={`# Enable query caching
CACHE_QUERIES=true
CACHE_QUERY_TTL=300  # 5 minutes default TTL for queries

# Disable for development if you want fresh data
CACHE_QUERIES=false`} />

      <Typography paragraph>
        Query caching works automatically with BaseAPI models:
      </Typography>

      <CodeBlock language="php" code={`<?php

// This query result will be cached for 5 minutes (CACHE_QUERY_TTL)
$users = User::where('active', '=', true)->cache()->get();

// Custom TTL for specific queries
$products = Product::where('featured', '=', true)->cache(600)->get();

// Cache with tags for easy invalidation
$posts = Post::where('published', '=', true)
    ->cacheWithTags(['posts', 'published'], 900)
    ->get();

// Use convenience method with auto-tagging
$cachedUsers = User::cached(300)->where('active', '=', true)->get();`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Response Caching Configuration
      </Typography>

      <Typography paragraph>
        Cache entire HTTP responses for maximum performance:
      </Typography>

      <CodeBlock language="bash" code={`# Enable response caching
CACHE_RESPONSES=true
CACHE_RESPONSE_TTL=600  # 10 minutes default for responses

# Disable in development
CACHE_RESPONSES=false`} />

      <Typography paragraph>
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

      <Typography paragraph>
        BaseAPI provides CLI commands for cache management:
      </Typography>

      <CodeBlock language="bash" code={`# Clear all cache entries
php bin/console cache:clear

# Clear specific driver cache
php bin/console cache:clear file
php bin/console cache:clear redis

# Show cache statistics
php bin/console cache:stats
php bin/console cache:stats redis

# Clean up expired entries (file driver only)
php bin/console cache:cleanup
php bin/console cache:cleanup file`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Tagged Cache Configuration
      </Typography>

      <Typography paragraph>
        Tags allow you to group cache entries for bulk invalidation:
      </Typography>

      <CodeBlock language="php" code={`<?php

// Store data with tags
Cache::tags(['users', 'profiles'])->put('user.profile.123', $data, 3600);

// Invalidate all entries with specific tags
Cache::tags(['users'])->flush(); // Clears all user-related cache

// Model cache is automatically tagged
$user = User::find($id); // Automatically tagged with 'model:users'
$user->save(); // Automatically invalidates related cache`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Performance Tuning
      </Typography>

      <Typography paragraph>
        Optimize caching for your specific use case:
      </Typography>

      <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
        Development Settings
      </Typography>

      <CodeBlock language="bash" code={`# Development - minimal caching for fresh data
CACHE_DRIVER=array
CACHE_QUERIES=false
CACHE_RESPONSES=false
CACHE_DEFAULT_TTL=60`} />

      <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
        Production Settings
      </Typography>

      <CodeBlock language="bash" code={`# Production - aggressive caching for performance
CACHE_DRIVER=redis
CACHE_QUERIES=true
CACHE_QUERY_TTL=900        # 15 minutes for queries
CACHE_RESPONSES=true
CACHE_RESPONSE_TTL=1800    # 30 minutes for responses
CACHE_DEFAULT_TTL=3600     # 1 hour default`} />

      <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
        High-Traffic Settings
      </Typography>

      <CodeBlock language="bash" code={`# High-traffic - maximum caching
CACHE_DRIVER=redis
CACHE_QUERIES=true
CACHE_QUERY_TTL=3600       # 1 hour for queries
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
            secondary="Profile your queries and ensure caching is applied to expensive operations"
          />
        </ListItem>
      </List>

      <Alert severity="success" sx={{ mt: 4 }}>
        <strong>Best Practices:</strong>
        <br />• Use Redis for production deployments
        <br />• Enable query caching for database-heavy operations
        <br />• Set appropriate TTL values based on data volatility
        <br />• Use tags for logical cache grouping
        <br />• Monitor cache hit rates and adjust strategies
        <br />• Clear cache after deployments
      </Alert>
    </Box>
  );
}