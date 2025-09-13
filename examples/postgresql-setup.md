# PostgreSQL Setup Guide

This guide will help you set up PostgreSQL with BaseAPI.

## Prerequisites

### 1. Install PostgreSQL

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install postgresql postgresql-contrib
```

**macOS (using Homebrew):**
```bash
brew install postgresql
brew services start postgresql
```

**Windows:**
Download and install from [PostgreSQL official website](https://www.postgresql.org/download/windows/).

### 2. Install PHP PostgreSQL Extension

**Ubuntu/Debian:**
```bash
sudo apt install php-pgsql
```

**macOS (using Homebrew):**
```bash
brew install php
# The pdo_pgsql extension is usually included
```

**Windows:**
Uncomment the following lines in your `php.ini`:
```ini
extension=php_pgsql.dll
extension=php_pdo_pgsql.dll
```

Restart your web server after installation.

## Database Setup

### 1. Create Database and User

Connect to PostgreSQL as the postgres user:
```bash
sudo -u postgres psql
```

Create a database and user for your BaseAPI project:
```sql
-- Create database
CREATE DATABASE baseapi_db;

-- Create user
CREATE USER baseapi_user WITH PASSWORD 'your_secure_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE baseapi_db TO baseapi_user;

-- Grant schema privileges
\c baseapi_db
GRANT ALL ON SCHEMA public TO baseapi_user;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO baseapi_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO baseapi_user;

-- Exit psql
\q
```

### 2. Configure BaseAPI

Update your `.env` file:

```env
# Database Configuration
DB_DRIVER=postgresql
DB_HOST=localhost
DB_PORT=5432
DB_NAME=baseapi_db
DB_USER=baseapi_user
DB_PASSWORD=your_secure_password
DB_CHARSET=utf8
DB_SCHEMA=public

# Optional PostgreSQL-specific settings
DB_SSLMODE=prefer
DB_PERSISTENT=false
```

## Configuration Options

### Connection Parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| `DB_HOST` | `127.0.0.1` | PostgreSQL server hostname |
| `DB_PORT` | `5432` | PostgreSQL server port |
| `DB_NAME` | `baseapi` | Database name |
| `DB_USER` | `postgres` | Database username |
| `DB_PASSWORD` | `` | Database password |
| `DB_CHARSET` | `utf8` | Client encoding |
| `DB_SCHEMA` | `public` | Default schema |
| `DB_SSLMODE` | `prefer` | SSL connection mode |
| `DB_PERSISTENT` | `false` | Use persistent connections |

### SSL Modes

PostgreSQL supports various SSL modes:

- `disable` - No SSL connection
- `allow` - Try non-SSL first, then SSL
- `prefer` - Try SSL first, then non-SSL (default)
- `require` - Require SSL connection
- `verify-ca` - Require SSL and verify certificate
- `verify-full` - Require SSL and verify certificate and hostname

## PostgreSQL-Specific Features

### 1. Data Types

BaseAPI's PostgreSQL driver supports all major PostgreSQL data types:

**Numeric Types:**
- `BOOLEAN` - True/false values
- `SMALLINT` - 2-byte integer
- `INTEGER` - 4-byte integer
- `BIGINT` - 8-byte integer
- `SERIAL` - Auto-incrementing integer
- `BIGSERIAL` - Auto-incrementing big integer
- `REAL` - Single precision floating point
- `DOUBLE PRECISION` - Double precision floating point
- `DECIMAL/NUMERIC` - Exact numeric with precision

**String Types:**
- `CHAR(n)` - Fixed-length character string
- `VARCHAR(n)` - Variable-length character string
- `TEXT` - Variable-length text

**Date/Time Types:**
- `DATE` - Date only
- `TIME` - Time only
- `TIMESTAMP` - Date and time
- `TIMESTAMPTZ` - Date and time with timezone
- `INTERVAL` - Time interval

**JSON Types:**
- `JSON` - JSON data (stored as text)
- `JSONB` - Binary JSON (more efficient)

**Other Types:**
- `UUID` - Universally unique identifier
- `BYTEA` - Binary data
- `INET` - IP address
- `CIDR` - Network address
- `MACADDR` - MAC address
- `ARRAY` - Array types

### 2. PHP Type Mapping

When creating models, BaseAPI automatically maps PHP types to PostgreSQL types:

```php
class User extends BaseModel
{
    public string $id;           // Maps to UUID (for ID fields) or VARCHAR(255)
    public int $age;             // Maps to INTEGER or SERIAL (for ID fields)
    public bool $active;         // Maps to BOOLEAN
    public float $score;         // Maps to REAL
    public array $metadata;      // Maps to JSONB
    public \DateTime $created_at; // Maps to TIMESTAMP
}
```

### 3. Advanced Features

**UUID Primary Keys:**
```php
class User extends BaseModel
{
    public string $id;  // Automatically uses UUID type for ID fields
    public string $name;
    public string $email;
}
```

**JSONB Columns:**
```php
class Product extends BaseModel
{
    public string $id;
    public string $name;
    public array $attributes;  // Stored as JSONB for efficient querying
    public object $metadata;   // Also stored as JSONB
}
```

**Timestamps with Timezone:**
```php
class Event extends BaseModel
{
    public string $id;
    public string $name;
    public \DateTime $scheduled_at;  // Uses TIMESTAMP
    // For timezone-aware timestamps, you can specify in migrations
}
```

## Migration Examples

### Creating Tables

```php
// This model definition:
class Post extends BaseModel
{
    public string $id;
    public string $title;
    public ?string $content = null;
    public string $author_id;
    public bool $published = false;
    public \DateTime $created_at;
    public \DateTime $updated_at;
}

