<?php

/**
 * Cache System Usage Examples
 * 
 * This file demonstrates how to use the unified caching system in BaseAPI.
 */

use BaseApi\Cache\Cache;
use BaseApi\Models\User; // Example model

// === Basic Cache Operations ===

// Store a value with TTL
Cache::put('user.123', ['name' => 'John', 'email' => 'john@example.com'], 3600);

// Retrieve a value
$user = Cache::get('user.123');

// Use remember pattern (get or execute callback)
$expensiveData = Cache::remember('expensive.calculation', 600, function() {
    // This will only execute if cache miss
    return performExpensiveCalculation();
});

// Store permanently
Cache::forever('app.settings', ['theme' => 'dark', 'lang' => 'en']);

// Check if key exists
if (Cache::has('user.123')) {
    // Do something
}

// Remove a key
Cache::forget('user.123');

// === Different Cache Drivers ===

// Use specific drivers
Cache::driver('redis')->put('session.data', $sessionData, 1800);
Cache::driver('file')->put('cached.view', $htmlContent, 300);
Cache::driver('array')->put('temp.data', $tempData, 60);

// === Tagged Cache for Invalidation ===

// Store with tags
Cache::tags(['users', 'profiles'])->put('user.profile.123', $profileData, 3600);

// Store multiple items with same tags
Cache::tags(['posts', 'published'])->putMany([
    'recent.posts' => $recentPosts,
    'featured.posts' => $featuredPosts,
    'post.count' => count($allPosts)
], 900);

// Invalidate by tags (removes all cached items with these tags)
Cache::tags(['users'])->flush();

// === Model Query Caching ===

// Cache query results automatically
$activeUsers = User::where('active', '=', true)->cache(300)->get();

// Cache with custom key
$user = User::find($userId)->cache(600, "user.{$userId}");

// Cache with tags for easy invalidation
$publishedPosts = Post::where('status', '=', 'published')
    ->cacheWithTags(['posts', 'published'], 600)
    ->get();

// Disable caching for specific query
$adminUsers = User::where('role', '=', 'admin')->noCache()->get();

// Use model's cached method with auto-tagging
$cachedUsers = User::cached(300)->where('active', '=', true)->get();

// === Counter Operations ===

// Increment/decrement values
$pageViews = Cache::increment('page.views');
$remainingSlots = Cache::decrement('available.slots', 5);

// === Bulk Operations ===

// Get multiple keys
$data = Cache::many(['user.123', 'user.456', 'user.789']);

// Store multiple keys
Cache::putMany([
    'metric.cpu' => 45.2,
    'metric.memory' => 78.5,
    'metric.disk' => 23.1
], 60);

// === Cache Management ===

// Get cache statistics
$stats = Cache::stats('redis');
// $stats contains driver statistics

// Clean up expired entries
$removed = Cache::cleanup('file');
echo "Removed {$removed} expired entries\n";

// Clear all cache
Cache::flush();

// Clear specific driver
Cache::driver('redis')->flush();

// === Response Caching (Middleware Usage) ===

// In route definitions:
// Route::get('/api/posts', [PostController::class, 'index'])
//      ->middleware('cache:300'); // Cache for 5 minutes

// Route::get('/api/user/{id}', [UserController::class, 'show'])
//      ->middleware('cache:600,user'); // Cache with 'user' tag

// === Helper Functions ===

function performExpensiveCalculation() {
    // Simulate expensive operation
    sleep(2);
    return [
        'result' => 42,
        'computed_at' => date('Y-m-d H:i:s'),
        'data' => range(1, 1000)
    ];
}

// === Configuration Examples ===

// The cache system is configured via config/cache.php:
/*
return [
    'default' => 'file',
    'stores' => [
        'array' => ['driver' => 'array'],
        'file' => [
            'driver' => 'file',
            'path' => storage_path('cache'),
        ],
        'redis' => [
            'driver' => 'redis',
            'host' => '127.0.0.1',
            'port' => 6379,
            'database' => 1,
        ],
    ],
];
*/

// === CLI Commands ===

// Clear all cache:
// php bin/console cache:clear

// Clear specific driver:
// php bin/console cache:clear redis

// Clear by tags:
// php bin/console cache:clear --tags=users,posts

// Show cache statistics:
// php bin/console cache:stats

// Clean up expired entries:
// php bin/console cache:cleanup
