# BaseAPI

A tiny, KISS-first PHP 8.4+ framework for building REST APIs.

BaseAPI is designed to get out of your way and let you build APIs quickly and efficiently.
It provides all the essential tools you need while maintaining simplicity and performance.

## ✨ Features

- **Low Configuration** - Works out of the box with sensible defaults
- **High Performance** - Minimal overhead, maximum speed (<0.01ms overhead per request)
- **Built-in Security** - CORS, rate limiting, and authentication middlewares included
- **Database Agnostic** - Automatic migrations from model definitions, supports MySQL, SQLite, PostgreSQL
- **Unified Caching** - Multi-driver caching system with Redis, File, and Array stores plus tagged cache invalidation
- **Internationalization** - Full i18n support with multiple automatic translation providers (OpenAI, DeepL)
- **Auto Documentation** - Generate OpenAPI specs and TypeScript types with one command
- **Dependency Injection** - Built-in DI container with auto-wiring and service providers

## Quick Start

### Create a New Project

In order to set up a new BaseAPI project, run:

```bash
composer create-project baseapi/baseapi-template my-api
cd my-api
```

This will create a new project in the `my-api` directory.
It will contain a User model, and some basic controllers:

```php
app/Controllers/MeController.php
app/Controllers/LoginController.php
app/Controllers/HealthController.php
app/Controllers/SignupController.php
app/Controllers/LogoutController.php
```

From there, you can easily create new models and controllers and immediately start building your API.

## 📖 Usage

### 1. Start the Development Server

```bash
php bin/console serve
```

Your API will be available at `http://localhost:7879`

### 2. Create Your First Model

```bash
php bin/console make:model Product
```

This creates a model in `app/Models/Product.php`:

```php
<?php

namespace App\Models;

use BaseApi\Models\BaseModel;

class Product extends BaseModel
{
    public string $id;
    public string $name;
    public ?string $description = null;
    public float $price;
    public \DateTime $created_at;
}
```

There will be comments in the file to guide you through adding fields and relationships.
There is support for:
- HasMany
- BelongsTo

These definitions will be used to generate migrations and foreign keys automatically.

### 3. Generate and Apply Migrations

```bash
# Generate migration plan from your models
php bin/console migrate:generate

# Apply the migrations to your database
php bin/console migrate:apply
```

### 4. Create a Controller

```bash
php bin/console make:controller ProductController
```

The controller file will also include comments to guide you through adding methods.

### 5. Define Routes

Add/remove routes to/from `routes/api.php`:

```php
<?php

use BaseApi\Router;
use App\Controllers\ProductController;

$router = App::router();

$router->get('/auth/login', [RateLimitMiddleware::class => ['limit' => '60/1m'], LoginController::class]);
# Example of rate limited endpoint, 60 requests per minute

$router->get('/products', [ProductController::class]);
$router->post('/products', [ProductController::class]);
$router->get('/products/{id}', [ProductController::class]);
# Basic CRUD routes for products, support for path parameters
```

## CLI Commands

BaseAPI includes a powerful CLI with the following commands.

Base command:

```bash
php bin/console
```

### Development
- `serve` - Start the development server
- `make:controller <name>` - Generate a new controller
- `make:model <name>` - Generate a new model

### Database
- `migrate:generate` - Generate migration plan from model changes
- `migrate:apply` - Apply migrations to database

### Documentation
- `types:generate` - Generate OpenAPI spec and TypeScript definitions

### Caching
- `cache:clear [driver]` - Clear cache entries (optionally by driver)
- `cache:stats [driver]` - Show cache statistics for all or specific drivers
- `cache:cleanup [driver]` - Clean up expired cache entries

### Internationalization
- `i18n:scan` - Scan codebase for translation tokens
- `i18n:add-lang <locale>` - Add a new language
- `i18n:fill` - Fill missing translations using AI/machine translation
- `i18n:lint` - Validate translation files
- `i18n:hash` - Generate translation bundle hashes

## 🏗️ Architecture

BaseAPI follows a simple, predictable structure:

