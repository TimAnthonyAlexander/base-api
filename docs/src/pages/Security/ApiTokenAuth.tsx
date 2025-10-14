import { Box, Typography, Alert, List, ListItem, ListItemText, Divider, Chip } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

export default function ApiTokenAuth() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                API Token Authentication
            </Typography>
            <Typography variant="h5" color="text.secondary" paragraph>
                Secure API access using tokens alongside session-based authentication
            </Typography>

            <Typography>
                BaseAPI supports dual authentication methods: traditional session-based authentication for web interfaces
                and API token authentication for programmatic access. This allows you to build APIs that serve both
                web frontends and external integrations seamlessly.
            </Typography>

            <Alert severity="info" sx={{ my: 3 }}>
                API tokens work alongside existing session authentication - you don't need to change your existing routes.
                Choose the authentication method that best fits each endpoint's use case.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Quick Start
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3, mb: 2 }}>
                1. Generate Database Schema
            </Typography>
            <Typography paragraph>
                The <code>ApiToken</code> model automatically generates the necessary database tables:
            </Typography>

            <CodeBlock language="bash" code={`./mason migrate:generate
./mason migrate:apply`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3, mb: 2 }}>
                2. Choose Authentication Method
            </Typography>

            <Typography paragraph>
                BaseAPI provides three middleware options for different authentication needs:
            </Typography>

            <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1, mb: 3 }}>
                <Chip label="AuthMiddleware" color="default" variant="outlined" />
                <Chip label="App\Middleware\ApiTokenAuthMiddleware" color="primary" variant="outlined" />
                <Chip label="App\Middleware\CombinedAuthMiddleware" color="secondary" variant="outlined" />
            </Box>

            <CodeBlock language="php" code={`<?php

// Session-only authentication (existing)
$router->get('/me', [
    AuthMiddleware::class,
    MeController::class,
]);

// API token-only authentication (new)
$router->get('/api/me', [
    App\Middleware\ApiTokenAuthMiddleware::class,
    MeController::class,
]);

// Combined authentication - supports both (new)
$router->get('/profile', [
    App\Middleware\App\\Middleware\\CombinedAuthMiddleware::class,
    MeController::class,
]);`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Token Management
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3, mb: 2 }}>
                Creating API Tokens
            </Typography>

            <Typography paragraph>
                Users can create API tokens through the web interface using session authentication:
            </Typography>

            <CodeBlock language="bash" code={`curl -X POST http://localhost:8080/api-tokens \\
  -H "Content-Type: application/json" \\
  -H "Cookie: BASEAPISESSID=your_session_id" \\
  -d '{
    "name": "Mobile App Token",
    "expires_at": "2024-12-31 23:59:59"
  }'`} />

            <Typography paragraph>
                <strong>Response:</strong>
            </Typography>

            <CodeBlock language="json" code={`{
  "token": "abcd1234efgh5678...", 
  "id": "token-uuid",
  "name": "Mobile App Token",
  "expires_at": "2024-12-31 23:59:59",
  "created_at": "2024-01-01 12:00:00"
}`} />

            <Alert severity="warning" sx={{ my: 2 }}>
                <strong>Important:</strong> The plain token is only shown once during creation. 
                Store it securely - you cannot retrieve it again.
            </Alert>

            <Typography variant="h3" gutterBottom sx={{ mt: 3, mb: 2 }}>
                Using API Tokens
            </Typography>

            <Typography paragraph>
                Include the token in the <code>Authorization</code> header with the <code>Bearer</code> scheme:
            </Typography>

            <CodeBlock language="bash" code={`curl http://localhost:8080/api/me \\
  -H "Authorization: Bearer your_api_token_here"`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3, mb: 2 }}>
                Managing Tokens
            </Typography>

            <CodeBlock language="bash" code={`# List all tokens for the authenticated user
curl http://localhost:8080/api-tokens \\
  -H "Cookie: BASEAPISESSID=your_session_id"

# Revoke a specific token
curl -X DELETE http://localhost:8080/api-tokens/token-id \\
  -H "Cookie: BASEAPISESSID=your_session_id"`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Implementation Details
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3, mb: 2 }}>
                ApiToken Model Features
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Secure Storage"
                        secondary="Only SHA256 hashes are stored in the database, never plain tokens"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Expiration Support"
                        secondary="Optional expiration dates for automatic token cleanup"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Usage Tracking"
                        secondary="last_used_at timestamp updated on each request"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="User Scoping"
                        secondary="Tokens are user-specific and cannot access other users' data"
                    />
                </ListItem>
            </List>

            <Typography variant="h3" gutterBottom sx={{ mt: 3, mb: 2 }}>
                Accessing Authenticated User Data
            </Typography>

            <Typography paragraph>
                Both authentication methods populate the request with consistent user data.
                Always use <code>$this-&gt;request-&gt;user</code> to access the authenticated user:
            </Typography>

            <CodeBlock language="php" code={`<?php

namespace App\\Controllers;

use BaseApi\\Controller;
use BaseApi\\Http\\JsonResponse;

class ExampleController extends Controller
{
    public function get(): JsonResponse
    {
        // ✅ CORRECT: Access user via request object
        $user = $this->request->user;
        
        // ✅ CORRECT: Check which authentication method was used
        $authMethod = $this->request->authMethod; // "session" or "api_token"
        
        // ✅ CORRECT: Access session data if needed
        $sessionData = $this->request->session;
        
        return JsonResponse::ok([
            'user' => $user,
            'authenticated_via' => $authMethod,
            'message' => 'Hello, ' . $user['name']
        ]);
    }
}`} />

            <Alert severity="error" sx={{ my: 2 }}>
                <strong>Never access $_SESSION directly!</strong> Always use <code>$this-&gt;request-&gt;user</code> for 
                authenticated user data and <code>$this-&gt;request-&gt;session</code> for session variables. 
                Direct $_SESSION access bypasses the framework's authentication abstraction and breaks compatibility 
                with both session-based and token-based authentication.
            </Alert>

            <CodeBlock language="php" code={`<?php

// ❌ WRONG: Direct $_SESSION access
$userId = $_SESSION['user_id'] ?? null;
$_SESSION['user_id'] = $user->id;

// ✅ CORRECT: Use request object
$user = $this->request->user; // Works for both session and token auth
$this->request->session['user_id'] = $user->id; // If you need to modify session`} />

            <Divider sx={{ my: 4 }} />

            <Typography variant="h2" gutterBottom>
                Common Use Cases
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3, mb: 2 }}>
                1. Web + API Application
            </Typography>

            <CodeBlock language="php" code={`<?php

// Web interface routes (session auth for CSRF protection)
$router->get('/dashboard', [AuthMiddleware::class, DashboardController::class]);
$router->get('/api-tokens', [AuthMiddleware::class, ApiTokenController::class]);

// Public API routes (token auth for external integrations) 
$router->get('/api/users', [
    App\\Middleware\\ApiTokenAuthMiddleware::class, 
    RateLimitMiddleware::class => ['limit' => '100/1h'],
    UserController::class
]);

// Shared endpoints (both auth methods supported)
$router->get('/profile', [App\\Middleware\\CombinedAuthMiddleware::class, ProfileController::class]);`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3, mb: 2 }}>
                2. Mobile App Integration
            </Typography>

            <CodeBlock language="php" code={`<?php

// Mobile apps can use either tokens (native) or sessions (web view)
$router->get('/api/posts', [
    App\\Middleware\\CombinedAuthMiddleware::class,
    RateLimitMiddleware::class => ['limit' => '200/1h'],
    PostController::class
]);

// Push notification endpoints (token-only for background services)
$router->post('/api/notifications/register', [
    App\\Middleware\\ApiTokenAuthMiddleware::class,
    NotificationController::class
]);`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3, mb: 2 }}>
                3. Third-Party Integrations
            </Typography>

            <CodeBlock language="php" code={`<?php

// Strict token-only authentication for external services
$router->post('/webhook/payments', [
    App\\Middleware\\ApiTokenAuthMiddleware::class,
    RateLimitMiddleware::class => ['limit' => '1000/1h'],
    WebhookController::class
]);

// API for partner integrations
$router->get('/api/export/data', [
    App\\Middleware\\ApiTokenAuthMiddleware::class,
    RateLimitMiddleware::class => ['limit' => '50/1h'],
    ExportController::class
]);`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Migration Strategies
            </Typography>

            <Alert severity="success" sx={{ my: 3 }}>
                <strong>Backward Compatibility:</strong> All existing routes continue to work unchanged.
                You can gradually adopt API token authentication at your own pace.
            </Alert>

            <Typography variant="h3" gutterBottom sx={{ mt: 3, mb: 2 }}>
                Gradual Migration
            </Typography>

            <CodeBlock language="php" code={`<?php

// Option 1: Keep existing session routes as-is
$router->get('/users', [AuthMiddleware::class, UserController::class]);

// Option 2: Switch to combined auth to support both methods
$router->get('/users', [App\\Middleware\\CombinedAuthMiddleware::class, UserController::class]);

// Option 3: Create parallel API routes
$router->get('/api/users', [ApiTokenAuthMiddleware::class, UserController::class]);`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Security Best Practices
            </Typography>

            <Callout type="warning" title="API Token Security">
                • Generate tokens with sufficient entropy (64+ characters)
                • Store only hashed versions in the database
                • Set expiration dates for enhanced security
                • Monitor token usage via last_used_at timestamps
                • Rotate tokens regularly for sensitive applications
                • Apply rate limiting to token-authenticated endpoints
                • Use HTTPS to protect tokens in transit
            </Callout>

            <Typography variant="h3" gutterBottom sx={{ mt: 3, mb: 2 }}>
                Rate Limiting Recommendations
            </Typography>

            <CodeBlock language="php" code={`<?php

// Different rate limits for different auth methods
$router->get('/api/data', [
    App\\Middleware\\ApiTokenAuthMiddleware::class,
    RateLimitMiddleware::class => ['limit' => '1000/1h'], // Higher limit for authenticated API
    DataController::class
]);

$router->get('/public/data', [
    RateLimitMiddleware::class => ['limit' => '100/1h'], // Lower limit for public access
    DataController::class
]);`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Error Handling
            </Typography>

            <Typography paragraph>
                All authentication middleware return consistent error responses:
            </Typography>

            <CodeBlock language="json" code={`{
  "error": "Unauthorized",
  "status": 401
}`} />

            <Alert severity="info" sx={{ mt: 4 }}>
                <strong>Need Help?</strong> API token authentication integrates seamlessly with BaseAPI's 
                existing validation, rate limiting, and error handling systems. Check the Security Overview 
                and Architecture sections for more details on building secure APIs.
            </Alert>
        </Box>
    );
}
