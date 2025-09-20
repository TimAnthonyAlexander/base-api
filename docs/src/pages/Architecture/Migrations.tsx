
import { Box, Typography, Alert, List, ListItem, ListItemText } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

export default function Migrations() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Migrations
            </Typography>
            <Typography variant="h5" color="text.secondary" paragraph>
                Automatic database migrations in BaseAPI.
            </Typography>

            <Typography paragraph>
                BaseAPI generates database migrations automatically from your model definitions.
                No need to write SQL or migration files manually - the framework analyzes your
                models and creates the appropriate database schema.
            </Typography>

            <Alert severity="info" sx={{ my: 3 }}>
                BaseAPI scans your models and generates migration plans that can be reviewed before applying.
                This ensures your database schema stays in sync with your code.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Basic Commands
            </Typography>

            <CodeBlock language="bash" code={`# Generate a migration plan from your models
php bin/console migrate:generate

# Review the generated migration plan
cat storage/migrations.json

# Apply migrations to your database
php bin/console migrate:apply

# Check current migration status
php bin/console migrate:status`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Model to Database Mapping
            </Typography>

            <Typography paragraph>
                BaseAPI automatically creates database tables and columns based on your model properties.
                All models extend BaseModel and automatically include <code>id</code>, <code>created_at</code>, 
                and <code>updated_at</code> columns:
            </Typography>

            <CodeBlock language="php" code={`<?php

class Product extends BaseModel
{
    // Creates VARCHAR(255) NOT NULL DEFAULT ''
    public string $name = '';
    
    // Creates TEXT NULL
    public ?string $description = null;
    
    // Creates DECIMAL(10,2) NOT NULL DEFAULT 0.00
    public float $price = 0.0;
    
    // Creates BOOLEAN NOT NULL DEFAULT true
    public bool $active = true;
    
    // Creates INTEGER NOT NULL DEFAULT 0
    public int $stock = 0;
    
    // Define indexes
    public static array $indexes = [
        'name' => 'index',        // Regular index for searching
        'price' => 'index',       // Index for price filtering
        'active' => 'index',      // Index for status queries
    ];
}`} />

            <Callout type="info" title="Automatic Table Creation">
                This model automatically generates a <code>products</code> table with appropriate
                columns, indexes, and constraints. No manual migration files needed!
            </Callout>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Relationships & Foreign Keys
            </Typography>

            <Typography paragraph>
                BaseAPI automatically creates foreign key constraints in two ways:
            </Typography>
            
            <List sx={{ mb: 2 }}>
                <ListItem>
                    <ListItemText
                        primary="Typed Properties"
                        secondary="Properties typed as other BaseModel classes automatically create FK columns (e.g., public ?User $user creates user_id column)"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Naming Convention"
                        secondary="Properties ending with '_id' are detected as foreign keys if a matching model exists (e.g., user_id creates FK to User model)"
                    />
                </ListItem>
            </List>

            <CodeBlock language="php" code={`<?php

class Order extends BaseModel
{
    public string $user_id = '';     // Creates FK to users.id
    public string $product_id = '';  // Creates FK to products.id
    public int $quantity = 1;
    public float $total = 0.0;
    
    // Relationship definitions
    public ?User $user = null;
    public ?Product $product = null;
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function product(): BelongsTo  
    {
        return $this->belongsTo(Product::class);
    }
    
    // Indexes for foreign keys
    public static array $indexes = [
        'user_id' => 'index',
        'product_id' => 'index',
    ];
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Migration State Management
            </Typography>

            <Typography paragraph>
                BaseAPI tracks migration plans in <code>storage/migrations.json</code>. The file contains 
                the generated migration plan and marks when it has been applied:
            </Typography>

            <CodeBlock language="json" code={`{
  "generated_at": "2024-12-01T12:00:00+00:00",
  "plan": [
    {
      "op": "create_table",
      "table": "users",
      "columns": {
        "id": {
          "name": "id",
          "type": "VARCHAR(36)",
          "nullable": false,
          "default": null,
          "is_pk": true
        },
        "name": {
          "name": "name",
          "type": "VARCHAR(255)",
          "nullable": false,
          "default": null,
          "is_pk": false
        }
      },
      "destructive": false
    },
    {
      "op": "add_index",
      "table": "users",
      "index": {
        "name": "idx_users_name",
        "column": "name",
        "type": "index"
      },
      "destructive": false
    }
  ],
  "applied_at": "2024-12-01T12:01:00+00:00"
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Custom Column Definitions
            </Typography>

            <Typography paragraph>
                You can customize column definitions when needed:
            </Typography>

            <CodeBlock language="php" code={`<?php

class User extends BaseModel
{
    public string $name = '';
    public string $email = '';
    public ?string $bio = null;
    
    // Custom column definitions
    public static array $columns = [
        'name' => ['type' => 'VARCHAR(120)', 'null' => false],
        'email' => ['type' => 'VARCHAR(255)', 'null' => false],
        'bio' => ['type' => 'TEXT', 'null' => true],
    ];
    
    // Indexes
    public static array $indexes = [
        'email' => 'unique',
        'name' => 'index',
    ];
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                PHP Type to Database Column Mapping
            </Typography>

            <Typography paragraph>
                BaseAPI automatically maps PHP property types to appropriate database column types 
                based on your database driver:
            </Typography>

            <CodeBlock language="php" code={`// Type mappings vary by database driver:

// MySQL Driver:
string  -> VARCHAR(255) or VARCHAR(36) for *_id properties
int     -> INT  
float   -> DOUBLE
bool    -> TINYINT(1)
array   -> JSON
object  -> JSON

// SQLite Driver:
string  -> TEXT
int     -> INTEGER
float   -> REAL  
bool    -> INTEGER (0/1)
array   -> TEXT (JSON)
object  -> TEXT (JSON)

// PostgreSQL Driver:
string  -> VARCHAR(255) or UUID for *_id properties
int     -> INTEGER or SERIAL for *_id properties
float   -> REAL
double  -> DOUBLE PRECISION
bool    -> BOOLEAN
array   -> JSONB
object  -> JSONB`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Database Drivers
            </Typography>

            <Typography paragraph>
                BaseAPI supports multiple database drivers with appropriate SQL generation:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="SQLite"
                        secondary="Perfect for development and small applications"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="MySQL"
                        secondary="Production-ready with full feature support"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="PostgreSQL"
                        secondary="Advanced features and JSON support"
                    />
                </ListItem>
            </List>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Safe Migration Practices
            </Typography>

            <Typography paragraph>
                BaseAPI migrations are designed to be safe and reversible:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Non-destructive Changes"
                        secondary="Adding columns and indexes is always safe"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Data Preservation"
                        secondary="Existing data is preserved during schema changes"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Foreign Key Safety"
                        secondary="Constraints are created in proper order to avoid conflicts"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Rollback Support"
                        secondary="Migration state is tracked for potential rollbacks"
                    />
                </ListItem>
            </List>

            <Alert severity="warning" sx={{ mt: 4 }}>
                <strong>Production Safety:</strong> Always review generated migrations before applying
                to production databases. Test migrations on a copy of your production data first.
            </Alert>

            <Alert severity="info" sx={{ mt: 2 }}>
                <strong>Safe Mode:</strong> Use <code>migrate:apply --safe</code> to skip destructive 
                operations like dropping tables or columns. This allows you to apply non-destructive 
                changes safely while reviewing destructive changes separately.
            </Alert>

            <Alert severity="success" sx={{ mt: 2 }}>
                <strong>Best Practices:</strong>
                <br />• Run <code>migrate:generate</code> after model changes
                <br />• Review migration plans before applying
                <br />• Use <code>--safe</code> flag for production deployments
                <br />• Test migrations on staging environments first
                <br />• Use version control for your models
                <br />• Backup production databases before major migrations
            </Alert>
        </Box>
    );
}
