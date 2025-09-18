
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

      <Typography paragraph>
        BaseAPI's routing system is designed for simplicity and performance. Routes are defined in 
        <code>routes/api.php</code> and support parameter extraction, middleware assignment, 
        and automatic controller resolution.
      </Typography>

      <Alert severity="info" sx={{ my: 3 }}>
        BaseAPI uses fast route compilation and matching, with minimal overhead even for complex route patterns.
      </Alert>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Basic Route Definition
      </Typography>

      <Typography paragraph>
        Routes are defined using the router instance from the App class. Each route specifies 
        an HTTP method, path pattern, and pipeline of middleware and controller.
      </Typography>

      <CodeBlock language="php" code={`<?php
// routes/api.php

use BaseApi\\App;
use App\\Controllers\\UserController;
use App\\Controllers\\ProductController;

$router = App::router();

// Basic routes
$router->get('/users', [UserController::class]);
$router->post('/users', [UserController::class]);
$router->get('/users/{id}', [UserController::class]);
$router->put('/users/{id}', [UserController::class]);
$router->delete('/users/{id}', [UserController::class]);`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        HTTP Methods
      </Typography>

      <Typography paragraph>
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

      <Typography paragraph>
        Route parameters are defined using curly braces <code>{'{paramName}'}</code> and are automatically 
        injected into controller properties or method parameters.
      </Typography>

      <CodeBlock language="php" code={`<?php
// Route with parameters
$router->get('/users/{id}/posts/{postId}', [PostController::class]);
$router->get('/categories/{category}/products', [ProductController::class]);

// In your controller
class PostController extends Controller
{
    public string $id = '';      // Automatically populated from {id}
    public string $postId = '';  // Automatically populated from {postId}
    
    public function get(): JsonResponse
    {
        $user = User::find($this->id);
        $post = Post::find($this->postId);
        
        if (!$user || !$post) {
            return JsonResponse::notFound();
        }
        
        return JsonResponse::ok($post->jsonSerialize());
    }
}`} />

      <Callout type="tip" title="Parameter Naming">
        Route parameter names must match controller property names exactly. 
        Use camelCase for multi-word parameters: <code>{'{userId}'}</code> matches <code>$userId</code>.
      </Callout>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Middleware Pipeline
      </Typography>

      <Typography paragraph>
        Routes can include middleware in their pipeline. Middleware runs in the order specified, 
        with the controller always being the last item in the pipeline.
      </Typography>

      <CodeBlock language="php" code={`<?php
use BaseApi\\Http\\Middleware\\AuthMiddleware;
use BaseApi\\Http\\Middleware\\RateLimitMiddleware;
use BaseApi\\Http\\Middleware\\CacheResponse;

// Route with middleware
$router->get(
    '/protected-endpoint',
    [
        RateLimitMiddleware::class => ['limit' => '100/1h'],  // Rate limiting
        AuthMiddleware::class,                                 // Authentication  
        CacheResponse::class => ['ttl' => 300],               // Response caching
        ProtectedController::class,                           // Final controller
    ]
);

// Multiple middleware with different configurations
$router->post(
    '/auth/login',
    [
        RateLimitMiddleware::class => ['limit' => '5/1m'],    // Strict rate limiting for auth
        LoginController::class,
    ]
);`} />

      <Alert severity="success" sx={{ mt: 4 }}>
        <strong>Best Practices:</strong>
        <br />• Place specific routes before general ones
        <br />• Use descriptive route parameter names
        <br />• Group related routes together
        <br />• Apply middleware judiciously for performance
        <br />• Use RESTful conventions for consistency
      </Alert>
    </Box>
  );
}
