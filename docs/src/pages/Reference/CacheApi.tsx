
import { Box, Typography, Alert } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';

export default function CacheAPI() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Cache API Reference
      </Typography>
      <Typography variant="h5" color="text.secondary" paragraph>
        Complete API reference for BaseAPI's caching system
      </Typography>

      <Typography paragraph>
        BaseAPI provides a comprehensive caching API that supports multiple drivers, tagged caching, 
        and both programmatic and model-level caching. This reference covers all available methods 
        and their usage based on the actual implementation.
      </Typography>

      <Alert severity="info" sx={{ my: 3 }}>
        Access the cache through <code>App::cache()</code> or inject <code>CacheInterface</code> 
        into your services. Model caching is automatic and uses tagged invalidation.
      </Alert>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Basic Cache Operations
      </Typography>

      <CodeBlock language="php" code={`<?php

use BaseApi\\App;

$cache = App::cache();

// Basic operations
$cache->put('user:123', $userData, 3600); // Store for 1 hour
$user = $cache->get('user:123'); // Retrieve
$exists = $cache->has('user:123'); // Check existence
$cache->forget('user:123'); // Remove
$cache->flush(); // Clear all`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Advanced Methods
      </Typography>

      <CodeBlock language="php" code={`<?php

// Remember pattern - get from cache or compute and store
$expensiveData = $cache->remember('expensive_calc', 3600, function() {
    return performExpensiveCalculation();
});

// Store forever (no expiration)
$cache->forever('app_version', '1.0.0');

// Atomic counters
$cache->increment('page_views'); 
$cache->decrement('page_views', 2);`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Tagged Caching
      </Typography>

      <Typography paragraph>
        Tagged caching allows efficient invalidation of related cache entries:
      </Typography>

      <CodeBlock language="php" code={`<?php

// Store cache entries with tags
$cache->tags(['users', 'profile'])->put('user:123:profile', $profile, 3600);
$cache->tags(['posts'])->put('featured_posts', $posts, 1800);

// Invalidate all entries with specific tags
$cache->tags(['users'])->flush(); // Clears all user-related cache`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Model-Level Caching
      </Typography>

      <Typography paragraph>
        BaseAPI models include built-in caching with automatic invalidation:
      </Typography>

      <CodeBlock language="php" code={`<?php

// Cache model queries automatically
$users = User::cached(3600)->where('active', true)->get();

// Cache is automatically invalidated when models change
$user = User::find('user-123');
$user->name = 'Updated Name';
$user->save(); // Automatically clears related cache entries`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Driver Management
      </Typography>

      <CodeBlock language="php" code={`<?php

$cacheManager = App::cache();

// Use specific drivers
$redisCache = $cacheManager->driver('redis');
$fileCache = $cacheManager->driver('file');
$arrayCache = $cacheManager->driver('array');

// Get cache statistics (if supported)
$stats = $cache->getStats();
$removedCount = $cache->cleanup(); // Clean expired entries`} />

      <Alert severity="success" sx={{ mt: 4 }}>
        <strong>Best Practices:</strong>
        <br />• Use tagged caching for related data
        <br />• Set appropriate TTL values 
        <br />• Use atomic operations for counters
        <br />• Handle cache failures gracefully
      </Alert>
    </Box>
  );
}