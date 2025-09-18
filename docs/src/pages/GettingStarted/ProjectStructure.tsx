
import { Box, Typography, Alert, List, ListItem, ListItemText, Accordion, AccordionSummary, AccordionDetails } from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

const projectStructure = `project-root/
├── app/                    # Application code
│   ├── Controllers/        # HTTP request handlers
│   ├── Models/            # Database models
│   ├── Services/          # Business logic services
│   ├── Providers/         # Service providers
│   └── Auth/              # Authentication components
├── bin/
│   └── console           # Command-line interface
├── config/               # Configuration files
│   ├── app.php          # Application settings
│   └── i18n.php         # Internationalization config
├── routes/              # Route definitions
│   └── api.php         # API routes
├── storage/            # Application storage
│   ├── cache/          # File cache
│   ├── logs/           # Application logs
│   ├── ratelimits/     # Rate limiting data
│   └── migrations.json # Migration state
├── translations/       # I18n translation files
│   ├── en/            # English translations
│   ├── de/            # German translations
│   └── es/            # Spanish translations
├── public/            # Web root (for traditional hosting)
│   └── index.php      # Application entry point
├── tests/            # Test files
├── vendor/           # Composer dependencies
├── .env             # Environment configuration
├── .env.example     # Environment template
├── composer.json    # Composer configuration
└── openapi.json    # Generated OpenAPI specification`;

