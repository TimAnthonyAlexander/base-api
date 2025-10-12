
import { Box, Typography, Alert } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';

export default function CachingApiReference() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Cache API Reference
            </Typography>
            <Typography variant="h5" color="text.secondary" paragraph>
                Complete API reference for BaseAPI's caching system
            </Typography>

            <Typography>
                BaseAPI provides a comprehensive caching API that supports multiple drivers, tagged caching,
                and both programmatic and model-level caching. This reference covers all available methods
                and their usage based on the actual implementation.
            </Typography>

            <Alert severity="info" sx={{ my: 3 }}>
                Access the cache through the <code>Cache</code> facade using static methods, or inject <code>CacheInterface</code>
                into your services via the container. Model caching uses tagged invalidation for automatic cache clearing.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Basic Cache Operations
            </Typography>

            <CodeBlock language="php" code={`<?php

use BaseApi\\Cache\\Cache;

// Basic operations using Cache facade
Cache::put('user:123', $userData, 3600); // Store for 1 hour  
$user = Cache::get('user:123'); // Retrieve
$exists = Cache::has('user:123'); // Check existence
Cache::forget('user:123'); // Remove
Cache::flush(); // Clear all

// Alternative: through dependency injection
$cache = App::container()->make(CacheInterface::class);
$user = $cache->get('user:123');`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Advanced Methods
            </Typography>

            <CodeBlock language="php" code={`<?php

// Remember pattern - get from cache or compute and store
$expensiveData = Cache::remember('expensive_calc', 3600, function() {
    return performExpensiveCalculation();
});

// Store forever (no expiration)
Cache::forever('app_version', '1.0.0');

// Atomic counters
Cache::increment('page_views'); 
Cache::decrement('page_views', 2);

// Additional operations
Cache::add('unique_key', 'value', 300); // Store only if key doesn't exist
$value = Cache::pull('temp_data'); // Get and remove
Cache::putMany(['key1' => 'value1', 'key2' => 'value2'], 300); // Store multiple`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Tagged Caching
            </Typography>

            <Typography>
                Tagged caching allows efficient invalidation of related cache entries:
            </Typography>

            <CodeBlock language="php" code={`<?php

// Store cache entries with tags
Cache::tags(['users', 'profile'])->put('user:123:profile', $profile, 3600);
Cache::tags(['posts'])->put('featured_posts', $posts, 1800);

// Invalidate all entries with specific tags
Cache::tags(['users'])->flush(); // Clears all user-related cache

// Tagged remember pattern
$userPosts = Cache::tags(['users', 'posts'])->remember('user:123:posts', 300, function() {
    return getUserPosts(123);
});`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Model-Level Caching
            </Typography>

            <Typography>
                BaseAPI models include built-in caching with automatic invalidation:
            </Typography>

            <CodeBlock language="php" code={`<?php

// Cache model queries with default TTL (300 seconds)
$users = User::cached()->where('active', true)->get();

// Cache with custom TTL
$activeUsers = User::cached(3600)->where('status', 'active')->get();

// Cache is automatically invalidated when models change
$user = User::find('user-123');
$user->name = 'Updated Name';
$user->save(); // Automatically clears related cache entries tagged with this model

// Manual cache key generation for complex queries
$cacheKey = Cache::key('users', 'active', serialize($filters));
$users = Cache::remember($cacheKey, 300, function() use ($filters) {
    return User::where($filters)->get();
});`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Driver Management
            </Typography>

            <CodeBlock language="php" code={`<?php

// Access cache manager through container  
$cacheManager = App::container()->make(CacheManager::class);

// Use specific drivers
$redisCache = Cache::driver('redis');
$fileCache = Cache::driver('file'); 
$arrayCache = Cache::driver('array');

// Get cache statistics and cleanup
$stats = Cache::stats('redis'); // Get stats for specific driver
$removedCount = Cache::cleanup('file'); // Clean expired entries

// Cache manager operations
$cacheManager->purge('redis'); // Clear and recreate driver instance
$drivers = $cacheManager->getStoreNames(); // Get all configured drivers`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Configuration & Multiple Operations
            </Typography>

            <CodeBlock language="php" code={`<?php

// Configuration is handled via .env and config/app.php
// CACHE_DRIVER=redis (or 'file', 'array')
// REDIS_HOST=127.0.0.1
// REDIS_PORT=6379
// REDIS_CACHE_DB=0

// Multiple key operations
$values = Cache::many(['user:123', 'user:456', 'settings']);
Cache::putMany([
    'user:123' => $userData,
    'settings' => $appSettings
], 3600);

// Cache key helpers
$complexKey = Cache::key('user', $userId, 'permissions', $roleId);
// Generates: "user:123:permissions:456"

// Extended cache driver with custom logic
Cache::extend('custom', function($config) {
    return new CustomCacheDriver($config);
});`} />

            <Alert severity="success" sx={{ mt: 4 }}>
                <strong>Best Practices:</strong>
                <br />• Use the <code>Cache</code> facade for simple operations
                <br />• Inject <code>CacheInterface</code> for services requiring testability  
                <br />• Use tagged caching for related data that should be invalidated together
                <br />• Set appropriate TTL values based on data freshness requirements
                <br />• Use model caching with <code>Model::cached()</code> for database queries
                <br />• Handle cache failures gracefully with fallback logic
            </Alert>
        </Box>
    );
}

