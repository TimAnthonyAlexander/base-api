import { Box, Typography, Alert, List, ListItem, ListItemText } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

export default function SecurityOverview() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Security Overview
            </Typography>
            <Typography variant="h5" color="text.secondary" paragraph>
                BaseAPI's built-in security features and best practices
            </Typography>

            <Typography>
                BaseAPI is designed with security-by-default principles. It includes comprehensive
                input validation, CSRF protection, secure headers, rate limiting, and authentication
                middleware to protect your applications.
            </Typography>

            <Alert severity="warning" sx={{ my: 3 }}>
                Security is a shared responsibility. BaseAPI provides the tools, but proper
                implementation and configuration are essential for secure applications.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Built-in Security Features
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Input Validation"
                        secondary="Comprehensive validation rules with automatic sanitization"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="CSRF Protection"
                        secondary="Built-in CSRF middleware for state-changing operations"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Rate Limiting"
                        secondary="Configurable rate limiting to prevent abuse"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Security Headers"
                        secondary="Automatic security headers (X-Frame-Options, X-XSS-Protection, etc.)"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="SQL Injection Prevention"
                        secondary="Prepared statements and query builder prevent SQL injection"
                    />
                </ListItem>
            </List>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Validation & Input Security
            </Typography>

            <CodeBlock language="php" code={`<?php

class UserController extends Controller
{
    public function post(): JsonResponse
    {
        // Comprehensive input validation
        $this->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|max:255',
            'age' => 'required|integer|min:18|max:120',
            'website' => 'url|max:255',
        ]);
        
        // Input is automatically sanitized and validated
        $user = new User();
        $user->email = $this->email; // Safe, validated email
        $user->password = password_hash($this->password, PASSWORD_DEFAULT);
        $user->save();
        
        return JsonResponse::created($user->jsonSerialize());
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Database Security
            </Typography>

            <CodeBlock language="bash" code={`# Environment-based database credentials
DB_HOST=127.0.0.1
DB_NAME=baseapi_production
DB_USER=app_user
DB_PASSWORD=secure_random_password

# BaseAPI uses prepared statements automatically
# No raw SQL execution by default`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Authentication & Authorization
            </Typography>

            <Typography paragraph>
                BaseAPI provides multiple authentication methods that all populate the request object consistently:
            </Typography>

            <CodeBlock language="php" code={`<?php

// Protect routes with authentication middleware
$router->post('/admin/users', [
    AuthMiddleware::class,
    RateLimitMiddleware::class => ['limit' => '10/1m'],
    AdminUserController::class
]);

// Multiple middleware layers
$router->delete('/users/{id}', [
    AuthMiddleware::class,
    RateLimitMiddleware::class => ['limit' => '5/1m'],
    UserController::class
]);

// Combined authentication (supports both session and API tokens)
$router->get('/profile', [
    App\\Middleware\\CombinedAuthMiddleware::class,
    ProfileController::class
]);`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3, mb: 2 }}>
                Accessing Authenticated Users
            </Typography>

            <Alert severity="info" sx={{ my: 2 }}>
                Always use <code>$this-&gt;request-&gt;user</code> to access authenticated user data. 
                Never access <code>$_SESSION</code> directly as this bypasses the authentication 
                abstraction and breaks compatibility with API token authentication.
            </Alert>

            <CodeBlock language="php" code={`<?php

class SecureController extends Controller
{
    public function get(): JsonResponse
    {
        // ✅ CORRECT: Access authenticated user
        $user = $this->request->user;
        
        // User is available regardless of auth method (session or token)
        return JsonResponse::ok([
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ]);
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Rate Limiting
            </Typography>

            <CodeBlock language="php" code={`<?php

// Apply rate limiting to sensitive endpoints
$router->post('/login', [
    RateLimitMiddleware::class => ['limit' => '5/1m'], // 5 attempts per minute
    LoginController::class
]);

$router->post('/api/data', [
    AuthMiddleware::class,
    RateLimitMiddleware::class => ['limit' => '100/1h'], // 100 requests per hour
    DataController::class
]);`} />

            <Callout type="warning" title="Security Checklist">
                • Always validate and sanitize user input
                • Use HTTPS in production
                • Keep dependencies updated
                • Implement proper authentication
                • Never access $_SESSION directly - use $this-&gt;request-&gt;user
                • Apply rate limiting to public endpoints
                • Use environment variables for secrets
                • Enable security headers
                • Log security events
            </Callout>

            <Alert severity="success" sx={{ mt: 4 }}>
                <strong>Security Best Practices:</strong>
                <br />• Never trust user input - always validate
                <br />• Use BaseAPI's built-in validation and middleware
                <br />• Never access $_SESSION directly - use $this-&gt;request-&gt;user and $this-&gt;request-&gt;session
                <br />• Keep your environment variables secure
                <br />• Apply rate limiting to prevent abuse
                <br />• Use HTTPS and security headers in production
                <br />• Regularly update dependencies
                <br />• Monitor and log security events
            </Alert>
        </Box>
    );
}
