# BaseAPI

A tiny, KISS-first PHP 8.4+ framework for building REST APIs.

BaseAPI is designed to get out of your way and let you build APIs quickly and efficiently.
It provides all the essential tools you need while maintaining simplicity and performance.

## ✨ Features

- **Low Configuration** - Works out of the box with sensible defaults
- **High Performance** - Minimal overhead, maximum speed (<0.01ms overhead per request)
- **Built-in Security** - CORS, rate limiting, and authentication middlewares included
- **Database Agnostic** - Automatic migrations from model definitions, supports MySQL, SQLite, PostgreSQL
- **Internationalization** - Full i18n support with multiple automatic translation providers (OpenAI, DeepL)
- **Auto Documentation** - Generate OpenAPI specs and TypeScript types with one command

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

This is the full configuration as of v0.3.11. More options may be added in future releases.

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
- **Efficient routing** - Fast route matching and caching
- **Database optimization** - Query builder with automatic optimization
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
