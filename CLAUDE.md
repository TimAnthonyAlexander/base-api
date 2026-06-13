# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

BaseAPI is a KISS-first PHP 8.4+ framework for building JSON-first REST APIs. It's a Composer library (`timanthonyalexander/base-api`) with namespace `BaseApi\` under `src/`. The CLI tool is called **Mason** (`./mason`).

## Commands

```bash
# Tests
composer phpunit                    # Run all tests
composer test-unit                  # Run tests with TestDox output
vendor/bin/phpunit tests/RouterTest.php  # Run a single test file
vendor/bin/phpunit --filter testMethodName  # Run a single test method

# Static analysis (PHPStan level 6)
composer phpstan

# Code modernization (Rector for PHP 8.4+)
composer rector                     # Dry-run check
composer rector:fix                 # Apply changes

# Dev server
./mason serve                       # Starts on port 7879

# Migrations
./mason migrate:generate            # Generate migrations from model definitions
./mason migrate:apply               # Apply pending migrations

# Code generation
./mason make:model <Name>
./mason make:controller <Name>
./mason job:make <Name>

# Route management
./mason route:list                   # List registered routes
./mason route:cache                  # Compile routes for production
./mason route:clear                  # Clear route cache
```

## Architecture

### Request Lifecycle

```
Request → Kernel → Middleware Stack → ControllerBinder → Controller Method → Response
```

`Kernel` (`src/Http/Kernel.php`) orchestrates the pipeline. `ControllerBinder` (`src/Http/Binding/ControllerBinder.php`) maps route params, query params, body data, and uploaded files to public controller properties with automatic type coercion and camelCase/snake_case conversion.

### App (Service Locator)

`App` (`src/App.php`) is the central service locator with static accessors: `App::boot()`, `App::config()`, `App::router()`, `App::db()`, `App::container()`, `App::queue()`, `App::logger()`, `App::kernel()`, `App::profiler()`. It loads `.env` via Dotenv, merges `config/defaults.php` with the app's `config/app.php`, and registers service providers.

### Routing

Routes are registered on the `Router` (`src/Router.php`) with a pipeline array containing middleware classes and a controller:

```php
$router->get('/products', [ProductController::class]);
$router->post('/products', [RateLimitMiddleware::class => ['limit' => '10/1m'], ProductController::class]);
$router->put('/products/{id}', [[ProductController::class, 'updateProduct']]);
```

- Static routes use O(1) hash lookup; dynamic routes use segment-count-based matching
- Controllers can use convention methods (`get()`, `post()`, `put()`, `delete()`, `patch()`) or custom methods via array syntax `[ControllerClass::class, 'methodName']`
- Route compilation (`RouteCompiler` in `src/Routing/`) serializes routes to pure PHP arrays for opcache

### Models (ActiveRecord)

`BaseModel` (`src/Models/BaseModel.php`) uses typed public properties as the schema definition. Properties auto-map to database columns. Every model gets `id` (UUID), `created_at`, and `updated_at` automatically.

- Table name defaults to snake_case of class name (override via `static $table`)
- Change tracking via `$__row` (original row data)
- Relations: `HasMany`, `BelongsTo` (in `src/Database/Relations/`)
- Querying: `Product::find($id)`, `Product::where('col', '>', val)->get()`, `Product::paginate($page, $perPage)`
- `ModelQuery` (`src/Database/ModelQuery.php`) extends `QueryBuilder` with model hydration

### Database Layer

- `Connection` wraps PDO; `DB` is the facade; `QueryBuilder` provides fluent queries
- Driver abstraction: `MySqlDriver`, `PostgreSqlDriver`, `SqliteDriver` in `src/Database/Drivers/`
- Auto migrations: `ModelScanner` reads model properties → `DiffEngine` compares with DB schema → `SqlGenerator` produces DDL

### Middleware

Implements `Middleware` interface (`src/Http/Middleware.php`): `handle(Request $req, callable $next): Response`. Configurable middleware implements `OptionedMiddleware` interface. Built-in middleware is in `src/Http/` (top-level) and `src/Http/Middleware/`.

### DI Container

`Container` (`src/Container/Container.php`) with `bind()`, `singleton()`, `instance()`, auto-wiring via constructor type hints, and circular dependency detection. Service providers extend `ServiceProvider` with `register()` and `boot()` methods.

### Responses

`JsonResponse` provides static factory methods: `::ok()`, `::created()`, `::badRequest()`, `::unauthorized()`, `::notFound()`, `::noContent()`. Also supports `StreamedResponse` and `BinaryResponse`.

### Other Subsystems

- **Cache**: `src/Cache/` — Manager pattern with ArrayStore, FileStore, RedisStore; tagged cache support
- **Queue**: `src/Queue/` — `JobInterface` with `handle()`/`failed()` methods; SyncQueueDriver and DatabaseQueueDriver
- **Validation**: `src/Http/Validation/` — rule syntax like `'required|string|min:3'`; custom closures supported
- **OpenAPI**: `src/OpenApi/` — auto-generates OpenAPI 3.0 spec from routes and model attributes
- **Permissions/RBAC**: `src/Permissions/` — group-based permission system with middleware
- **i18n**: `src/Support/Translation/` — translation providers (OpenAI, DeepL), ICU validation
- **Storage**: `src/Storage/` — filesystem abstraction with local driver
- **Console**: `src/Console/` — Mason CLI; commands in `src/Console/Commands/`

## Key Conventions

- PHP 8.4 strict typing throughout; use typed properties, not docblock types
- Controller public properties are auto-bound from request data — they define the controller's input contract
- Models define their schema via typed public properties (no separate migration files to write by hand)
- Configuration uses dot notation: `App::config('database.host')`
- The framework config defaults live in `config/defaults.php`; app config goes in the consuming project's `config/app.php`

## Gotchas / Non-obvious Behaviors

These bit me while building a real app on BaseAPI (~v1.9.4) and aren't obvious from the conventions above.

### Array/JSON casts are read-only — there is NO encode on write (footgun)

`castAttribute()` (`src/Models/BaseModel.php`) json_**decodes** `'array'` and `'json'` casts on read, but the write path (`getInsertData()` / `getUpdateData()`) only converts bool→int — it never json_**encodes**. So a property holding a PHP array is passed straight to the driver and silently coerced to the literal string `"Array"`. The symmetric-looking cast names imply round-tripping that doesn't happen.

Until write-side encoding exists, the safe pattern for JSON-shaped data is:
- Declare the column as `TEXT` via `static $columns` (don't rely on the array cast for storage).
- Store a `?string` property and encode/decode explicitly with accessor methods (`json_encode` on set, `json_decode` on get), guarding empty → `null`.

```php
public ?string $params = null;                              // TEXT column
public static array $columns = ['params' => ['type' => 'TEXT', 'nullable' => true]];
public function paramsArray(): ?array { /* json_decode */ }
public function setParamsArray(?array $v): void { /* json_encode */ }
```

### Schema overrides: `static $columns` and `static $indexes`

Typed properties define the schema, but two static arrays (read by `ModelScanner`) let you override what auto-mapping can't infer — neither is mentioned above:

- `static array $indexes = ['user_id' => 'index', 'token_hash' => 'unique'];` — declare indexes/uniques. Value is the index type; supports composite keys.
- `static array $columns = ['token_hash' => ['type' => 'TEXT', 'length' => 255]];` — override the column DDL (type, length, nullable) when the PHP type isn't enough (e.g. forcing `TEXT`, sizing a varchar).

Both feed `migrate:generate`, so changing them is a schema change that needs a migration.
