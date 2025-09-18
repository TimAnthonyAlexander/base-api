
import { Box, Typography, Alert, List, ListItem, ListItemText, Card, CardContent, Grid } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';

export default function Overview() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Architecture Overview
      </Typography>
      
      <Typography variant="h5" color="text.secondary" paragraph>
        Understanding the core architecture and design principles of BaseAPI.
      </Typography>

      <Typography paragraph>
        BaseAPI is a KISS-first (Keep It Simple, Stupid) PHP 8.4+ framework that prioritizes simplicity, 
        performance, and developer experience. It follows a conventional MVC-like architecture with 
        modern features like dependency injection, caching, and automated migrations.
      </Typography>

      <Alert severity="info" sx={{ my: 3 }}>
        BaseAPI adds less than 0.01ms overhead per request and is designed to get out of your way 
        so you can focus on building your API logic.
      </Alert>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Core Principles
      </Typography>

      <Grid container spacing={3} sx={{ mb: 4 }}>
        <Grid item xs={12} md={6}>
          <Card>
            <CardContent>
              <Typography variant="h6" gutterBottom color="primary">
                Convention over Configuration
              </Typography>
              <Typography variant="body2">
                BaseAPI uses sensible defaults and naming conventions to minimize configuration. 
                Models automatically map to tables, routes follow predictable patterns, and 
                migrations are generated from model definitions.
              </Typography>
            </CardContent>
          </Card>
        </Grid>
        <Grid item xs={12} md={6}>
          <Card>
            <CardContent>
              <Typography variant="h6" gutterBottom color="primary">
                Performance First
              </Typography>
              <Typography variant="body2">
                Every component is designed for minimal overhead and maximum speed. Built-in 
                caching system, efficient routing, and optimized database queries ensure 
                your API performs well even under load.
              </Typography>
            </CardContent>
          </Card>
        </Grid>
        <Grid item xs={12} md={6}>
          <Card>
            <CardContent>
              <Typography variant="h6" gutterBottom color="primary">
                Security by Default
              </Typography>
              <Typography variant="body2">
                CORS handling, rate limiting, input validation, and SQL injection protection 
                are built-in and enabled by default. You don't have to remember to add security—it's already there.
              </Typography>
            </CardContent>
          </Card>
        </Grid>
        <Grid item xs={12} md={6}>
          <Card>
            <CardContent>
              <Typography variant="h6" gutterBottom color="primary">
                Developer Experience
              </Typography>
              <Typography variant="body2">
                Auto-generated OpenAPI specs and TypeScript types, comprehensive CLI tools, 
                and clear error messages make development fast and enjoyable.
              </Typography>
            </CardContent>
          </Card>
        </Grid>
      </Grid>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Application Lifecycle
      </Typography>

      <Typography paragraph>
        BaseAPI applications follow a predictable lifecycle from request to response:
      </Typography>

      <List sx={{ mb: 4 }}>
        <ListItem>
          <ListItemText
            primary="1. Bootstrap & Service Registration"
            secondary="App::boot() loads environment, configuration, and registers services with the DI container"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="2. Route Matching"
            secondary="Router matches the incoming request to a defined route and extracts parameters"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="3. Middleware Pipeline"
            secondary="Request passes through middleware chain (CORS, rate limiting, authentication, etc.)"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="4. Controller Resolution"
            secondary="DI container resolves controller with all dependencies injected"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="5. Request Processing"
            secondary="Controller processes the request, interacting with models and services"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="6. Response Generation"
            secondary="Controller returns a JsonResponse that's serialized and sent to client"
          />
        </ListItem>
      </List>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Core Components
      </Typography>

      <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
        Application Core (App)
      </Typography>
      
      <Typography paragraph>
        The central <code>App</code> class manages application lifecycle, service registration, 
        and provides access to core services. It follows a singleton pattern and bootstraps 
        the entire framework.
      </Typography>

      <CodeBlock language="php" code={`<?php

// Bootstrap the application
App::boot('/path/to/project');

// Access core services
$router = App::router();
$db = App::db();
$config = App::config();
$container = App::container();`} />

      <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
        Dependency Injection Container
      </Typography>
      
      <Typography paragraph>
        BaseAPI includes a powerful DI container that automatically resolves dependencies 
        and manages service lifecycles. Controllers and services receive their dependencies 
        through constructor injection.
      </Typography>

      <CodeBlock language="php" code={`<?php

class UserController extends Controller
{
    public function __construct(
        private EmailService $emailService,
        private UserRepository $userRepository
    ) {}
    
    public function post(): JsonResponse
    {
        // Dependencies are automatically injected
        $user = $this->userRepository->create($this->toArray());
        $this->emailService->sendWelcome($user);
        
        return JsonResponse::created($user->jsonSerialize());
    }
}`} />

      <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
        HTTP Layer
      </Typography>
      
      <Typography paragraph>
        The HTTP layer handles request parsing, routing, middleware execution, and response 
        formatting. It's built around PSR-7 principles but optimized for BaseAPI's specific use case.
      </Typography>

      <List>
        <ListItem>
          <ListItemText
            primary="Request"
            secondary="Parses HTTP requests, including JSON body parsing, file uploads, and query parameters"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Router"
            secondary="Fast route matching with parameter extraction and method-based routing"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Middleware"
            secondary="Composable request/response pipeline with built-in security middlewares"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Response"
            secondary="JsonResponse and BinaryResponse classes with automatic serialization"
          />
        </ListItem>
      </List>

      <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
        Database Layer
      </Typography>
      
      <Typography paragraph>
        BaseAPI provides a database abstraction layer with query builder, ORM-like models, 
        and automatic migrations. It supports MySQL, PostgreSQL, and SQLite.
      </Typography>

      <CodeBlock language="php" code={`<?php

// Query Builder
$users = App::db()->qb()
    ->table('users')
    ->where('active', '=', true)
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();

// Model Usage
$user = User::find($id);
$activeUsers = User::where('active', '=', true)
    ->orderBy('created_at')
    ->get();

// Relationships
$userWithPosts = User::with(['posts'])->find($id);`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Project Structure
      </Typography>

      <Typography paragraph>
        BaseAPI follows a conventional directory structure that separates concerns while 
        keeping everything easily discoverable:
      </Typography>

      <CodeBlock language="bash" code={`project-root/
├── app/                    # Application code
│   ├── Controllers/        # Request handlers
│   ├── Models/            # Database models
│   ├── Services/          # Business logic services
│   └── Providers/         # Service providers
├── config/                # Configuration files
│   ├── app.php           # Application config
│   └── i18n.php          # Internationalization config
├── routes/               # Route definitions
│   └── api.php          # API routes
├── storage/             # Application storage
│   ├── cache/           # File cache
│   ├── logs/            # Application logs
│   └── migrations.json  # Migration state
├── translations/        # I18n translation files
│   ├── en/             # English translations
│   └── de/             # German translations
├── public/             # Web root
│   └── index.php       # Entry point
├── .env               # Environment configuration
└── composer.json      # Dependencies`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Design Patterns
      </Typography>

      <Typography paragraph>
        BaseAPI leverages several proven design patterns to maintain clean, maintainable code:
      </Typography>

      <List>
        <ListItem>
          <ListItemText
            primary="Service Container Pattern"
            secondary="Centralized dependency resolution and service lifecycle management"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Active Record Pattern"
            secondary="Models encapsulate both data and behavior, with automatic persistence"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Middleware Pattern"
            secondary="Composable request/response pipeline for cross-cutting concerns"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Repository Pattern"
            secondary="Optional data access abstraction for complex queries and business logic"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Service Provider Pattern"
            secondary="Modular service registration and configuration"
          />
        </ListItem>
      </List>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Performance Characteristics
      </Typography>

      <Typography paragraph>
        BaseAPI is optimized for real-world API performance:
      </Typography>

      <List>
        <ListItem>
          <ListItemText
            primary="Minimal Framework Overhead"
            secondary="Less than 0.01ms added to request time (measured on MacBook Pro M3)"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Efficient Memory Usage"
            secondary="Low memory footprint even with complex object graphs"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Smart Caching"
            secondary="Multi-layer caching with automatic invalidation reduces database load"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Optimized Queries"
            secondary="Query builder generates efficient SQL with automatic parameter binding"
          />
        </ListItem>
      </List>

      <Alert severity="success" sx={{ mt: 4 }}>
        <strong>Next Steps:</strong> Explore the individual architecture components:
        <br />• <strong>Routing</strong> - How requests are matched to controllers
        <br />• <strong>Controllers</strong> - Request handling and business logic
        <br />• <strong>Models & ORM</strong> - Database interaction and relationships
        <br />• <strong>Migrations</strong> - Automatic database schema management
        <br />• <strong>Validation</strong> - Input validation and sanitization
      </Alert>
    </Box>
  );
}
