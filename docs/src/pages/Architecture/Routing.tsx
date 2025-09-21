
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
        </Box>
    );
}
