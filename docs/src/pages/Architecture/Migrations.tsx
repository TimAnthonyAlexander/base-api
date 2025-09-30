
import { Box, Typography, Alert, List, ListItem, ListItemText } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';

export default function Migrations() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Migrations
            </Typography>
            <Typography variant="h5" color="text.secondary">
                Automatic database migrations in BaseAPI.
            </Typography>

            <Typography>
                BaseAPI generates database migrations automatically from your model definitions.
                The migration system scans your models, compares them with your current database schema,
                and generates individual SQL migration statements that can be reviewed before applying.
            </Typography>

            <Alert severity="info" sx={{ my: 3 }}>
                BaseAPI uses a diff-based migration system that introspects your database and compares it
                with your model definitions to generate only the necessary changes. Each migration has a
                unique ID and tracks whether it's been applied.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Commands
            </Typography>

            <CodeBlock language="bash" code={`# Generate migrations from your model changes
./mason migrate:generate`} />

            <CodeBlock language="bash" code={`# Apply generated migrations (all)
./mason migrate:apply`} />

            <Typography sx={{ mt: 2 }}>
                These migrations are stored in <code>storage/migrations.json</code> by default.
                Executed migrations get added to <code>storage/executed-migrations.json</code>.
            </Typography>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Model to Database Mapping
            </Typography>

            <Typography>
                BaseAPI automatically creates database tables and columns based on your model properties.
                <br />
                All models extend BaseModel and automatically include <code>id</code>, <code>created_at</code>,
                and <code>updated_at</code> columns.
            </Typography>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Relationships & Foreign Keys
            </Typography>

            <Typography>
                BaseAPI automatically creates foreign key constraints in two ways:
            </Typography>

            <List sx={{ mb: 2 }}>
                <ListItem>
                    <ListItemText
                        primary="String Foreign Keys"
                        secondary="Properties ending with '_id' are created as foreign key columns (e.g., public string $user_id creates FK to User model)"
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
    // String foreign key properties
    public string $user_id = '';
    public string $product_id = '';
    public int $quantity = 1;
    public float $total = 0.0;
    
    
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
                Foreign Key Cascade Configuration
            </Typography>

            <Typography>
                By default, all foreign key relationships use <code>ON DELETE CASCADE</code> and <code>ON UPDATE CASCADE</code>.
                This means when you delete a parent record, all related child records are automatically deleted.
            </Typography>

            <Alert severity="info" sx={{ my: 3 }}>
                <strong>Default Behavior:</strong> Foreign keys will CASCADE on delete by default. This is the most
                intuitive behavior for modern applications where deleting a parent should clean up all related data.
            </Alert>

            <Typography sx={{ mb: 2 }}>
                You can customize this behavior per foreign key using a static <code>$foreignKeys</code> property:
            </Typography>

            <CodeBlock language="php" code={`<?php

class Comment extends BaseModel
{
    public string $id;
    public ?string $user_id = null;  // Nullable
    public string $post_id;
    public string $content;
    public ?\\DateTime $created_at = null;
    
    // Customize foreign key cascade behavior
    public static array $foreignKeys = [
        'user_id' => [
            'on_delete' => 'SET NULL',  // Keep comments when user deleted
            'on_update' => 'CASCADE'
        ],
        'post_id' => [
            'on_delete' => 'CASCADE',   // Delete comments when post deleted
            'on_update' => 'CASCADE'
        ]
    ];
}`} />

            <Typography sx={{ mt: 2 }}>
                <strong>Supported cascade options:</strong>
            </Typography>

            <List sx={{ mb: 2 }}>
                <ListItem>
                    <ListItemText
                        primary="CASCADE (default)"
                        secondary="Automatically delete/update child records when parent is deleted/updated"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="RESTRICT"
                        secondary="Prevent deletion/update if child records exist"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="SET NULL"
                        secondary="Set foreign key to NULL when parent is deleted/updated (column must be nullable)"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="NO ACTION"
                        secondary="Similar to RESTRICT (database-specific behavior)"
                    />
                </ListItem>
            </List>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Example: Blog System with Cascading
            </Typography>

            <CodeBlock language="php" code={`<?php

class User extends BaseModel
{
    public string $id;
    public string $email;
    public string $name;
}

class Post extends BaseModel
{
    public string $id;
    public string $user_id;  // CASCADE by default
    public string $title;
    public string $content;
    public ?\\DateTime $created_at = null;
}

class Comment extends BaseModel
{
    public string $id;
    public ?string $user_id = null;  // Nullable for SET NULL
    public string $post_id;
    public string $content;
    public ?\\DateTime $created_at = null;
    
    public static array $foreignKeys = [
        'user_id' => [
            'on_delete' => 'SET NULL',  // Preserve comments, remove user link
            'on_update' => 'CASCADE'
        ],
        'post_id' => [
            'on_delete' => 'CASCADE',   // Delete comments with post
            'on_update' => 'CASCADE'
        ]
    ];
}`} />

            <Typography sx={{ mt: 2 }}>
                In this example:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="User Deletion"
                        secondary="Posts are deleted (CASCADE), Comments remain but user_id is set to NULL"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Post Deletion"
                        secondary="All Comments for that post are automatically deleted (CASCADE)"
                    />
                </ListItem>
            </List>

            <Alert severity="warning" sx={{ mt: 3 }}>
                <strong>Important:</strong> When using <code>SET NULL</code>, the foreign key column must be
                nullable (e.g., <code>public ?string $user_id = null</code>). Otherwise, the database will
                reject the constraint.
            </Alert>

            <Alert severity="success" sx={{ mt: 2, mb: 3 }}>
                <strong>Best Practices:</strong>
                <br />• Use CASCADE for child records that should always be deleted with parent
                <br />• Use SET NULL for soft references where data should be preserved
                <br />• Use RESTRICT for critical relationships that require manual cleanup
                <br />• Always test cascade behavior in development first
                <br />• Document cascade decisions in your model classes
                <br />• Consider soft deletes for important data instead of hard deletes
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Migration State Management
            </Typography>

            <Typography>
                BaseAPI tracks migrations in <code>storage/migrations.json</code> and execution state in
                <code>storage/executed-migrations.json</code>. <br />Each migration is a self-contained SQL
                statement with metadata:
            </Typography>

            <CodeBlock language="json" code={`{
  "version": "1.0",
  "migrations": [
    {
      "id": "mig_a684b3eb7c1c",
      "sql": "CREATE TABLE \\"users\\" (\\n  \\"id\\" TEXT PRIMARY KEY NOT NULL,\\n  \\"name\\" TEXT NOT NULL,\\n  \\"email\\" TEXT NOT NULL\\n)",
      "destructive": false,
      "generated_at": "2024-12-01T12:00:00+00:00",
      "table": "users",
      "operation": "create_table",
      "warning": null
    },
    {
      "id": "mig_471ef548fac7",
      "sql": "CREATE UNIQUE INDEX \\"uniq_users_email\\" ON \\"users\\" (\\"email\\")",
      "destructive": false,
      "generated_at": "2024-12-01T12:00:00+00:00",
      "table": "users",
      "operation": "add_index",
      "warning": null
    }
  ]
}`} />

            <Typography sx={{ mt: 2 }}>
                Execution state is tracked separately in <code>storage/executed-migrations.json</code>:
            </Typography>

            <CodeBlock language="json" code={`{
  "version": "1.0", 
  "executed": [
    {
      "id": "mig_a684b3eb7c1c",
      "executed_at": "2024-12-01T12:01:00+00:00"
    }
  ]
}`} />

            <Typography sx={{ mt: 2 }}>
                The <code>storage/executed-migrations.json</code> file is not git-tracked, as every system can have a different migration execution state.
            </Typography>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Custom Column Definitions
            </Typography>

            <Typography>
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

            <Typography>
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

            <Typography>
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

            <Typography>
                BaseAPI migrations are designed with safety in mind, automatically detecting destructive operations:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Automatic Destructive Detection"
                        secondary="The system identifies operations that could lose data (dropping tables/columns, shrinking types)"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Safe Mode Support"
                        secondary="Use --safe flag to apply only non-destructive changes for production deployments"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Individual Migration Tracking"
                        secondary="Each migration has a unique ID and tracks its execution status independently"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Transaction Safety"
                        secondary="Migrations are grouped by table and executed in transactions for consistency"
                    />
                </ListItem>
            </List>

            <CodeBlock language="bash" code={`# Apply only safe (non-destructive) migrations
./mason migrate:apply --safe`} />

            <CodeBlock language="bash" code={`# Review what will be executed before applying
./mason migrate:generate`} />

            <CodeBlock language="bash" code={`# Apply specific migrations by reviewing the execution plan
./mason migrate:apply`} />

            <Alert severity="warning" sx={{ mt: 4 }}>
                <strong>Production Safety:</strong> Always run <code>migrate:apply --safe</code> first in production
                to apply non-destructive changes. Review destructive operations separately and test on staging data.
            </Alert>

            <Alert severity="info" sx={{ mt: 2 }}>
                <strong>Migration IDs:</strong> Migration IDs are generated from content hash, making them deterministic.
                The same model changes will generate identical migration IDs, preventing duplicates across environments.
            </Alert>

            <Alert severity="success" sx={{ mt: 2 }}>
                <strong>Best Practices:</strong>
                <br />• Run <code>migrate:generate</code> after model changes
                <br />• Review generated SQL in <code>storage/migrations.json</code> before applying
                <br />• Use <code>--safe</code> flag for production deployments
                <br />• Test migrations on staging environments first
                <br />• Keep migration files in version control
                <br />• Backup production databases before applying destructive changes
                <br />• Use deterministic migration IDs to prevent duplicates across environments
            </Alert>
        </Box>
    );
}
