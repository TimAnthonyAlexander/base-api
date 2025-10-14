
import { Box, Typography, Alert, List, ListItem, ListItemText, Accordion, AccordionSummary, AccordionDetails } from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

export default function ProjectStructure() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Project Structure
            </Typography>

            <Typography variant="h5" color="text.secondary" paragraph>
                Understanding how BaseAPI organizes your code and configuration.
            </Typography>

            <Typography>
                BaseAPI follows a simple, predictable structure that keeps your code organized and easy to navigate.
                Each directory has a specific purpose, and the framework uses conventions to minimize configuration.
            </Typography>

            <Alert severity="info" sx={{ my: 3 }}>
                BaseAPI uses convention over configuration - most directories and files follow predictable naming patterns
                that reduce the need for manual setup.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Core Directories
            </Typography>

            <Box sx={{ mt: 3 }}>
                <Accordion>
                    <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                        <Typography variant="h6">.githooks/ - Code Quality Automation</Typography>
                    </AccordionSummary>
                    <AccordionDetails>
                        <Typography>
                            The <code>.githooks/</code> directory contains automated code quality checks that run before commits.
                            These hooks are automatically installed when creating a new BaseAPI project.
                        </Typography>

                        <List>
                            <ListItem>
                                <ListItemText
                                    primary="pre-commit"
                                    secondary="Runs PHP syntax checks, PHPStan analysis, tests, and validates composer dependencies before allowing commits."
                                />
                            </ListItem>
                            <ListItem>
                                <ListItemText
                                    primary="setup.sh"
                                    secondary="Installation script that copies hooks to .git/hooks/ and makes them executable."
                                />
                            </ListItem>
                            <ListItem>
                                <ListItemText
                                    primary="README.md"
                                    secondary="Comprehensive documentation on using and customizing the git hooks system."
                                />
                            </ListItem>
                        </List>

                        <Callout type="info">
                            <Typography>
                                Git hooks are automatically installed via <code>composer setup-hooks</code> during project creation.
                                They help maintain code quality by preventing commits with syntax errors or failing tests.
                            </Typography>
                        </Callout>
                    </AccordionDetails>
                </Accordion>

                <Accordion>
                    <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                        <Typography variant="h6">app/ - Application Code</Typography>
                    </AccordionSummary>
                    <AccordionDetails>
                        <Typography>
                            The <code>app/</code> directory contains all your application-specific code. This is where you'll
                            spend most of your development time.
                        </Typography>

                        <List>
                            <ListItem>
                                <ListItemText
                                    primary="Auth/"
                                    secondary="Authentication-related classes like user providers and custom authentication logic."
                                />
                            </ListItem>
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
                                    primary="Providers/"
                                    secondary="Service providers that register and configure services in the dependency injection container."
                                />
                            </ListItem>
                            <ListItem>
                                <ListItemText
                                    primary="Services/"
                                    secondary="Business logic classes that encapsulate complex operations. Services are injected into controllers via dependency injection."
                                />
                            </ListItem>
                        </List>

                        <CodeBlock language="bash" code={`app/Auth/
└── SimpleUserProvider.php

app/Controllers/
├── BenchmarkController.php
├── HealthController.php
├── LoginController.php
├── SignupController.php
└── FileUploadController.php

app/Models/
└── User.php

app/Providers/
└── AppServiceProvider.php

app/Services/
└── EmailService.php`} />
                    </AccordionDetails>
                </Accordion>

                <Accordion>
                    <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                        <Typography variant="h6">config/ - Configuration Files</Typography>
                    </AccordionSummary>
                    <AccordionDetails>
                        <Typography>
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
                        <Typography>
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
                        <Typography>
                            The storage directory holds files generated by your application. Some subdirectories are
                            included in the template, others are created automatically at runtime.
                        </Typography>

                        <List>
                            <ListItem>
                                <ListItemText
                                    primary="app/"
                                    secondary="File storage root directory for uploaded files and application assets."
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
                                    secondary="Generated migration statements with metadata. Each migration contains SQL, operation type, and destructive status."
                                />
                            </ListItem>
                            <ListItem>
                                <ListItemText
                                    primary="executed-migrations.json (auto-created)"
                                    secondary="Tracks which migrations have been applied to the database with execution timestamps."
                                />
                            </ListItem>
                            <ListItem>
                                <ListItemText
                                    primary="database.sqlite"
                                    secondary="Default SQLite database file created when using SQLite driver."
                                />
                            </ListItem>
                            <ListItem>
                                <ListItemText
                                    primary="cache/ (auto-created)"
                                    secondary="File-based cache storage when using the 'file' cache driver. Created automatically when needed."
                                />
                            </ListItem>
                            <ListItem>
                                <ListItemText
                                    primary="logs/ (auto-created)"
                                    secondary="Application log files including error logs, access logs, and custom application logs."
                                />
                            </ListItem>
                        </List>

                        <Callout type="warning" title="Storage Permissions">
                            Ensure the <code>storage/</code> directory is writable by your web server.
                            BaseAPI needs to write cache files, logs, database files, and migration state.
                        </Callout>
                    </AccordionDetails>
                </Accordion>

                <Accordion>
                    <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                        <Typography variant="h6">translations/ - Internationalization</Typography>
                    </AccordionSummary>
                    <AccordionDetails>
                        <Typography>
                            Translation files organized by language and namespace for internationalization support.
                            The template includes multiple languages with common namespace files.
                        </Typography>

                        <List>
                            <ListItem>
                                <ListItemText
                                    primary="Language Directories"
                                    secondary="Supported languages: en/ (English), de/ (German), es/ (Spanish), fr/ (French), nl/ (Dutch)."
                                />
                            </ListItem>
                            <ListItem>
                                <ListItemText
                                    primary="Namespace Files"
                                    secondary="Each language contains 'common.json' for general translations and 'admin.json' for admin-specific translations."
                                />
                            </ListItem>
                        </List>

                        <CodeBlock language="bash" code={`translations/
├── en/
│   ├── admin.json      # English admin translations
│   └── common.json     # English common translations
├── de/
│   ├── admin.json      # German admin translations
│   └── common.json     # German common translations
├── es/                 # Spanish translations
├── fr/                 # French translations
└── nl/                 # Dutch translations`} />

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
                        primary="composer.json"
                        secondary="Composer configuration file defining project dependencies, autoloading rules, scripts, and project metadata."
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="composer.lock"
                        secondary="Locked dependency versions ensuring consistent installs across environments. Generated by Composer."
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="mason"
                        secondary="Console command shim that forwards all commands to vendor/mason (the actual BaseAPI CLI)."
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="phpstan.neon"
                        secondary="PHPStan static analysis configuration for code quality checks."
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
                        primary="types.ts"
                        secondary="TypeScript type definitions generated from your API controllers for frontend integration."
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="README.md"
                        secondary="Project documentation with quick start guide, git hooks information, and dependency injection examples."
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="public/index.php"
                        secondary="Application entry point for traditional web hosting. Routes all requests to the BaseAPI framework."
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary=".env (created by setup)"
                        secondary="Environment configuration file created from .env.example during project setup. Contains database credentials and settings."
                    />
                </ListItem>
            </List>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Development Workflow
            </Typography>

            <Typography>
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
                        secondary="Run ./mason migrate:generate to create database schema"
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

            <Typography>
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

            <Callout type="info">
                <Typography>
                    <strong>Missing Directories:</strong> The <code>tests/</code> directory is referenced in composer.json but not included in the template.
                    Create it manually when adding tests: <code>mkdir tests</code>. The git hooks will automatically run tests when this directory contains test files.
                </Typography>
            </Callout>

            <Alert severity="success" sx={{ mt: 4 }}>
                <strong>Best Practices:</strong>
                <br />• Follow the directory conventions for consistency
                <br />• Keep controllers thin, move business logic to services
                <br />• Use meaningful names for models and controllers
                <br />• Organize related functionality into subdirectories
                <br />• Keep configuration files minimal and use environment variables
                <br />• Let git hooks maintain code quality automatically
                <br />• Use the comprehensive config/app.php for environment-specific settings
                <br />• Regularly clean up unused files and dependencies
            </Alert>
        </Box>
    );
}
