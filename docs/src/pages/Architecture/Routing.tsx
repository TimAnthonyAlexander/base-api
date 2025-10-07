
import { Box, Typography, Alert, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

export default function Routing() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Routing
            </Typography>

            <Typography variant="h5" color="text.secondary" paragraph>
                How BaseAPI handles HTTP routing and URL patterns.
            </Typography>

            <Typography>
                BaseAPI's routing system maps HTTP requests to controllers with minimal configuration.
                Routes are defined in <code>routes/api.php</code> and support automatic parameter extraction,
                middleware pipelines, and fast pattern matching.
            </Typography>

            <Alert severity="info" sx={{ my: 3 }}>
                Routes are compiled to optimized regex patterns for fast matching, even with complex parameter patterns.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Basic Route Definition
            </Typography>

            <Typography>
                Routes map HTTP methods and URL patterns to controllers. The router automatically
                handles parameter extraction and controller method selection.
            </Typography>

            <CodeBlock language="php" code={`<?php
// routes/api.php

use BaseApi\\App;
use App\\Controllers\\UserController;

$router = App::router();

// Basic CRUD routes
$router->get('/users', [UserController::class]);      // List users
$router->post('/users', [UserController::class]);     // Create user
$router->get('/users/{id}', [UserController::class]); // Get user by ID
$router->put('/users/{id}', [UserController::class]); // Update user
$router->delete('/users/{id}', [UserController::class]); // Delete user`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                HTTP Methods
            </Typography>

            <Typography>
                BaseAPI supports all standard HTTP methods for RESTful API design:
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Method</strong></TableCell>
                            <TableCell><strong>Usage</strong></TableCell>
                            <TableCell><strong>Example</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            <TableCell><code>GET</code></TableCell>
                            <TableCell>Retrieve resources</TableCell>
                            <TableCell><code>$router-{'>'}get('/users', [UserController::class]);</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>POST</code></TableCell>
                            <TableCell>Create resources</TableCell>
                            <TableCell><code>$router-{'>'}post('/users', [UserController::class]);</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>PUT</code></TableCell>
                            <TableCell>Update/replace resources</TableCell>
                            <TableCell><code>$router-{'>'}put('/users/{'{'}id{'}'}, [UserController::class]);</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>PATCH</code></TableCell>
                            <TableCell>Partial resource updates</TableCell>
                            <TableCell><code>$router-{'>'}patch('/users/{'{'}id{'}'}, [UserController::class]);</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>DELETE</code></TableCell>
                            <TableCell>Remove resources</TableCell>
                            <TableCell><code>$router-{'>'}delete('/users/{'{'}id{'}'}, [UserController::class]);</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>OPTIONS</code></TableCell>
                            <TableCell>CORS preflight requests</TableCell>
                            <TableCell><code>$router-{'>'}options('/users', [CorsController::class]);</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>HEAD</code></TableCell>
                            <TableCell>Metadata without body</TableCell>
                            <TableCell><code>$router-{'>'}head('/users/{'{'}id{'}'}, [UserController::class]);</code></TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Route Parameters
            </Typography>

            <Typography>
                Route parameters use <code>{'{paramName}'}</code> syntax and are automatically
                injected into matching controller properties.
            </Typography>

            <CodeBlock language="php" code={`<?php
// Routes with parameters
$router->get('/users/{id}', [UserController::class]);
$router->get('/categories/{category}/products', [ProductController::class]);

// Controller automatically receives parameters
class UserController extends Controller
{
    public string $id = '';      // Auto-populated from {id} parameter
    
    public function get(): JsonResponse
    {
        $user = User::find($this->id);
        
        if (!$user) {
            return JsonResponse::notFound('User not found');
        }
        
        return JsonResponse::ok($user);
    }
}`} />

            <Callout type="tip">
                Parameter names must match controller property names exactly.
                Use camelCase: <code>{'{userId}'}</code> → <code>$userId</code>.
            </Callout>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Middleware Pipeline
            </Typography>

            <Typography>
                Routes can include middleware that runs before the controller.
                Middleware executes in the order specified, ending with the controller.
            </Typography>

            <CodeBlock language="php" code={`<?php
use BaseApi\\Http\\Middleware\\AuthMiddleware;
use BaseApi\\Http\\Middleware\\RateLimitMiddleware;

// Protected route with authentication
$router->get(
    '/users/{id}',
    [
        AuthMiddleware::class,        // Require authentication
        UserController::class,       // Then run controller
    ]
);

// Rate-limited endpoint
$router->post(
    '/auth/login',
    [
        RateLimitMiddleware::class => ['limit' => '5/1m'],  // 5 requests per minute
        LoginController::class,
    ]
);`} />

            <Alert severity="success" sx={{ mt: 4 }}>
                <strong>Routing Best Practices:</strong>
                <br />• Use RESTful conventions: GET for retrieval, POST for creation
                <br />• Place specific routes before general patterns
                <br />• Apply middleware only where needed to avoid overhead
                <br />• Group related routes together for maintainability
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 5 }}>
                Route Caching & Performance
            </Typography>

            <Typography paragraph>
                BaseAPI includes a high-performance route compilation system that optimizes routing
                dispatch by pre-computing route structures and eliminating runtime overhead.
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                How Route Compilation Works
            </Typography>

            <Typography paragraph>
                The router uses a two-tier dispatch system:
            </Typography>

            <Box component="ul" sx={{ pl: 3 }}>
                <li>
                    <Typography>
                        <strong>Static routes</strong> (no parameters) use O(1) hash map lookups
                    </Typography>
                </li>
                <li>
                    <Typography>
                        <strong>Dynamic routes</strong> (with parameters) use segment-based matching
                    </Typography>
                </li>
            </Box>

            <Typography paragraph sx={{ mt: 2 }}>
                When routes are compiled:
            </Typography>

            <Box component="ul" sx={{ pl: 3 }}>
                <li>Routes are separated into static and dynamic groups</li>
                <li>Middleware stacks are pre-merged at compile time</li>
                <li>Parameter constraints are pre-compiled to regex patterns</li>
                <li>All data is exported as pure PHP arrays for Opcache optimization</li>
            </Box>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Caching Routes
            </Typography>

            <Typography paragraph>
                In production, compile your routes to maximize performance:
            </Typography>

            <CodeBlock language="bash" code={`# Compile routes to cache
./mason route:cache

# View all registered routes
./mason route:list

# Clear route cache (development)
./mason route:clear`} />

            <Alert severity="warning" sx={{ my: 3 }}>
                <strong>Important:</strong> After modifying routes, remember to run <code>route:cache</code>
                again in production. The cache is automatically bypassed in development when not present.
            </Alert>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Performance Benefits
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Scenario</strong></TableCell>
                            <TableCell><strong>Without Cache</strong></TableCell>
                            <TableCell><strong>With Cache</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            <TableCell>Static route lookup</TableCell>
                            <TableCell>O(n) scan through routes</TableCell>
                            <TableCell>O(1) hash map lookup</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell>Dynamic route matching</TableCell>
                            <TableCell>Regex match on every route</TableCell>
                            <TableCell>Segment-based matching with early exit</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell>Middleware resolution</TableCell>
                            <TableCell>Merged per request</TableCell>
                            <TableCell>Pre-merged at compile time</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell>Memory allocation</TableCell>
                            <TableCell>New objects per request</TableCell>
                            <TableCell>Zero-allocation hot path</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>

            <Callout type="tip" title="Automatic Cache Detection">
                The router automatically detects and loads compiled routes if present.
                No code changes are needed - just run <code>route:cache</code> and your
                application immediately benefits from optimized routing.
            </Callout>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Cache Invalidation
            </Typography>

            <Typography paragraph>
                The route cache is a simple PHP file stored in <code>storage/cache/routes.php</code>.
                It's automatically invalidated when:
            </Typography>

            <Box component="ul" sx={{ pl: 3 }}>
                <li>You run <code>./mason route:clear</code></li>
                <li>You manually delete the cache file</li>
                <li>You redeploy with <code>route:cache</code> (overwrites existing cache)</li>
            </Box>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Deployment Workflow
            </Typography>

            <CodeBlock language="bash" code={`# In your deployment script (after composer install)
./mason route:cache
./mason cache:clear  # Clear application caches
./mason migrate:apply  # Run migrations

# Restart PHP-FPM/workers if using long-running processes
sudo service php8.4-fpm restart`} />

            <Alert severity="info" sx={{ my: 3 }}>
                <strong>Opcache Optimization:</strong> Compiled routes are designed to work
                seamlessly with Opcache. The cache file uses immutable data structures and
                readonly classes that Opcache can fully optimize.
            </Alert>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Backwards Compatibility
            </Typography>

            <Typography paragraph>
                Route compilation is fully backwards compatible. If no cache file exists,
                the router automatically falls back to traditional route matching. This means:
            </Typography>

            <Box component="ul" sx={{ pl: 3 }}>
                <li>Development works without caching (automatic reloading)</li>
                <li>Existing applications work without any code changes</li>
                <li>You can enable caching gradually on specific environments</li>
                <li>The same codebase works with or without the cache</li>
            </Box>
        </Box>
    );
}
