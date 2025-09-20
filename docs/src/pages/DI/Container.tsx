
import { Box, Typography, Alert, List, ListItem, ListItemText } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

export default function Container() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Dependency Injection Container
            </Typography>
            <Typography variant="h5" color="text.secondary" paragraph>
                Understanding BaseAPI's powerful DI container with auto-wiring
            </Typography>

            <Typography>
                BaseAPI includes a built-in dependency injection container that automatically resolves
                dependencies and manages service lifecycles. Controllers and services receive their
                dependencies through constructor injection without manual configuration.
            </Typography>

            <Alert severity="info" sx={{ my: 3 }}>
                BaseAPI's DI container uses auto-wiring based on type hints, making dependency injection
                seamless and reducing boilerplate code.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Automatic Dependency Injection
            </Typography>

            <Typography>
                Controllers automatically receive dependencies through constructor injection:
            </Typography>

            <CodeBlock language="php" code={`<?php

namespace App\\Controllers;

use BaseApi\\Controllers\\Controller;
use BaseApi\\Http\\JsonResponse;
use App\\Services\\EmailService;
use App\\Services\\UserService;

class UserController extends Controller
{
    // Dependencies are automatically injected
    public function __construct(
        private EmailService $emailService,
        private UserService $userService
    ) {}
    
    public string $email = '';
    public string $name = '';
    
    public function post(): JsonResponse
    {
        // Use injected services
        $user = $this->userService->createUser($this->name, $this->email);
        $this->emailService->sendWelcome($user);
        
        return JsonResponse::created($user->jsonSerialize());
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Container Methods
            </Typography>

            <Typography>
                Access the container directly when needed:
            </Typography>

            <CodeBlock language="php" code={`<?php

// Access the container globally
$container = \\BaseApi\\App::container();

// Resolve a service
$emailService = $container->make(EmailService::class);

// In controllers, use the helper methods
class SomeController extends Controller
{
    public function someMethod()
    {
        // Get the container
        $container = $this->container();
        
        // Resolve a service
        $service = $this->make(SomeService::class);
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Binding Services
            </Typography>

            <Typography>
                Register services in the container using various binding methods:
            </Typography>

            <CodeBlock language="php" code={`<?php

$container = App::container();

// Simple binding
$container->bind(ServiceInterface::class, ConcreteService::class);

// Singleton binding (shared instance)
$container->singleton(ExpensiveService::class);

// Instance binding (specific instance)
$container->instance(ConfigService::class, $configInstance);

// Factory binding with closure
$container->bind(ComplexService::class, function($container) {
    return new ComplexService(
        $container->make(DependencyA::class),
        $container->make(DependencyB::class)
    );
});

// Contextual binding
$container->when(EmailService::class)
    ->needs(ApiClient::class)
    ->give(EmailApiClient::class);`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Auto-Wiring
            </Typography>

            <Typography>
                The container automatically resolves dependencies based on type hints:
            </Typography>

            <CodeBlock language="php" code={`<?php

class EmailService
{
    // Dependencies automatically injected
    public function __construct(
        private Logger $logger,
        private Config $config,
        private ApiClient $apiClient
    ) {
        // No manual wiring required
    }
    
    public function sendEmail(string $to, string $subject, string $body): bool
    {
        $this->logger->info("Sending email to: {$to}");
        
        $apiKey = $this->config->get('email.api_key');
        
        return $this->apiClient->sendEmail($to, $subject, $body, $apiKey);
    }
}

// This service is automatically injectable into controllers
class NotificationController extends Controller
{
    public function __construct(
        private EmailService $emailService  // Automatically resolved
    ) {}
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Service Lifecycle Management
            </Typography>

            <Typography>
                The container manages different service lifecycles:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Transient Services"
                        secondary="New instance created every time (default behavior)"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Singleton Services"
                        secondary="Single shared instance across the application"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Instance Services"
                        secondary="Pre-configured instance registered in container"
                    />
                </ListItem>
            </List>

            <CodeBlock language="php" code={`<?php

// Transient (new instance each time)
$container->bind(TransientService::class);

// Singleton (shared instance)
$container->singleton(DatabaseConnection::class);
$container->singleton(CacheManager::class);
$container->singleton(Logger::class);

// Instance (pre-configured object)
$config = new Config($configArray);
$container->instance(Config::class, $config);`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Interface to Implementation Binding
            </Typography>

            <Typography>
                Bind interfaces to concrete implementations for flexible architecture:
            </Typography>

            <CodeBlock language="php" code={`<?php

// Define interface
interface CacheInterface 
{
    public function get(string $key): mixed;
    public function put(string $key, mixed $value, int $ttl = 0): bool;
}

// Implement interface
class RedisCache implements CacheInterface
{
    public function get(string $key): mixed { /* ... */ }
    public function put(string $key, mixed $value, int $ttl = 0): bool { /* ... */ }
}

// Bind interface to implementation
$container->bind(CacheInterface::class, RedisCache::class);

// Controllers can depend on the interface
class ProductController extends Controller
{
    public function __construct(
        private CacheInterface $cache  // Receives RedisCache instance
    ) {}
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Container Resolution Process
            </Typography>

            <Typography>
                Understanding how the container resolves dependencies:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="1. Check for Explicit Binding"
                        secondary="Look for registered bindings or singletons"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="2. Attempt Auto-Wiring"
                        secondary="Use reflection to analyze constructor parameters"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="3. Resolve Dependencies"
                        secondary="Recursively resolve all constructor dependencies"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="4. Create Instance"
                        secondary="Instantiate the class with resolved dependencies"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="5. Cache if Singleton"
                        secondary="Store instance for future requests if registered as singleton"
                    />
                </ListItem>
            </List>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Container in Testing
            </Typography>

            <Typography>
                Mock dependencies for testing by binding test implementations:
            </Typography>

            <CodeBlock language="php" code={`<?php

// In your test
class UserControllerTest extends TestCase
{
    public function testCreateUser()
    {
        $container = App::container();
        
        // Mock the email service
        $mockEmailService = $this->createMock(EmailService::class);
        $mockEmailService->expects($this->once())
                        ->method('sendWelcome');
        
        // Bind the mock
        $container->instance(EmailService::class, $mockEmailService);
        
        // Test the controller
        $controller = $container->make(UserController::class);
        $response = $controller->post();
        
        $this->assertEquals(201, $response->getStatusCode());
    }
}`} />

            <Callout type="tip" title="Performance Note">
                The container caches reflection information and resolved singletons for optimal performance.
                Auto-wiring adds minimal overhead to your application.
            </Callout>

            <Alert severity="success" sx={{ mt: 4 }}>
                <strong>Container Best Practices:</strong>
                <br />• Use constructor injection for required dependencies
                <br />• Register expensive services as singletons
                <br />• Bind interfaces to implementations for flexibility
                <br />• Use the container's make() method sparingly
                <br />• Mock dependencies in tests using instance binding
                <br />• Avoid circular dependencies
            </Alert>
        </Box>
    );
}
