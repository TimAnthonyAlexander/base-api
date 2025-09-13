# Profiler Usage Examples

The BaseAPI profiler allows you to measure execution time of code segments during development.

## Enabling Profiling

Profiling is only available in local/development environment (`app.env = 'local'`) and must be enabled by adding `?profiling=true` to your request URL.

## Basic Usage

### 1. Manual Start/Stop

```php
<?php

use BaseApi\App;
use BaseApi\Controllers\Controller;
use BaseApi\Http\JsonResponse;

class ExampleController extends Controller
{
    public function index(): JsonResponse
    {
        // Start a profiling span
        $spanId = App::profiler()->start('database_query', ['table' => 'users']);
        
        // Your code here
        $users = $this->simulateSlowDatabaseQuery();
        
        // Stop the span
        App::profiler()->stop($spanId);
        
        // Start another span
        $processSpanId = App::profiler()->start('data_processing');
        
        $processedUsers = $this->processUsers($users);
        
        App::profiler()->stop($processSpanId);
        
        return JsonResponse::ok($processedUsers);
    }
    
    private function simulateSlowDatabaseQuery(): array
    {
        usleep(50000); // 50ms delay
        return [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];
    }
    
    private function processUsers(array $users): array
    {
        usleep(25000); // 25ms delay
        return array_map(fn($user) => [
            'id' => $user['id'],
            'name' => strtoupper($user['name'])
        ], $users);
    }
}
```

### 2. Using the Profile Helper Method

```php
<?php

use BaseApi\App;
use BaseApi\Controllers\Controller;
use BaseApi\Http\JsonResponse;

class ExampleController extends Controller
{
    public function index(): JsonResponse
    {
        // Profile a callable
        $users = App::profiler()->profile('database_query', function() {
            return $this->simulateSlowDatabaseQuery();
        }, ['table' => 'users']);
        
        $processedUsers = App::profiler()->profile('data_processing', function() use ($users) {
            return $this->processUsers($users);
        });
        
        return JsonResponse::ok($processedUsers);
    }
}
```

## Example Response

When you make a request with `?profiling=true`, the response will include profiling data:

```json
{
    "data": [
        {"id": 1, "name": "JOHN"},
        {"id": 2, "name": "JANE"}
    ],
    "responseTimeMs": 82,
    "profiling": {
        "total_spans": 3,
        "total_time_ms": 78.234,
        "spans": [
            {
                "name": "http_request",
                "duration_ms": 78.234,
                "metadata": {
                    "method": "GET",
                    "path": "/api/users"
                }
            },
            {
                "name": "database_query",
                "duration_ms": 52.145,
                "metadata": {
                    "table": "users"
                }
            },
            {
                "name": "data_processing",
                "duration_ms": 26.089,
                "metadata": {}
            }
        ]
    }
}
```

## Nested Profiling

You can nest profiling spans to get more detailed insights:

```php
public function complexOperation(): JsonResponse
{
    $mainSpanId = App::profiler()->start('complex_operation');
    
    $step1SpanId = App::profiler()->start('step_1');
    // Step 1 code
    App::profiler()->stop($step1SpanId);
    
    $step2SpanId = App::profiler()->start('step_2');
    // Step 2 code
    App::profiler()->stop($step2SpanId);
    
    App::profiler()->stop($mainSpanId);
    
    return JsonResponse::ok(['status' => 'completed']);
}
```

## Best Practices

1. **Use descriptive names**: Make span names clear and meaningful
2. **Add metadata**: Include relevant context in the metadata array
3. **Profile meaningful operations**: Focus on database queries, API calls, heavy computations
4. **Don't over-profile**: Too many small spans can add overhead
5. **Remember to stop spans**: Always pair `start()` with `stop()` or use the `profile()` helper
