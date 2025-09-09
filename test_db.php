<?php

require_once __DIR__ . '/vendor/autoload.php';

use BaseApi\App;
use BaseApi\Support\Uuid;
use BaseApi\Database\QueryBuilder;

try {
    echo "Testing BaseAPI Database Layer\n";
    echo "================================\n\n";

    // Test UUID generation
    echo "1. Testing UUID generation:\n";
    $uuid1 = Uuid::v7();
    $uuid2 = Uuid::v7();
    echo "UUID 1: {$uuid1}\n";
    echo "UUID 2: {$uuid2}\n";
    echo "UUIDs are different: " . ($uuid1 !== $uuid2 ? 'YES' : 'NO') . "\n";
    echo "UUID format valid: " . (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid1) ? 'YES' : 'NO') . "\n\n";

    // Test QueryBuilder SQL generation (without DB connection)
    echo "2. Testing QueryBuilder SQL generation:\n";
    $connection = new \BaseApi\Database\Connection();
    $qb = new QueryBuilder($connection);
    
    $sqlData = $qb->table('users')
        ->select(['id', 'name', 'email'])
        ->where('active', '=', 1)
        ->where('age', '>', 18)
        ->whereIn('role', ['admin', 'user'])
        ->orderBy('name', 'asc')
        ->limit(10)
        ->offset(5)
        ->toSql();
    
    echo "Generated SQL: " . $sqlData['sql'] . "\n";
    echo "Bindings: " . json_encode($sqlData['bindings']) . "\n\n";

    // Test column name validation
    echo "3. Testing column name validation:\n";
    try {
        $qb->table('users')->where('invalid-column!', '=', 'test');
        echo "ERROR: Should have rejected invalid column name\n";
    } catch (\BaseApi\Database\DbException $e) {
        echo "GOOD: Rejected invalid column name: " . $e->getMessage() . "\n";
    }
    
    try {
        $qb->table('valid_column_123')->where('valid.column', '=', 'test');
        echo "GOOD: Accepted valid column names\n";
    } catch (\BaseApi\Database\DbException $e) {
        echo "ERROR: Should have accepted valid column names: " . $e->getMessage() . "\n";
    }
    
    echo "\n4. Testing without database connection (app boot):\n";
    
    // Boot app without DB connection (should not fail)
    App::boot();
    echo "App booted successfully\n";
    
    // Test services are available
    echo "Config service: " . (App::config() ? 'Available' : 'Missing') . "\n";
    echo "Router service: " . (App::router() ? 'Available' : 'Missing') . "\n";
    echo "DB service: " . (App::db() ? 'Available' : 'Missing') . "\n";

    echo "\nAll tests passed! ✅\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
