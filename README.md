# BaseAPI (v2)

**The tiny, KISS-first PHP 8.4 framework that gets out of your way.**

Build JSON-first APIs with almost no ceremony. No heavy PSR stacks, no DI containers, no magic relations—just **readable, explicit, and strong together**.

[![PHP Version](https://img.shields.io/badge/PHP-8.4+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 🚀 **Quick Start**

**Install BaseAPI in seconds with our one-liner:**

```bash
curl -sSL https://raw.githubusercontent.com/timanthonyalexander/base-api/main/install.sh | bash
```

**Or install to a custom directory:**

```bash
bash <(curl -sSL https://raw.githubusercontent.com/timanthonyalexander/base-api/main/install.sh) my-project
```

**Then start building:**

```bash
cd base-api                 # (or your custom directory)
php bin/console serve       # Start the development server
```

Visit `http://localhost:8000/health` to see your API running! 🎉

---

## 💡 **Why BaseAPI?**

Modern PHP frameworks often feel heavyweight and complex. BaseAPI brings you back to basics:

### **🎯 Simple & Explicit**
- **Explicit routes** via clean `$app->get()/post()/delete()` DSL
- **Typed controllers** with auto-bound properties from requests
- **No magic** - every line of code is readable and predictable

### **⚡ Lightweight & Fast**
- **Tiny footprint** - homegrown HTTP layer without heavy abstractions
- **Zero-dependency CLI** for serving and scaffolding
- **File-based sessions** and rate limiting - no external dependencies

### **🛠️ Developer-First**
- **TypeScript generation** from PHP controllers automatically
- **Model-driven migrations** that diff your code against the database
- **Built-in validation** without complex rule engines
- **ETag & caching** helpers for performance optimization

---

## ✨ **Core Features**

### 🌐 **HTTP Pipeline**
- Clean Request/Response with automatic property binding
- Middleware support (Auth, Rate Limiting, CORS)
- JSON body parsing and consistent error handling
- Built-in request ID tracking

### 🔐 **Security & Authentication**
- Session-based authentication with user providers
- Per-route rate limiting with customizable windows
- CORS allowlist with proper headers
- Client IP detection with proxy support

### 🗄️ **Database Layer**
- PDO MySQL with UUIDv7 primary keys
- Chainable QueryBuilder with parameterized queries
- ActiveRecord-style BaseModel with simple hydration
- Model-driven migrations that generate SQL from your PHP classes

### 🎨 **Developer Experience**
- **TypeScript & OpenAPI generation** from controller annotations
- File upload handling (public/private buckets)
- Health endpoints with optional database checks
- Comprehensive CLI for scaffolding and serving

---

## 🏃‍♂️ **5-Minute Tutorial**

### 1. **Create a Controller**

```bash
php bin/console make:controller UserController
```

```php
<?php
// app/Controllers/UserController.php

namespace BaseApi\Controllers;

class UserController extends Controller
{
    // Auto-bound from route params, query, or JSON body
    public string $id = '';
    public string $name = '';
    public string $email = '';

    public function get(): array
    {
        if ($this->id) {
            return ['user' => User::find($this->id)];
        }
        return ['users' => User::all()];
    }

    public function post(): array
    {
        $user = new User();
        $user->name = $this->name;
        $user->email = $this->email;
        $user->save();
        
        return ['user' => $user];
    }
}
```

### 2. **Define Routes**

```php
// routes/api.php
use BaseApi\App;
use BaseApi\Controllers\UserController;

$router = App::router();

$router->get('/users', [UserController::class]);
$router->post('/users', [UserController::class]);
$router->get('/users/{id}', [UserController::class]);
```

### 3. **Create a Model**

```bash
php bin/console make:model User
```

```php
<?php
// app/Models/User.php

namespace BaseApi\Models;

class User extends BaseModel
{
    public string $name = '';
    public string $email = '';
    
    public static array $indexes = [
        'email' => 'unique'
    ];
}
```

### 4. **Generate Database Schema**

```bash
php bin/console migrate:generate  # Creates migration plan
php bin/console migrate:apply     # Applies to database
```

### 5. **Test Your API**

```bash
# Create a user
curl -X POST http://localhost:8000/users \
  -H "Content-Type: application/json" \
  -d '{"name": "John Doe", "email": "john@example.com"}'

# Get all users
curl http://localhost:8000/users
```

**That's it!** Your API is running with automatic validation, consistent responses, and database persistence.

---

## 🎛️ **Manual Installation**

If you prefer manual setup or want more control:

```bash
git clone https://github.com/timanthonyalexander/base-api.git
cd base-api
composer install
cp .env.example .env
php bin/console serve
```

### Configuration

Edit `.env` with your settings:

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
```

---

## 🎭 **Advanced Features**

### **TypeScript Generation**

BaseAPI automatically generates TypeScript definitions from your controller annotations:

```php
use BaseApi\Http\Attributes\ResponseType;
use BaseApi\Http\Attributes\Tag;

#[Tag('Users')]
class UserController extends Controller
{
    public ?int $id = null;
    public ?int $perPage = 10;
    
    #[ResponseType(['user' => User::class], when: 'single')]
    #[ResponseType(['users' => 'User[]', 'perPage' => 'int'], when: 'list')]
    public function get(): JsonResponse
    {
        // Implementation...
    }
}
```

Generate types and OpenAPI specs:

```bash
php bin/console types:generate --out-ts=types.d.ts --out-openapi=api.json
```

### **Authentication**

Built-in session authentication:

```php
// Protected routes
$router->get('/me', [
    AuthMiddleware::class,
    UserController::class
]);

// Login endpoint
$router->post('/auth/login', [LoginController::class]);
$router->post('/auth/logout', [LogoutController::class]);
```

### **Rate Limiting**

Per-route rate limiting:

```php
$router->get('/api/data', [
    RateLimitMiddleware::class => ['limit' => '60/1m'],
    DataController::class
]);
```

### **File Uploads**

Simple file handling:

```php
class UploadController extends Controller
{
    public array $files = [];
    
    public function post(): array
    {
        foreach ($this->files as $file) {
            $path = $file->storePublic('uploads');
        }
        
        return ['uploaded' => count($this->files)];
    }
}
```

### **Validation**

Built-in validation:

```php
use BaseApi\Http\Validation\Validator;

Validator::make([
    'name' => $this->name,
    'email' => $this->email,
], [
    'name' => 'required|string|max:100',
    'email' => 'required|email|unique:users',
])->validate();
```

### **Caching**

ETag and cache control helpers:

```php
use BaseApi\Http\Caching\CacheHelper;

$response = JsonResponse::ok($data);
$etag = CacheHelper::strongEtag(json_encode($data));

// Return 304 if unchanged
$cached = CacheHelper::notModifiedIfMatches($this->request, $response, $etag);
if ($cached) return $cached;

// Set cache headers
return CacheHelper::cacheControl($response, 300); // 5 minutes
```

---

## 📖 **API Response Format**

All responses follow a consistent envelope pattern:

**Success:**
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

**Error:**
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

---

## 🛠️ **CLI Commands**

BaseAPI includes a comprehensive CLI:

```bash
# Development server
php bin/console serve

# Code generation
php bin/console make:controller ProductController
php bin/console make:model Product

# Type generation
php bin/console types:generate --out-ts=types.d.ts --out-openapi=api.json

# Database migrations
php bin/console migrate:generate    # Plan changes
php bin/console migrate:apply       # Apply to database
php bin/console migrate:apply --safe # Skip destructive changes

# Show all commands
php bin/console
```

---

## 📋 **Requirements**

- **PHP 8.4+**
- **Composer**
- **MySQL 5.7+** (optional, for database features)
- **Git** (for installation)

---

# 📚 **Technical Documentation**

*The sections below contain detailed technical information for advanced usage.*

---

## **Database Queries**

### QueryBuilder

```php
use BaseApi\App;

$users = App::db()->qb()
    ->table('users')
    ->select(['id', 'name', 'email'])
    ->where('active', '=', 1)
    ->where('created_at', '>', '2024-01-01')
    ->whereIn('role', ['admin', 'editor'])
    ->orderBy('name', 'asc')
    ->limit(50)
    ->get();
```

### Raw Queries

```php
$result = App::db()->raw('SELECT COUNT(*) as total FROM users WHERE active = ?', [1]);
$count = App::db()->scalar('SELECT COUNT(*) FROM users WHERE active = ?', [1]);
$affected = App::db()->exec('UPDATE users SET last_login = NOW() WHERE id = ?', [$userId]);
```

## **Model-Driven Migrations**

BaseAPI's migration system generates database schema changes from your model definitions:

### Model Schema Inference

```php
class User extends BaseModel
{
    public string $id = '';           // CHAR(36) PRIMARY KEY
    public string $name = '';         // VARCHAR(255) NOT NULL
    public ?string $email = null;     // VARCHAR(255) NULL
    public int $age = 0;              // INT NOT NULL
    public bool $active = true;       // BOOLEAN NOT NULL
    public ?string $created_at = null; // DATETIME DEFAULT CURRENT_TIMESTAMP
    public ?string $updated_at = null; // DATETIME ON UPDATE CURRENT_TIMESTAMP
    
    // Foreign key inference
    public ?Project $project = null;  // Creates project_id CHAR(36) + FK
    
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

### Migration Workflow

```bash
# 1. Edit your models (add/remove/modify properties)
# 2. Generate migration plan
php bin/console migrate:generate

# 3. Review generated plan in storage/migrations.json
# 4. Apply to database
php bin/console migrate:apply

# Or apply safely (skip destructive changes)
php bin/console migrate:apply --safe
```

### Migration Plan Format

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

## **TypeScript & OpenAPI Generation**

### Generated TypeScript

```typescript
// Generated from controller properties → request interfaces
export interface GetUserRequestQuery {
  id?: number;
  perPage?: number;
}

export interface GetUserRequestPath {
  id: number;
}

// Generated from ResponseType attributes → response types
export type GetUserResponse = Envelope<{user: User}> | Envelope<{users: User[], perPage: number}>;
export type DeleteUserResponse = Envelope<{message: string}>;

// All responses wrapped in data envelope
export type Envelope<T> = { data: T };
```

### ResponseType Attribute Options

```php
// Simple class reference
#[ResponseType(User::class)]

// Array of objects
#[ResponseType('User[]')]

// Inline object shape
#[ResponseType(['message' => 'string', 'count' => 'int'])]

// Multiple response variants
#[ResponseType(User::class, when: 'found')]
#[ResponseType(['error' => 'string'], status: 404, when: 'not_found')]

// Different status codes  
#[ResponseType(['user' => User::class], status: 201)]
```

### Tag Organization

```php
#[Tag('Authentication', 'Users')]  // Multiple tags
class AuthController extends Controller { /* ... */ }

#[Tag('Admin')]  // Method-level override
#[ResponseType(['users' => 'User[]'])]
public function adminUsers(): JsonResponse { /* ... */ }
```

## **Database Schema Conventions**

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
- **Table Names**: Plural snake_case (e.g., `user_posts` for `UserPost`)
- **Foreign Keys**: `{model}_id` format (e.g., `user_id`)

## **Directory Structure**

```
baseapi/
├── app/
│   ├── Auth/             # Authentication system
│   │   ├── UserProvider.php        # User resolution contract
│   │   └── SimpleUserProvider.php  # Default DB-backed provider
│   ├── Console/          # CLI commands
│   ├── Controllers/      # HTTP controllers
│   │   ├── LoginController.php     # Authentication login
│   │   ├── LogoutController.php    # Authentication logout
│   │   └── MeController.php        # Protected user endpoint
│   ├── Database/         # Database layer
│   ├── Http/             # HTTP layer
│   │   ├── Attributes/   # Controller annotations
│   │   ├── Caching/      # Response caching utilities
│   │   ├── Middleware/   # HTTP middleware
│   │   └── ...           # Requests, responses, validation
│   ├── Models/           # Data models
│   ├── Dto/              # Data Transfer Objects
│   └── Support/          # Utilities (UUID, ClientIP, etc.)
├── bin/console           # CLI entry point
├── config/app.php        # Configuration defaults
├── public/index.php      # Application entry point
├── routes/api.php        # Route definitions
├── storage/              # Logs, rate limits, uploads
└── vendor/               # Composer dependencies
```

## **Health Checks**

```bash
# Basic health check
curl http://localhost:8000/health
# Response: {"data": {"ok": true}}

# Health check with database verification
curl http://localhost:8000/health?db=1  
# Response: {"data": {"ok": true, "db": true}}
```

## **Authentication Flow**

### Endpoints

```bash
# Login
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"userId": "user123"}'

# Access protected endpoint
curl -X GET http://localhost:8000/me \
  -H "Cookie: PHPSESSID=session_id"

# Logout
curl -X POST http://localhost:8000/auth/logout \
  -H "Cookie: PHPSESSID=session_id"
```

### User Providers

Resolve authenticated users through pluggable providers:

```php
use BaseApi\Auth\UserProvider;
use BaseApi\Auth\SimpleUserProvider;

$userProvider = App::userProvider();
$user = $userProvider->byId($userId); // Returns ['id' => '...', 'email' => '...']
```

## **Rate Limiting**

### Configuration

```php
$router->get('/api/endpoint', [
    RateLimitMiddleware::class => ['limit' => '60/1m'],  // 60 per minute
    ApiController::class
]);

// Supported formats:
// '100/1h'  - 100 requests per hour
// '1000/1d' - 1000 requests per day
// '10/10s'  - 10 requests per 10 seconds
```

### Headers

Rate limit headers are automatically included:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1640995200
Retry-After: 15  (on 429 responses)
```

## **Testing**

BaseAPI is designed to be easily testable:

```php
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

---

## 🤝 **Contributing**

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 **License**

BaseAPI is open-sourced software licensed under the [MIT license](LICENSE).

## 🗺️ **Roadmap**

- ✅ **Milestone 1**: Core HTTP pipeline with middleware
- ✅ **Milestone 2**: Request/response handling, validation, file uploads  
- ✅ **Milestone 3**: CLI tools and rate limiting
- ✅ **Milestone 4**: Database layer with QueryBuilder and BaseModel
- ✅ **Milestone 5**: Model-driven migrations and schema management
- ✅ **Milestone 6**: Relations + Eager Helpers + Pagination/Sort/Filter
- ✅ **Milestone 7**: Session Auth + UserProvider + Login/Logout + Caching
- ✅ **Milestone 7.5**: ResponseType Attributes + TypeScript & OpenAPI Generation

---

**BaseAPI** - Because sometimes you just want to build an API without the ceremony. 🚀