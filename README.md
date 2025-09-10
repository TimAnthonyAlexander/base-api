# BaseAPI (v2)

A tiny, KISS-first PHP 8.4 framework for building JSON-first APIs with almost no ceremony.

[![PHP Version](https://img.shields.io/badge/PHP-8.4+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## Philosophy

BaseAPI embraces **Keep It Simple, Stupid (KISS)** principles:

- **Explicit routes** via simple `$app->get()/post()/delete()` array DSL
- **Small homegrown** Request/Response with typed properties
- **Controllers** that declare public typed properties (auto-bound from route/query/body/files)
- **Optional mini validation** without heavy abstractions
- **Responses wrapped** in a `.data` envelope for consistency
- **No magic relations** or heavy PSR stacks - everything is readable and explicit

## Features

### 🚀 **Core HTTP Pipeline**
- Minimal global middleware pipeline (error handler, request ID, CORS, JSON parsing, sessions)
- Explicit route definitions with middleware support
- Request/Response handling with automatic property binding
- Built-in validation with clear error messages

### 🛡️ **Security & Rate Limiting**
- Per-route rate limiting with file-based counters
- CORS allowlist with proper headers
- Session management with secure defaults
- Client IP detection with proxy support

### 🗄️ **Database Layer**
- Single PDO MySQL connection with UTC timezone
- Chainable QueryBuilder with parameterized queries
- BaseModel with UUIDv7 IDs and ActiveRecord pattern
- Simple hydration and JSON serialization

### 🔧 **Developer Experience**
- Zero-dependency CLI for serving and scaffolding
- File upload handling (public/private buckets)
- Health endpoint with optional database checks
- Comprehensive error handling with request IDs

## Quick Start

### Installation

```bash
git clone https://github.com/timanthonyalexander/base-api.git
cd base-api
composer install
cp .env.example .env
```

### Configuration

Edit your `.env` file with your settings:

```env
# App Configuration
APP_NAME=BaseApi
APP_ENV=local
APP_DEBUG=true
APP_HOST=localhost
APP_PORT=8000

# CORS Configuration
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000

# Database Configuration (optional)
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=baseapi
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
DB_PERSISTENT=false

# Rate Limiting
RATE_LIMIT_DIR=storage/ratelimits
APP_TRUST_PROXY=false
```

### Start the Server

```bash
# Using the built-in CLI
php bin/console serve

# Or using PHP's built-in server directly
php -S localhost:8000 -t public public/router.php
```

Visit `http://localhost:8000/health` to verify everything is working.

## Usage Examples

### Creating Controllers

```bash
# Generate a new controller
php bin/console make:controller User

# This creates app/Controllers/UserController.php
```

```php
<?php

namespace BaseApi\Controllers;

class UserController extends Controller
{
    // Auto-bound from route parameters, query string, or request body
    public string $id = '';
    public string $name = '';
    public string $email = '';

    public function get(): array
    {
        if ($this->id) {
            // GET /users/123
            return ['user' => User::find($this->id)];
        }
        
        // GET /users
        return ['users' => User::all()];
    }

    public function post(): array
    {
        // POST /users with JSON body
        $user = new User();
        $user->name = $this->name;
        $user->email = $this->email;
        $user->save();

        return ['user' => $user];
    }
}
```

### Defining Routes

```php
// routes/api.php
use BaseApi\App;
use BaseApi\Controllers\UserController;
use BaseApi\Http\Middleware\RateLimitMiddleware;

$router = App::router();

// Simple routes
$router->get('/users', [UserController::class]);
$router->post('/users', [UserController::class]);
$router->get('/users/{id}', [UserController::class]);

// Routes with middleware
$router->get('/api/data', [
    RateLimitMiddleware::class => ['limit' => '100/1h'],
    DataController::class
]);
```

### Working with Models

```bash
# Generate a model
php bin/console make:model Post
```

```php
<?php

namespace BaseApi\Models;

class Post extends BaseModel
{
    public string $title = '';
    public string $content = '';
    public string $author_id = '';
    
    // Table name auto-inferred as 'posts'
    // Or override: protected static ?string $table = 'custom_posts';
}
```

```php
// Using the model
$post = new Post();
$post->title = 'Hello World';
$post->content = 'This is my first post';
$post->save(); // Auto-generates UUIDv7 ID

// Querying
$post = Post::find('uuid-here');
$posts = Post::where('author_id', '=', $userId)->get();
$recent = Post::all(limit: 10);
```

### Database Queries

```php
use BaseApi\App;

// Using the QueryBuilder
$users = App::db()->qb()
    ->table('users')
    ->select(['id', 'name', 'email'])
    ->where('active', '=', 1)
    ->where('created_at', '>', '2024-01-01')
    ->whereIn('role', ['admin', 'editor'])
    ->orderBy('name', 'asc')
    ->limit(50)
    ->get();

// Raw queries
$result = App::db()->raw('SELECT COUNT(*) as total FROM users WHERE active = ?', [1]);
$count = App::db()->scalar('SELECT COUNT(*) FROM users WHERE active = ?', [1]);
$affected = App::db()->exec('UPDATE users SET last_login = NOW() WHERE id = ?', [$userId]);
```

### Request Validation

```php
use BaseApi\Http\Validation\Validator;

class UserController extends Controller
{
    public string $name = '';
    public string $email = '';
    
    public function post(): array
    {
        // Validate request data
        Validator::make([
            'name' => $this->name,
            'email' => $this->email,
        ], [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
        ])->validate();

        // Create user...
    }
}
```

### File Uploads

```php
class UploadController extends Controller
{
    public array $files = [];
    
    public function post(): array
    {
        foreach ($this->files as $file) {
            // Upload to public directory
            $path = $file->storePublic('uploads');
            
            // Or private directory
            $path = $file->storePrivate('documents');
        }
        
        return ['uploaded' => count($this->files)];
    }
}
```

## API Response Format

All API responses are wrapped in a consistent format:

### Success Response
```json
{
    "data": {
        "user": {
            "id": "0199308f-d328-7902-b4ed-73ee4d0fc11d",
            "name": "John Doe",
            "email": "john@example.com"
        }
    }
}
```

### Error Response
```json
{
    "error": "Validation failed",
    "message": "The email field is required.",
    "requestId": "0199308f-d328-7902-b4ed-73ee4d0fc11d",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

## Rate Limiting

BaseAPI includes built-in rate limiting with file-based storage:

```php
// In routes/api.php
$router->get('/api/endpoint', [
    RateLimitMiddleware::class => ['limit' => '60/1m'],  // 60 requests per minute
    ApiController::class
]);

// Supported formats:
// '100/1h'  - 100 requests per hour
// '1000/1d' - 1000 requests per day
// '10/10s'  - 10 requests per 10 seconds
```

Rate limit headers are automatically included:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1640995200
Retry-After: 15  (on 429 responses)
```

## Model-Driven Migrations

BaseAPI features a powerful model-driven migration system that generates database schema changes from your model definitions:

### Basic Migration Workflow

```bash
# 1. Edit your models (add/remove/modify public typed properties)
# 2. Generate migration plan
php bin/console migrate:generate

# 3. Review the generated plan in storage/migrations.json
# 4. Apply migrations to database
php bin/console migrate:apply

# Or apply safely (skip destructive changes)
php bin/console migrate:apply --safe
```

### Model Schema Inference

BaseAPI automatically infers database schema from your model properties:

```php
class User extends BaseModel
{
    public string $id = '';           // CHAR(36) PRIMARY KEY
    public string $name = '';         // VARCHAR(255) NOT NULL
    public ?string $email = null;     // VARCHAR(255) NULL
    public int $age = 0;              // INT NOT NULL
    public bool $active = true;       // BOOLEAN NOT NULL
    public ?string $created_at = null; // DATETIME DEFAULT CURRENT_TIMESTAMP
    public ?string $updated_at = null; // DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    
    // Foreign key inference
    public ?Project $project = null;  // Creates project_id CHAR(36) + FK to projects(id)
    
    // Index definitions
    public static array $indexes = [
        'email' => 'unique',
        'created_at' => 'index'
    ];
    
    // Column overrides
    public static array $columns = [
        'name' => ['type' => 'VARCHAR(120)', 'null' => false],
        'description' => ['type' => 'TEXT']
    ];
}
```

### Migration Plan Format

Generated plans are stored as JSON in `storage/migrations.json`:

```json
{
  "generated_at": "2025-09-10T15:07:00Z",
  "plan": [
    {"op": "create_table", "table": "users", "columns": [...], "destructive": false},
    {"op": "add_column", "table": "users", "column": {...}, "destructive": false},
    {"op": "add_unique", "table": "users", "index": {...}, "destructive": false},
    {"op": "drop_column", "table": "users", "column": "old_field", "destructive": true}
  ]
}
```

## Health Checks

BaseAPI provides built-in health check endpoints:

```bash
# Basic health check
curl http://localhost:8000/health
# Response: {"data": {"ok": true}}

# Health check with database verification
curl http://localhost:8000/health?db=1  
# Response: {"data": {"ok": true, "db": true}}
```

## CLI Commands

BaseAPI includes a simple CLI for common tasks:

```bash
# Start the development server
php bin/console serve

# Generate a controller
php bin/console make:controller ProductController

# Generate a model  
php bin/console make:model Product

# Generate migration plan from model changes
php bin/console migrate:generate

# Apply migration plan to database
php bin/console migrate:apply

# Apply migration plan (skip destructive changes)
php bin/console migrate:apply --safe

# Show available commands
php bin/console
```

## Directory Structure

```
baseapi/
├── app/
│   ├── Console/           # CLI commands
│   ├── Controllers/       # HTTP controllers
│   ├── Database/         # Database layer
│   ├── Http/             # HTTP layer (middleware, requests, responses)
│   ├── Models/           # Data models
│   └── Support/          # Utilities (UUID, ClientIP, etc.)
├── bin/
│   └── console           # CLI entry point
├── config/
│   └── app.php          # Configuration defaults
├── public/
│   ├── index.php        # Application entry point
│   └── router.php       # Development router
├── routes/
│   └── api.php          # Route definitions
├── storage/
│   ├── logs/            # Application logs
│   ├── ratelimits/      # Rate limiting data
│   └── uploads/         # File uploads
└── vendor/              # Composer dependencies
```

## Database Schema Conventions

BaseAPI follows simple conventions for database schemas:

### Model Tables
```sql
CREATE TABLE users (
    id CHAR(36) PRIMARY KEY,           -- UUIDv7
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Key Conventions
- **Primary Keys**: UUIDv7 stored as `CHAR(36)`
- **Timestamps**: `created_at` and `updated_at` as `DATETIME` (UTC)
- **Table Names**: Plural snake_case (e.g., `user_posts` for `UserPost` model)
- **Foreign Keys**: `{model}_id` format (e.g., `user_id`)

## Testing

BaseAPI is designed to be easily testable:

```php
// Example test structure
class UserControllerTest extends TestCase
{
    public function test_creates_user()
    {
        $response = $this->post('/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email']
            ]
        ]);
    }
}
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Requirements

- **PHP 8.4+**
- **MySQL 5.7+** (for database features)
- **Composer**

## License

BaseAPI is open-sourced software licensed under the [MIT license](LICENSE).

## Roadmap

BaseAPI is built in milestones:

- ✅ **Milestone 1**: Core HTTP pipeline with middleware
- ✅ **Milestone 2**: Request/response handling, validation, file uploads  
- ✅ **Milestone 3**: CLI tools and rate limiting
- ✅ **Milestone 4**: Database layer with QueryBuilder and BaseModel
- ✅ **Milestone 5**: Model-driven migrations and schema management
- ✅ **Milestone 6**: Relations + Eager Helpers + Pagination/Sort/Filter

---

**BaseAPI** - Because sometimes you just want to build an API without the ceremony. 🚀
