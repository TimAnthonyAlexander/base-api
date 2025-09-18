import { Box, Typography, Alert, List, ListItem, ListItemText } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';

export default function Overview() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Architecture Overview
      </Typography>
      <Typography variant="h5" color="text.secondary" paragraph>
        Understanding BaseAPI's architecture and core principles
      </Typography>

      <Typography paragraph>
        BaseAPI is designed as a modern, high-performance PHP framework that prioritizes 
        developer productivity and application maintainability. Built on PHP 8.4+, it leverages 
        the latest language features while maintaining simplicity and clarity.
      </Typography>

      <Alert severity="info" sx={{ my: 3 }}>
        BaseAPI follows a request-response cycle with dependency injection, middleware processing, 
        and automatic data binding to create robust APIs with minimal boilerplate.
      </Alert>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Core Principles
      </Typography>

      <Alert severity="info" sx={{ mb: 2 }}>
        <Typography variant="h6" gutterBottom>
          Convention over Configuration
        </Typography>
        <Typography>
          BaseAPI uses sensible defaults and naming conventions to minimize configuration. 
          Models automatically map to tables, routes follow predictable patterns, and 
          migrations are generated from model definitions.
        </Typography>
      </Alert>

      <Alert severity="success" sx={{ mb: 2 }}>
        <Typography variant="h6" gutterBottom>
          Performance First
        </Typography>
        <Typography>
          Every component is designed for minimal overhead and maximum speed. Built-in 
          caching system, efficient routing, and optimized database queries ensure 
          your API performs well even under load.
        </Typography>
      </Alert>

      <Alert severity="warning" sx={{ mb: 2 }}>
        <Typography variant="h6" gutterBottom>
          Security by Default
        </Typography>
        <Typography>
          CORS handling, rate limiting, input validation, and SQL injection protection 
          are built-in and enabled by default. You don't have to remember to add security—it's already there.
        </Typography>
      </Alert>

      <Alert severity="info" sx={{ mb: 2 }}>
        <Typography variant="h6" gutterBottom>
          Developer Experience
        </Typography>
        <Typography>
          Auto-generated OpenAPI specs and TypeScript types, comprehensive CLI tools, 
          and clear error messages make development fast and enjoyable.
        </Typography>
      </Alert>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Application Lifecycle
      </Typography>

      <Typography paragraph>
        Every BaseAPI request follows a predictable lifecycle that ensures consistency and performance:
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
        App & DI Container
      </Typography>

      <Typography paragraph>
        The App class serves as the central bootstrap point, managing service registration, 
        configuration loading, and providing static access to core services like routing, 
        database, cache, and logging.
      </Typography>

      <CodeBlock language="php" code={`<?php

// Bootstrap the application
App::boot();

// Access core services
$router = App::router();
$db = App::db();
$cache = App::cache();
$logger = App::logger();`} />

      <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
        HTTP Layer
      </Typography>

      <Typography paragraph>
        The HTTP layer handles incoming requests through a middleware pipeline, with automatic 
        parameter binding, validation, and response formatting:
      </Typography>

      <CodeBlock language="php" code={`<?php

class UserController extends Controller
{
    public string $name = '';     // Auto-populated from request
    public string $email = '';    // Validated against rules
    
    public function post(): JsonResponse
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email'
        ]);
        
        // Create user with validated data
        $user = new User();
        $user->name = $this->name;
        $user->email = $this->email;
        $user->save();
        
        return JsonResponse::created($user);
    }
}`} />

      <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
        Database Layer
      </Typography>

      <Typography paragraph>
        BaseAPI's ORM provides a simple yet powerful abstraction over database operations 
        with automatic caching, relationship loading, and migration generation:
      </Typography>

      <CodeBlock language="php" code={`<?php

// Simple queries with automatic caching
$users = User::cached(300)->where('active', true)->get();

// Relationships and eager loading
$posts = Post::with(['user', 'comments'])->get();

// Automatic migrations from model definitions
// php bin/console migrate:generate`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Project Structure
      </Typography>

      <Typography paragraph>
        BaseAPI projects follow a conventional structure that promotes organization and maintainability:
      </Typography>

      <List>
        <ListItem>
          <ListItemText
            primary="app/ - Application Code"
            secondary="Controllers, Models, Services, and custom business logic"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="config/ - Configuration"
            secondary="Environment-specific settings and application configuration"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="routes/ - Route Definitions"
            secondary="API endpoint definitions with middleware and controller mappings"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="storage/ - File Storage"
            secondary="Logs, cache files, uploads, and temporary data"
          />
        </ListItem>
      </List>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Design Patterns
      </Typography>

      <Typography paragraph>
        BaseAPI implements several proven design patterns:
      </Typography>

      <List>
        <ListItem>
          <ListItemText
            primary="Active Record Pattern"
            secondary="Models represent database records with built-in query methods"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Dependency Injection"
            secondary="Constructor injection with automatic resolution and container management"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Middleware Pattern"
            secondary="Composable request/response processing pipeline"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Repository Pattern"
            secondary="Cache and database access through consistent interfaces"
          />
        </ListItem>
      </List>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Performance Characteristics
      </Typography>

      <Typography paragraph>
        BaseAPI is designed for high performance in production environments:
      </Typography>

      <List>
        <ListItem>
          <ListItemText
            primary="Minimal Memory Footprint"
            secondary="Lazy loading and efficient object creation reduce memory usage"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Built-in Caching"
            secondary="Multi-level caching for queries, responses, and application data"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Optimized Database Access"
            secondary="Connection pooling, prepared statements, and query optimization"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="Efficient Routing"
            secondary="Fast route matching and parameter extraction"
          />
        </ListItem>
      </List>

      <Alert severity="success" sx={{ mt: 4 }}>
        <strong>Architecture Benefits:</strong>
        <br />• Predictable request lifecycle and clear separation of concerns
        <br />• Automatic dependency resolution reduces coupling
        <br />• Convention-based structure accelerates development
        <br />• Built-in performance optimizations handle scaling concerns
        <br />• Security features protect against common vulnerabilities
      </Alert>
    </Box>
  );
}