```
app/
├── Controllers/     # Request handlers
├── Models/          # Database models
routes/
└── api.php          # Route definitions
config/
├── app.php          # Application configuration
├── i18n.php         # Translation configuration, important configuration is in .env
storage/
├── logs/            # Application logs  
├── cache/           # File-based cache storage
├── ratelimits/      # File based rate limiting storage
└── migrations.json  # Migration state
```

## 🔧 Configuration

BaseAPI uses environment variables for configuration. Copy `.env.example` to `.env`:

```bash
cp .env.example .env
```

### Hint: When installed via composer create-project, this is done automatically.

### .env Configuration

```env
########################################
# Application Settings
########################################

# The display name of your application
APP_NAME=BaseApi

# Environment type: local, staging, production
APP_ENV=local

# Enable/disable debug mode (shows detailed error messages)
APP_DEBUG=true

# Base URL and server binding
APP_URL=http://127.0.0.1:7879
APP_HOST=127.0.0.1
APP_PORT=7879


########################################
# CORS (Cross-Origin Resource Sharing)
########################################

# Comma-separated list of allowed origins for API access
# Example: http://localhost:3000,http://127.0.0.1:3000
CORS_ALLOWLIST=http://localhost:3000,http://127.0.0.1:3000
CORS_MAX_AGE=86400

########################################
# Database Configuration
########################################

# Database driver: sqlite, mysql, postgresql
DB_DRIVER=sqlite

# Database name or file (for SQLite, this is a file path)
DB_NAME=database.sqlite


########################################
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
CACHE_QUERIES=false
CACHE_QUERY_TTL=300

# Enable HTTP response caching middleware
CACHE_RESPONSES=false
CACHE_RESPONSE_TTL=600


########################################
# Redis Cache Configuration (if using redis driver)
########################################

# Redis connection details for caching
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_CACHE_DB=1


########################################
# MySQL / PostgreSQL (example settings)
########################################

# Uncomment and adjust if using MySQL or PostgreSQL
# DB_DRIVER=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306        # Use 5432 for PostgreSQL
# DB_NAME=baseapi
# DB_USER=root        # Use a dedicated non-root user in production
# DB_PASSWORD=secret
```

This is the full configuration as of v0.4.0 with the unified caching system. More options may be added in future releases.

## 🌐 Internationalization

Translations are separated into namespaces within the `translations/{language}` directories.
The language token consists of the namespace, a dot, and the key.
For an admin panel title translation you might have the token `admin.dashboardTitle`,
which would be stored in `translations/en/admin.json`.

With `php bin/console i18n:fill` you can automatically fill missing translations in other languages than the default using OpenAI or DeepL.
This way you only have to mainting the default (English) translations and the rest can be generated automatically.

With `php bin/console i18n:scan` you can scan your codebase for translation tokens and add them to the default language file.

These are the i18n commands:

```bash
  i18n:scan    Scan codebase for translation tokens
  i18n:add-lang    Add new language(s) to the translation system
  i18n:fill    Fill missing translations using machine translation
  i18n:lint    Lint translation files for errors and inconsistencies
  i18n:hash    Generate hash for translation bundles
```

You can use these translations in your controllers and modules like this:

```php

class ProductController extends Controller 
{
    public function index(): JsonResponse 
    {
        return JsonResponse::ok([
            'message' => I18n::t('products.list_success'),
            'products' => Product::all(),
        ]);
    }
}
```

## 🚀 Caching

BaseAPI includes a powerful unified caching system that supports multiple drivers, tagged cache invalidation, and automatic model query caching to dramatically improve application performance.

### Cache Drivers

BaseAPI supports multiple cache drivers for different deployment scenarios:

- **Array** - In-memory caching for development and testing
- **File** - File-based caching for single-server deployments  
- **Redis** - Distributed caching for production multi-server setups

### Basic Cache Operations

```php
use BaseApi\Cache\Cache;

// Store a value with TTL (time-to-live in seconds)
Cache::put('user.123', $userData, 300);

// Retrieve a value
$user = Cache::get('user.123');

// Get or store pattern (executes callback only on cache miss)
$expensiveData = Cache::remember('heavy.calculation', 600, function() {
    return performExpensiveOperation();
});

// Store permanently (no expiration)
Cache::forever('app.settings', ['theme' => 'dark']);

// Check if key exists
if (Cache::has('user.123')) {
    // Key exists and is not expired
}

// Remove a key
Cache::forget('user.123');

// Clear all cache
Cache::flush();
```

