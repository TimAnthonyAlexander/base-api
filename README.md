# BaseAPI

<img src="baseapi.png" alt="BaseAPI Logo" width="200"/>

A tiny, KISS-first PHP 8.4+ framework for building REST APIs.

BaseAPI is designed to get out of your way and let you build APIs quickly and efficiently.
It provides all the essential tools you need while maintaining simplicity and performance.

[Documentation Here](https://baseapi.timanthonyalexander.de)

## ✨ Features

- **Low Configuration** - Works out of the box with sensible defaults
- **High Performance** - Minimal overhead, maximum speed (<1ms overhead per request)
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

Check out the [Documentation](https://baseapi.timanthonyalexander.de) for detailed usage instructions.

## Security

BaseAPI includes security features out of the box:

- **CORS handling** - Configurable cross-origin resource sharing
- **Rate limiting** - Prevent API abuse with customizable limits
- **Input validation** - Automatic request validation based on model types
- **SQL injection protection** - Parameterized queries and ORM
- **Session management** - Secure session handling

## Performance

- **Minimal overhead** - Framework adds < 1ms to request time (measured on MacBook Pro M3 Pro)
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
