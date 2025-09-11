# BaseAPI

A tiny, KISS-first PHP 8.4 framework for building JSON-first APIs.

BaseAPI is designed to get out of your way and let you build APIs quickly and efficiently. It provides all the essential tools you need while maintaining simplicity and performance.

## ✨ Features

- **🚀 Zero Configuration** - Works out of the box with sensible defaults
- **⚡ High Performance** - Minimal overhead, maximum speed
- **🔒 Built-in Security** - CORS, rate limiting, and authentication middleware
- **📊 Database First** - Automatic migrations from model definitions
- **🌐 Internationalization** - Full i18n support with multiple translation providers
- **📝 Auto Documentation** - Generate OpenAPI specs and TypeScript types
- **🎯 Type Safe** - Full PHP 8.4 type support with automatic validation
- **📱 Modern Development** - Hot reloading dev server and comprehensive CLI tools

## 🚀 Quick Start

### Create a New Project

The fastest way to get started is using the BaseAPI template:

```bash
composer create-project baseapi/baseapi-template my-api
cd my-api
```

### Manual Installation

If you prefer to add BaseAPI to an existing project:

```bash
composer require timanthonyalexander/base-api
```

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

### 5. Define Routes

Add routes to `routes/api.php`:

```php
<?php

use BaseApi\Router;
use App\Controllers\ProductController;

$router = app()->router();

$router->get('/products', [ProductController::class]);
$router->post('/products', [ProductController::class]);
$router->get('/products/{id}', [ProductController::class]);
```

## 🛠️ CLI Commands

BaseAPI includes a powerful CLI with the following commands:

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
├── Models/         # Database models
├── Database/       # Database utilities
└── Console/        # CLI commands

routes/
└── api.php         # Route definitions

config/
├── app.php         # Application configuration
└── i18n.php        # Internationalization settings

storage/
├── logs/           # Application logs
└── migrations.json # Migration state
```

## 🔧 Configuration

BaseAPI uses environment variables for configuration. Copy `.env.example` to `.env`:

```bash
cp .env.example .env
```

Key configuration options:

```env
# Application
APP_PORT=7879
APP_DEBUG=true

# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=my_api
DB_USER=root
DB_PASS=

# Features
ENABLE_CORS=true
ENABLE_RATE_LIMITING=true
```

## 🌐 Internationalization

BaseAPI includes comprehensive i18n support:

```php
// In your controllers
use BaseApi\Support\I18n;

class ProductController extends Controller 
{
    public function index(): JsonResponse 
    {
        return JsonResponse::ok([
            'message' => I18n::t('products.list_success'),
            'products' => Product::all()
        ]);
    }
}
```

Add translations with AI assistance:

```bash
# Add German language support
php bin/console i18n:add-lang de --auto

# Fill missing translations using OpenAI or DeepL
php bin/console i18n:fill de --provider=openai
```

## 📚 Documentation Generation

Generate comprehensive API documentation:

```bash
# Generate OpenAPI specification and TypeScript types
php bin/console types:generate --openapi --typescript
```

This creates:
- `docs/openapi.json` - OpenAPI 3.0 specification
- `docs/types.ts` - TypeScript type definitions

## 🔒 Security

BaseAPI includes security features out of the box:

- **CORS handling** - Configurable cross-origin resource sharing
- **Rate limiting** - Prevent API abuse with customizable limits
- **Input validation** - Automatic request validation based on model types
- **SQL injection protection** - Parameterized queries and ORM
- **Session management** - Secure session handling

## 🚀 Performance

- **Minimal overhead** - Framework adds < 1ms to request time
- **Efficient routing** - Fast route matching and caching
- **Database optimization** - Query builder with automatic optimization
- **Memory efficient** - Low memory footprint even with large datasets

## 📦 Ecosystem

BaseAPI works great with:

- **Frontend frameworks** - React, Vue, Angular (with generated TypeScript types)
- **Testing tools** - PHPUnit integration ready
- **Deployment** - Docker, traditional hosting, serverless
- **Databases** - MySQL, PostgreSQL, SQLite

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## 📄 License

BaseAPI is open-sourced software licensed under the [MIT license](LICENSE).

## 🆘 Support

- 📖 [Documentation](https://github.com/timanthonyalexander/base-api/wiki)
- 🐛 [Issue Tracker](https://github.com/timanthonyalexander/base-api/issues)
- 💬 [Discussions](https://github.com/timanthonyalexander/base-api/discussions)

---

**BaseAPI** - The tiny, KISS-first PHP 8.4 framework that gets out of your way.