### Multiple Drivers

Use specific cache drivers for different types of data:

```php
// Use Redis for sessions (distributed)
Cache::driver('redis')->put('session.abc123', $sessionData, 1800);

// Use file cache for views (persistent across requests)
Cache::driver('file')->put('view.cached', $htmlContent, 3600);

// Use array cache for temporary data (current request only)
Cache::driver('array')->put('temp.data', $tempData, 60);
```

### Model Query Caching

Automatically cache database query results to reduce database load:

```php
// Cache query results for 5 minutes
$activeUsers = User::where('active', '=', true)->cache(300)->get();

// Cache with custom key
$user = User::find($userId)->cache(600, "user.{$userId}");

// Cache with tags for easy invalidation
$publishedPosts = Post::where('status', '=', 'published')
    ->cacheWithTags(['posts', 'published'], 600)
    ->get();

// Disable caching for specific query
$adminUsers = User::where('role', '=', 'admin')->noCache()->get();

// Use convenience method with auto-tagging
$cachedUsers = User::cached(300)->where('active', '=', true)->get();
```

### Tagged Cache & Invalidation

Group cache entries with tags for bulk invalidation:

```php
// Store data with tags
Cache::tags(['users', 'profiles'])->put('user.profile.123', $profileData, 3600);

// Store multiple items with same tags
Cache::tags(['posts', 'published'])->putMany([
    'recent.posts' => $recentPosts,
    'featured.posts' => $featuredPosts,
    'post.count' => count($allPosts)
], 900);

// Invalidate all cache entries with specific tags
Cache::tags(['users'])->flush(); // Removes all user-related cache
Cache::tags(['posts', 'published'])->flush(); // Removes all post cache
```

### Automatic Cache Invalidation

Model cache is automatically invalidated when data changes:

```php
// This query result gets cached
$user = User::find(123)->cache(600);

// When you save the user, related cache is automatically cleared
$user->name = 'Updated Name';
$user->save(); // Cache invalidated automatically

// Same with delete operations
$user->delete(); // All related cache cleared
```

### Counter Operations

Use cache for atomic counter operations:

```php
// Increment/decrement values
$pageViews = Cache::increment('page.views');
$remainingSlots = Cache::decrement('available.slots', 5);

// Get current value and increment atomically
$previousViews = Cache::getAndIncrement('page.views');
```

### Bulk Operations

Efficiently handle multiple cache operations:

```php
// Get multiple keys at once
$data = Cache::many(['user.123', 'user.456', 'user.789']);

// Store multiple keys at once
Cache::putMany([
    'metric.cpu' => 45.2,
    'metric.memory' => 78.5,
    'metric.disk' => 23.1
], 60);

// Store only if key doesn't exist
$wasStored = Cache::add('unique.key', 'value', 300);
```

### Cache Management

Monitor and manage your cache:

```php
// Get cache statistics
$stats = Cache::stats('redis');

// Clean up expired entries
$removed = Cache::cleanup('file');

// Check cache driver status
if (Cache::driver('redis')->getStore()->ping()) {
    // Redis is available
}
```

### HTTP Response Caching

Cache entire HTTP responses using middleware:

```php
// In your routes file
use BaseApi\Cache\Middleware\CacheResponse;

// Cache responses for 5 minutes
$router->get('/api/posts', [PostController::class])
    ->middleware([CacheResponse::class => ['ttl' => 300]]);

// Cache with tags for easy invalidation  
$router->get('/api/user/{id}', [UserController::class])
    ->middleware([CacheResponse::class => ['ttl' => 600, 'tags' => ['users']]]);
```

### Cache Configuration

Configure caching in your `.env` file:

```env
# Choose your cache driver
CACHE_DRIVER=redis  # or 'file' or 'array'

# Set cache key prefix (useful for shared environments)
CACHE_PREFIX=myapp_cache

# Configure default TTL
CACHE_DEFAULT_TTL=3600

# Enable automatic model query caching
CACHE_QUERIES=true
CACHE_QUERY_TTL=300

# Enable HTTP response caching
CACHE_RESPONSES=true
CACHE_RESPONSE_TTL=600

# Redis configuration (if using redis driver)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_CACHE_DB=1
```