// Generates this PostgreSQL SQL:
CREATE TABLE "posts" (
    "id" UUID PRIMARY KEY,
    "title" VARCHAR(255) NOT NULL,
    "content" TEXT,
    "author_id" UUID NOT NULL,
    "published" BOOLEAN NOT NULL DEFAULT false,
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

### Adding Foreign Keys

```bash
# Generate migration with foreign key
php bin/console migrate:generate

# The system will detect relationships and generate:
ALTER TABLE "posts" ADD CONSTRAINT "fk_posts_author_id" 
FOREIGN KEY ("author_id") REFERENCES "users" ("id") 
ON DELETE CASCADE ON UPDATE CASCADE;
```

### Adding Indexes

```bash
# Generate migration for indexes
php bin/console migrate:generate

# Creates optimized PostgreSQL indexes:
CREATE INDEX "idx_posts_author_id" ON "posts" ("author_id");
CREATE UNIQUE INDEX "idx_posts_slug" ON "posts" ("slug");
```

## Performance Optimization

### 1. Connection Pooling

For production environments, consider using connection pooling:

```env
# Enable persistent connections
DB_PERSISTENT=true
```

### 2. JSONB Indexing

PostgreSQL's JSONB columns can be indexed for better performance:

```sql
-- Create GIN index on JSONB column
CREATE INDEX idx_products_attributes ON products USING GIN (attributes);

-- Create index on specific JSONB key
CREATE INDEX idx_products_category ON products USING GIN ((attributes->>'category'));
```

### 3. Partial Indexes

Create indexes only for specific conditions:

```sql
-- Index only published posts
CREATE INDEX idx_posts_published ON posts (created_at) WHERE published = true;
```

## Troubleshooting

### Common Issues

**1. Connection Refused**
```
SQLSTATE[08006] Connection refused
```
- Check if PostgreSQL is running: `sudo systemctl status postgresql`
- Verify host and port in configuration
- Check firewall settings

**2. Authentication Failed**
```
SQLSTATE[08006] FATAL: password authentication failed
```
- Verify username and password
- Check `pg_hba.conf` authentication method
- Ensure user has proper privileges

**3. Database Does Not Exist**
```
SQLSTATE[08006] FATAL: database "baseapi_db" does not exist
```
- Create the database as shown in setup steps
- Verify database name in configuration

**4. Permission Denied**
```
SQLSTATE[42501] ERROR: permission denied for schema public
```
- Grant proper privileges to the user
- Check schema permissions

### Debugging

Enable query logging in PostgreSQL:

```sql
-- Show current log settings
SHOW log_statement;
SHOW log_min_duration_statement;

-- Enable all statement logging (for development only)
ALTER SYSTEM SET log_statement = 'all';
ALTER SYSTEM SET log_min_duration_statement = 0;

-- Reload configuration
SELECT pg_reload_conf();
```

View logs:
```bash
# Ubuntu/Debian
sudo tail -f /var/log/postgresql/postgresql-*-main.log

# macOS (Homebrew)
tail -f /usr/local/var/log/postgres.log
```

## Best Practices

### 1. Use UUIDs for Primary Keys

PostgreSQL handles UUIDs efficiently and they're great for distributed systems:

```php
class User extends BaseModel
{
    public string $id;  // Automatically becomes UUID
}
```

### 2. Leverage JSONB

Use JSONB for flexible, queryable JSON data:

```php
class Product extends BaseModel
{
    public string $id;
    public string $name;
    public array $specifications;  // Stored as JSONB
}
```

### 3. Use Appropriate Data Types

Choose the right PostgreSQL data type for your use case:
- Use `TIMESTAMP` for most date/time needs
- Use `TIMESTAMPTZ` when timezone matters
- Use `JSONB` instead of `JSON` for better performance
- Use `TEXT` for variable-length strings without size limits

### 4. Index Strategy

- Create indexes on frequently queried columns
- Use partial indexes for conditional queries
- Consider GIN indexes for JSONB columns
- Monitor query performance with `EXPLAIN ANALYZE`

## Security Considerations

### 1. Connection Security

- Use SSL connections in production (`DB_SSLMODE=require`)
- Use strong passwords
- Limit network access with `pg_hba.conf`
- Use connection pooling to limit connections

### 2. User Privileges

- Create dedicated database users for applications
- Grant only necessary privileges
- Use different users for different environments
- Regularly audit user permissions

### 3. Data Protection

- Enable row-level security when needed
- Use PostgreSQL's built-in encryption features
- Regular backups with `pg_dump`
- Monitor access logs

## Next Steps

1. **Test Your Setup**: Run the BaseAPI test suite to verify PostgreSQL integration
2. **Create Your First Model**: Define a model and generate migrations
3. **Optimize Performance**: Add appropriate indexes and configure connection pooling
4. **Monitor**: Set up logging and monitoring for your PostgreSQL instance

For more information, see the [PostgreSQL Documentation](https://www.postgresql.org/docs/) and [BaseAPI Migration Guide](../DATABASE-ABSTRACTION.md).