export default function ProjectStructure() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Project Structure
      </Typography>
      
      <Typography variant="h5" color="text.secondary" paragraph>
        Understanding how BaseAPI organizes your code and configuration.
      </Typography>

      <Typography paragraph>
        BaseAPI follows a simple, predictable structure that keeps your code organized and easy to navigate. 
        Each directory has a specific purpose, and the framework uses conventions to minimize configuration.
      </Typography>

      <Alert severity="info" sx={{ my: 3 }}>
        BaseAPI uses convention over configuration - most directories and files follow predictable naming patterns 
        that reduce the need for manual setup.
      </Alert>

      <CodeBlock
        language="bash"
        code={projectStructure}
        title="Complete BaseAPI Project Structure"
      />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Core Directories
      </Typography>

      <Box sx={{ mt: 3 }}>
        <Accordion>
          <AccordionSummary expandIcon={<ExpandMoreIcon />}>
            <Typography variant="h6">app/ - Application Code</Typography>
          </AccordionSummary>
          <AccordionDetails>
            <Typography paragraph>
              The <code>app/</code> directory contains all your application-specific code. This is where you'll 
              spend most of your development time.
            </Typography>
            
            <List>
              <ListItem>
                <ListItemText
                  primary="Controllers/"
                  secondary="HTTP request handlers that process requests and return responses. Each controller handles one or more related endpoints."
                />
              </ListItem>
              <ListItem>
                <ListItemText
                  primary="Models/"
                  secondary="Database models that represent your data structures. Each model typically maps to a database table."
                />
              </ListItem>
              <ListItem>
                <ListItemText
                  primary="Services/"
                  secondary="Business logic classes that encapsulate complex operations. Services are injected into controllers via dependency injection."
                />
              </ListItem>
              <ListItem>
                <ListItemText
                  primary="Providers/"
                  secondary="Service providers that register and configure services in the dependency injection container."
                />
              </ListItem>
              <ListItem>
                <ListItemText
                  primary="Auth/"
                  secondary="Authentication-related classes like user providers and custom authentication logic."
                />
              </ListItem>
            </List>

            <CodeBlock language="bash" code={`app/Controllers/
├── HealthController.php
├── LoginController.php
├── UserController.php
└── ProductController.php

app/Models/
├── User.php
├── Product.php
└── Order.php

app/Services/
├── EmailService.php
├── PaymentService.php
└── NotificationService.php`} />
          </AccordionDetails>
        </Accordion>

        <Accordion>
          <AccordionSummary expandIcon={<ExpandMoreIcon />}>
            <Typography variant="h6">config/ - Configuration Files</Typography>
          </AccordionSummary>
          <AccordionDetails>
            <Typography paragraph>
              Configuration files define application behavior and settings. BaseAPI uses PHP configuration 
              files that can reference environment variables.
            </Typography>
            
            <List>
              <ListItem>
                <ListItemText
                  primary="app.php"
                  secondary="Main application configuration including service providers, middleware, and application settings."
                />
              </ListItem>
              <ListItem>
                <ListItemText
                  primary="i18n.php"
                  secondary="Internationalization configuration including default language, translation providers, and language settings."
                />
              </ListItem>
            </List>

            <CodeBlock language="php" code={`<?php
// config/app.php
return [
    'name' => env('APP_NAME', 'BaseAPI'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    
    'providers' => [
        \\App\\Providers\\AppServiceProvider::class,
    ],
];`} />
          </AccordionDetails>
        </Accordion>

        <Accordion>
          <AccordionSummary expandIcon={<ExpandMoreIcon />}>
            <Typography variant="h6">routes/ - Route Definitions</Typography>
          </AccordionSummary>
          <AccordionDetails>
            <Typography paragraph>
              The routes directory contains your API route definitions. Routes map HTTP requests to controllers.
            </Typography>
            
            <List>
              <ListItem>
                <ListItemText
                  primary="api.php"
                  secondary="Main API routes file where you define all your endpoints, middleware, and controller mappings."
                />
              </ListItem>
            </List>

            <CodeBlock language="php" code={`<?php
// routes/api.php
use BaseApi\\App;
use App\\Controllers\\UserController;

$router = App::router();

$router->get('/users', [UserController::class]);
$router->post('/users', [UserController::class]);
$router->get('/users/{id}', [UserController::class]);`} />
          </AccordionDetails>
        </Accordion>

        <Accordion>
          <AccordionSummary expandIcon={<ExpandMoreIcon />}>
            <Typography variant="h6">storage/ - Application Storage</Typography>
          </AccordionSummary>
          <AccordionDetails>
            <Typography paragraph>
              The storage directory holds files generated by your application including logs, cache, and state files.
            </Typography>
            
            <List>
              <ListItem>
                <ListItemText
                  primary="cache/"
                  secondary="File-based cache storage when using the 'file' cache driver. Organized by cache keys and TTL."
                />
              </ListItem>
              <ListItem>
                <ListItemText
                  primary="logs/"
                  secondary="Application log files including error logs, access logs, and custom application logs."
                />
              </ListItem>
              <ListItem>
                <ListItemText
                  primary="ratelimits/"
                  secondary="Rate limiting state files that track request counts per client/endpoint."
                />
              </ListItem>
              <ListItem>
                <ListItemText
                  primary="migrations.json"
                  secondary="Migration state tracking which database migrations have been applied and which are pending."
                />
              </ListItem>
            </List>

            <Callout type="warning" title="Storage Permissions">
              Ensure the <code>storage/</code> directory is writable by your web server. 
              BaseAPI needs to write cache files, logs, and migration state.
            </Callout>
          </AccordionDetails>
        </Accordion>

        <Accordion>
          <AccordionSummary expandIcon={<ExpandMoreIcon />}>
            <Typography variant="h6">translations/ - Internationalization</Typography>
          </AccordionSummary>
          <AccordionDetails>
            <Typography paragraph>
              Translation files organized by language and namespace for internationalization support.
            </Typography>
            
            <List>
              <ListItem>
                <ListItemText
                  primary="Language Directories (en/, de/, es/)"
                  secondary="Each language has its own directory containing JSON files for different translation namespaces."
                />
              </ListItem>
              <ListItem>
                <ListItemText
                  primary="Namespace Files"
                  secondary="JSON files like 'common.json', 'admin.json' that group related translations together."
                />
              </ListItem>
            </List>

            <CodeBlock language="json" code={`// translations/en/common.json
{
  "welcome": "Welcome to our API",
  "user_created": "User created successfully",
  "validation_error": "Please check your input"
}

// translations/de/common.json  
{
  "welcome": "Willkommen zu unserer API",
  "user_created": "Benutzer erfolgreich erstellt",
  "validation_error": "Bitte überprüfen Sie Ihre Eingabe"
}`} />
          </AccordionDetails>
        </Accordion>
      </Box>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Key Files
      </Typography>

      <List>
        <ListItem>
          <ListItemText
            primary=".env"
            secondary="Environment configuration file containing database credentials, API keys, and environment-specific settings. Never commit to version control."
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary=".env.example"
            secondary="Template file showing all available environment variables. Safe to commit to version control."
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="composer.json"
            secondary="Composer configuration file defining project dependencies, autoloading rules, and project metadata."
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="bin/console"
            secondary="Command-line interface for BaseAPI. Used for migrations, code generation, cache management, and more."
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="openapi.json"
            secondary="Generated OpenAPI specification describing your API endpoints, parameters, and responses."
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="public/index.php"
            secondary="Application entry point for traditional web hosting. Routes all requests to the BaseAPI framework."
          />
        </ListItem>
      </List>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Development Workflow
      </Typography>

      <Typography paragraph>
        Here's the typical development workflow when working with BaseAPI's structure:
      </Typography>

      <List>
        <ListItem>
          <ListItemText
            primary="1. Define Models"
            secondary="Create models in app/Models/ to represent your data structures"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="2. Generate Migrations"
            secondary="Run php bin/console migrate:generate to create database schema"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="3. Create Controllers"
            secondary="Build controllers in app/Controllers/ to handle HTTP requests"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="4. Define Routes"
            secondary="Map URLs to controllers in routes/api.php"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="5. Add Services"
            secondary="Create business logic in app/Services/ and register in providers"
          />
        </ListItem>
        <ListItem>
          <ListItemText
            primary="6. Configure Environment"
            secondary="Set up database, cache, and other settings in .env"
          />
        </ListItem>
      </List>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Customization
      </Typography>

      <Typography paragraph>
        While BaseAPI follows conventions, you can customize the structure when needed:
      </Typography>

      <CodeBlock language="php" code={`<?php
// Custom model table names
class Product extends BaseModel
{
    protected static ?string $table = 'product_catalog';
}

// Custom namespace organization
namespace App\\Controllers\\Admin;
class UserManagementController extends Controller { }

// Custom service organization
namespace App\\Services\\Payment;
class StripeService { }
class PayPalService { }`} />

      <Alert severity="success" sx={{ mt: 4 }}>
        <strong>Best Practices:</strong>
        <br />• Follow the directory conventions for consistency
        <br />• Keep controllers thin, move business logic to services
        <br />• Use meaningful names for models and controllers
        <br />• Organize related functionality into subdirectories
        <br />• Keep configuration files minimal and use environment variables
        <br />• Regularly clean up unused files and dependencies
      </Alert>
    </Box>
  );
}