### Performance Benefits

The caching system provides significant performance improvements:

- **10x+ faster queries** - Cached query results eliminate database round trips
- **Reduced server load** - Cache responses and expensive computations
- **Scalable architecture** - Redis support for multi-server deployments
- **Smart invalidation** - Tagged cache prevents stale data issues
- **Memory efficient** - Intelligent cleanup and TTL management

## Dependency Injection

BaseAPI includes a powerful dependency injection container that automatically resolves dependencies and manages service lifecycles.

### Basic Usage

Controllers and middleware automatically receive dependencies through constructor injection:

```php
<?php

namespace App\Controllers;

use App\Services\EmailService;
use BaseApi\Controllers\Controller;
use BaseApi\Http\JsonResponse;

class UserController extends Controller
{
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function post(): JsonResponse
    {
        // Use the injected service
        $this->emailService->sendWelcome($this->email, $this->name);
        
        return JsonResponse::ok(['message' => 'User created']);
    }
}
```

### Service Providers

Organize service registration using service providers:

```php
<?php

namespace App\Providers;

use BaseApi\Container\ServiceProvider;
use BaseApi\Container\ContainerInterface;
use App\Services\EmailService;
use App\Services\PaymentService;

class AppServiceProvider extends ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        // Register as singleton
        $container->singleton(EmailService::class);
        
        // Register with custom configuration
        $container->singleton(PaymentService::class, function ($c) {
            return new PaymentService($c->make(ApiClient::class));
        });
    }

    public function boot(ContainerInterface $container): void
    {
        // Configure services after all are registered
        $emailService = $container->make(EmailService::class);
        $emailService->setDefaultFrom('noreply@example.com');
    }
}
```

Register providers in `config/app.php`:

```php
return [
    'providers' => [
        \App\Providers\AppServiceProvider::class,
    ],
];
```

### Container Methods

Access the container directly when needed:

```php
// In controllers
$service = $this->make(SomeService::class);
$container = $this->container();

// Globally
$container = \BaseApi\App::container();
$service = $container->make(SomeService::class);

// Bind services
$container->bind(ServiceInterface::class, ConcreteService::class);
$container->singleton(ExpensiveService::class);
$container->instance(ConfigService::class, $configInstance);
```

### Auto-wiring

The container automatically resolves dependencies based on type hints:

```php
class EmailService
{
    public function __construct(
        private Logger $logger,
        private Config $config,
        private DatabaseConnection $db
    ) {
        // Dependencies automatically injected
    }
}
```

## Documentation (OpenAPI & TypeScript)

Generate comprehensive API documentation:

```bash
# Generate OpenAPI specification and TypeScript types
php bin/console types:generate --openapi --typescript
```

This creates:
- `/openapi.json` - OpenAPI 3.0 specification
- `/types.ts` - TypeScript type definitions for models and endpoints

## Security

BaseAPI includes security features out of the box:

- **CORS handling** - Configurable cross-origin resource sharing
- **Rate limiting** - Prevent API abuse with customizable limits
- **Input validation** - Automatic request validation based on model types
- **SQL injection protection** - Parameterized queries and ORM
- **Session management** - Secure session handling

## Performance

- **Minimal overhead** - Framework adds < 0.01ms to request time (measured on MacBook Pro M3 Pro)
- **Unified caching** - Multi-driver cache system with Redis support for 10x+ query performance
- **Efficient routing** - Fast route matching and caching
- **Database optimization** - Query builder with automatic optimization and query result caching
- **Memory efficient** - Low memory footprint even with large datasets

## Compatibility; should I use BaseAPI?

BaseAPI works great with:

- **Frontend frameworks** - React, Vue, Angular (with generated TypeScript types)
- **Mobile apps** - iOS, Android (with generated OpenAPI spec)
- **Testing tools** - PHPUnit integration ready
- **Deployment** - Docker, traditional hosting, serverless
- **Databases** - MySQL, SQLite, PostgreSQL

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

BaseAPI is open-sourced software licensed under the [MIT license](LICENSE).

## Found a bug?

- [Issue Tracker](https://github.com/timanthonyalexander/base-api/issues)
