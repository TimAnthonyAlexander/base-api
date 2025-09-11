# SQLite Setup Example

This example shows how to use BaseAPI with SQLite instead of MySQL.

## 1. Environment Configuration

Create a `.env` file with SQLite configuration:

```env
APP_DEBUG=true
APP_PORT=7879

# SQLite Database Configuration
DB_DRIVER=sqlite
DB_NAME=database.sqlite

# Features
ENABLE_CORS=true
ENABLE_RATE_LIMITING=true
```

## 2. Create a Model

```php
<?php
// app/Models/Product.php

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

## 3. Generate and Apply Migrations

```bash
# Generate migration plan from your models
php bin/console migrate:generate

# Apply the migrations to your SQLite database
php bin/console migrate:apply
```

## 4. Start the Server

```bash
php bin/console serve
```

Your API will now be running with SQLite as the database backend!

## Database File Location

The SQLite database file will be created at `storage/database.sqlite` (relative to your project root).

For an in-memory database (useful for testing), use:

```env
DB_NAME=:memory:
```

## Switching Between Databases

You can easily switch between MySQL and SQLite by changing the `DB_DRIVER` environment variable:

- `DB_DRIVER=mysql` - Use MySQL
- `DB_DRIVER=sqlite` - Use SQLite

The migration system will automatically generate the appropriate SQL for each database type.
