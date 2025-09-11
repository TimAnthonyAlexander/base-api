# Database Abstraction Implementation

This document describes the database abstraction layer implemented in BaseAPI, which makes the framework agnostic to database types.

## Overview

BaseAPI has been enhanced with a database driver abstraction layer that allows it to work with multiple database systems. The framework now supports:

- **MySQL** (existing support, refactored)
- **SQLite** (newly added)
- **Extensible architecture** for adding more databases (PostgreSQL, etc.)

## Architecture

### Core Components

1. **DatabaseDriverInterface** - Defines the contract for database drivers
2. **DatabaseDriverFactory** - Creates driver instances based on configuration
3. **MySqlDriver** - MySQL-specific implementation
4. **SqliteDriver** - SQLite-specific implementation

### Key Files

- `src/Database/Drivers/DatabaseDriverInterface.php` - Interface definition
- `src/Database/Drivers/DatabaseDriverFactory.php` - Factory for creating drivers
- `src/Database/Drivers/MySqlDriver.php` - MySQL driver implementation
- `src/Database/Drivers/SqliteDriver.php` - SQLite driver implementation
- `src/Database/Connection.php` - Updated to use driver abstraction
- `src/Database/Migrations/` - All migration classes updated to use drivers

## Configuration

### MySQL Configuration

```env
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=my_database
DB_USER=root
DB_PASSWORD=secret
DB_CHARSET=utf8mb4
DB_PERSISTENT=false
```

### SQLite Configuration

```env
DB_DRIVER=sqlite
DB_NAME=database.sqlite
# For in-memory database:
# DB_NAME=:memory:
```

## Usage

The abstraction is completely transparent to application code. Models, queries, and migrations work identically regardless of the database backend.

### Switching Databases

Simply change the `DB_DRIVER` environment variable and update the relevant connection parameters.

### Model Definition

Models remain unchanged:

```php
<?php
namespace App\Models;

use BaseApi\Models\BaseModel;

class Product extends BaseModel
{
    public string $id;
    public string $name;
    public float $price;
    public bool $active = true;
    public \DateTime $created_at;
}
```

### Migration Generation

The migration system automatically generates database-appropriate SQL:

```bash
# Works with any configured database
php bin/console migrate:generate
php bin/console migrate:apply
```

## Database-Specific Differences

### Type Mappings

| PHP Type | MySQL | SQLite |
|----------|-------|--------|
| `string` | `VARCHAR(255)` | `TEXT` |
| `int` | `INT` | `INTEGER` |
| `float` | `DOUBLE` | `REAL` |
| `bool` | `TINYINT(1)` | `INTEGER` |
| `DateTime` | `DATETIME` | `TEXT` |

### SQL Syntax

The drivers handle database-specific SQL syntax:

- **MySQL**: Uses backticks for identifiers, supports `ENGINE=InnoDB`
- **SQLite**: Uses double quotes for identifiers, simpler syntax

### Feature Support

| Feature | MySQL | SQLite | Notes |
|---------|-------|--------|-------|
| Foreign Keys | ✅ | ✅ | SQLite requires PRAGMA foreign_keys=ON |
| Indexes | ✅ | ✅ | Full support |
| ALTER COLUMN | ✅ | ❌ | SQLite requires table recreation |
| DROP COLUMN | ✅ | ⚠️ | SQLite 3.35.0+ only |

## Testing

Comprehensive tests ensure compatibility:

- Unit tests for each driver
- Integration tests for migration system
- Type mapping verification
- SQL generation testing

Run tests with:

```bash
composer test
```

## Adding New Database Drivers

To add support for a new database:

1. Create a driver class implementing `DatabaseDriverInterface`
2. Add the driver to `DatabaseDriverFactory`
3. Implement database-specific methods:
   - Connection creation
   - Schema introspection
   - SQL generation
   - Type mapping

Example structure:

```php
class PostgreSqlDriver implements DatabaseDriverInterface
{
    public function getName(): string { return 'postgresql'; }
    public function createConnection(array $config): PDO { /* ... */ }
    public function generateSql(MigrationPlan $plan): array { /* ... */ }
    // ... other methods
}
```

## Limitations

### SQLite Limitations

- No `ALTER COLUMN` support (requires table recreation)
- Limited `DROP COLUMN` support (version dependent)
- Foreign keys defined at table creation time

### Performance Considerations

- SQLite is file-based, suitable for development/small applications
- MySQL recommended for production with high concurrency
- Connection pooling not implemented (single connection per request)

## Migration Path

For existing BaseAPI applications:

1. No code changes required
2. Add `DB_DRIVER=mysql` to `.env` to maintain current behavior
3. Optionally switch to SQLite for development/testing

## Future Enhancements

Planned improvements:

- PostgreSQL driver
- Connection pooling
- Database-specific optimizations
- Advanced migration features (table recreation for SQLite)
- Query builder enhancements for database-specific features
