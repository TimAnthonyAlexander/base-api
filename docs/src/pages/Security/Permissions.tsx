import { Box, Typography } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

export default function Permissions() {
    return (
        <Box>
            <Typography variant="h3" gutterBottom>
                Permissions System
            </Typography>

            <Typography variant="body1" paragraph>
                BaseAPI includes a production-ready permissions system with role-based access control (RBAC),
                group inheritance, wildcard matching, and atomic file operations for concurrency safety.
            </Typography>

            {/* Overview */}
            <Typography variant="h4" gutterBottom sx={{ mt: 4 }}>
                Overview
            </Typography>

            <Typography variant="body1" paragraph>
                The permissions system provides:
            </Typography>

            <Box component="ul" sx={{ pl: 3, mb: 2 }}>
                <li><Typography variant="body1">Role-based groups with inheritance chains</Typography></li>
                <li><Typography variant="body1">Weight-based conflict resolution</Typography></li>
                <li><Typography variant="body1">Wildcard permission matching (<code>*</code>, <code>admin.*</code>, <code>content.create.*</code>)</Typography></li>
                <li><Typography variant="body1">File locking for concurrent operations</Typography></li>
                <li><Typography variant="body1">Atomic writes with fsync</Typography></li>
                <li><Typography variant="body1">CLI commands for management</Typography></li>
                <li><Typography variant="body1">Middleware integration for route protection</Typography></li>
                <li><Typography variant="body1">Detailed trace/debugging tools</Typography></li>
            </Box>

            {/* Quick Start */}
            <Typography variant="h4" gutterBottom sx={{ mt: 4 }}>
                Quick Start
            </Typography>

            <Typography variant="body1" paragraph>
                The permissions system is automatically initialized with default groups:
            </Typography>

            <CodeBlock
                language="php"
                title="Default Permission Groups"
                code={`// Default groups created automatically:
// - guest: Basic public access (weight: 0)
// - user: Authenticated users (weight: 10)
// - premium: Premium features (weight: 50)
// - admin: Full access with wildcard (weight: 100)

// Each group inherits from the previous one`}
            />

            <Callout type="info" title="Storage Location">
                Permissions are stored in <code>storage/permissions/permissions.json</code> and automatically
                created on first use.
            </Callout>

            {/* Configuration */}
            <Typography variant="h4" gutterBottom sx={{ mt: 4 }}>
                Configuration
            </Typography>

            <Typography variant="body1" paragraph>
                The permissions system works out of the box. To enable it, register the service provider:
            </Typography>

            <CodeBlock
                language="php"
                title="config/providers.php"
                code={`return [
    BaseApi\\Auth\\AuthServiceProvider::class,
    BaseApi\\Permissions\\PermissionsServiceProvider::class, // Add this
    // ... other providers
];`}
            />

            <Typography variant="body1" paragraph sx={{ mt: 2 }}>
                Ensure your User model or UserProvider implements the <code>getRole()</code> and <code>setRole()</code> methods:
            </Typography>

            <CodeBlock
                language="php"
                title="User Provider Interface"
                code={`interface UserProvider
{
    public function byId(string $id): ?array;
    
    /**
     * Get the role/group ID for a user
     */
    public function getRole(string $id): ?string;
    
    /**
     * Set the role/group ID for a user
     */
    public function setRole(string $id, string $role): bool;
}`}
            />

            {/* Protecting Routes */}
            <Typography variant="h4" gutterBottom sx={{ mt: 4 }}>
                Protecting Routes
            </Typography>

            <Typography variant="body1" paragraph>
                Use <code>PermissionsMiddleware</code> to protect routes:
            </Typography>

            <CodeBlock
                language="php"
                title="routes/api.php"
                showLineNumbers
                code={`use BaseApi\\Permissions\\PermissionsMiddleware;

// Single permission check
$router->post('/content/create', [
    CombinedAuthMiddleware::class,
    PermissionsMiddleware::class => ['node' => 'content.create'],
    ContentController::class,
]);

// Require ALL permissions
$router->post('/content/publish', [
    CombinedAuthMiddleware::class,
    PermissionsMiddleware::class => ['requiresAll' => [
        'content.create',
        'content.publish'
    ]],
    ContentController::class,
]);

// Require ANY permission (at least one)
$router->delete('/content/{id}', [
    CombinedAuthMiddleware::class,
    PermissionsMiddleware::class => ['requiresAny' => [
        'content.delete',
        'admin.content'
    ]],
    ContentController::class,
]);`}
            />

            <Callout type="tip" title="Combined Authentication">
                <code>PermissionsMiddleware</code> requires authentication. Always place it after <code>AuthMiddleware</code>
                or <code>CombinedAuthMiddleware</code>.
            </Callout>

            {/* Permission Nodes */}
            <Typography variant="h4" gutterBottom sx={{ mt: 4 }}>
                Permission Nodes
            </Typography>

            <Typography variant="body1" paragraph>
                Permission nodes follow a hierarchical naming convention:
            </Typography>

            <CodeBlock
                language="plaintext"
                title="Node Format"
                code={`Format: domain.resource.action

Examples:
  content.create
  content.edit
  content.delete
  admin.users.manage
  reports.export.csv
  billing.subscriptions.cancel

Wildcards:
  *                  → Matches everything (universal)
  admin.*            → Matches admin.users, admin.settings, etc.
  reports.export.*   → Matches reports.export.csv, reports.export.pdf, etc.`}
            />

            <Typography variant="body1" paragraph sx={{ mt: 2 }}>
                <strong>Validation rules:</strong>
            </Typography>

            <Box component="ul" sx={{ pl: 3, mb: 2 }}>
                <li><Typography variant="body1">Lowercase alphanumeric only (<code>a-z0-9</code>)</Typography></li>
                <li><Typography variant="body1">Segments separated by dots (<code>.</code>)</Typography></li>
                <li><Typography variant="body1">Wildcard (<code>*</code>) only as full segment or entire node</Typography></li>
                <li><Typography variant="body1">Wildcard must be the last segment</Typography></li>
                <li><Typography variant="body1">Maximum length: 128 characters</Typography></li>
            </Box>

            {/* Resolution Algorithm */}
            <Typography variant="h4" gutterBottom sx={{ mt: 4 }}>
                Resolution Algorithm
            </Typography>

            <Typography variant="body1" paragraph>
                When checking permissions, BaseAPI uses a deterministic resolution algorithm:
            </Typography>

            <CodeBlock
                language="plaintext"
                title="Resolution Steps"
                code={`1. Collect all matching patterns from user's role and inherited groups
2. Sort by specificity (most specific first):
   - content.delete (specificity: 20)
   - content.*      (specificity: 15)
   - admin.*        (specificity: 15)
   - *              (specificity: 0)

3. If multiple patterns have same specificity:
   → Resolve by weight (higher weight wins)

4. If same specificity AND same weight:
   → DENY takes precedence over ALLOW

5. If no matches found:
   → Implicit DENY`}
            />

            <Typography variant="body1" paragraph sx={{ mt: 2 }}>
                <strong>Specificity calculation:</strong>
            </Typography>

            <CodeBlock
                language="plaintext"
                code={`Base score: segments × 10
Wildcard penalty: -5 per wildcard

Examples:
  content.edit.draft → (3 segments × 10) = 30
  content.*          → (2 segments × 10) - 5 = 15
  admin.*            → (2 segments × 10) - 5 = 15
  *                  → 0`}
            />

            {/* CLI Commands */}
            <Typography variant="h4" gutterBottom sx={{ mt: 4 }}>
                CLI Commands
            </Typography>

            <Typography variant="h5" gutterBottom sx={{ mt: 3 }}>
                Group Management
            </Typography>

            <CodeBlock
                language="bash"
                title="Managing Groups"
                code={`# List all groups
./mason perm:group:list

# Show group details
./mason perm:group:show admin

# Create new group
./mason perm:group:create moderator --weight=25

# Set group weight
./mason perm:group:set-weight moderator 30

# Rename group (updates all references)
./mason perm:group:rename moderator mod

# Delete group
./mason perm:group:delete temp-group

# Add parent (inheritance)
./mason perm:group:add-parent premium user

# Remove parent
./mason perm:group:remove-parent premium user`}
            />

            <Typography variant="h5" gutterBottom sx={{ mt: 3 }}>
                Permission Management
            </Typography>

            <CodeBlock
                language="bash"
                title="Managing Permissions"
                code={`# Grant permission (allow)
./mason perm:grant user content.create

# Grant with deny
./mason perm:grant guest admin.* --deny

# Grant wildcard (requires --force for low-weight groups)
./mason perm:grant admin '*' --force

# Revoke permission
./mason perm:revoke user content.delete

# Validate configuration
./mason perm:validate`}
            />

            <Callout type="warning" title="Wildcard Protection">
                Granting wildcard permissions (<code>*</code> or <code>domain.*</code>) to groups with weight &lt; 50
                requires <code>--force</code> flag to prevent accidental over-privileging.
            </Callout>

            <Typography variant="h5" gutterBottom sx={{ mt: 3 }}>
                User Role Management
            </Typography>

            <CodeBlock
                language="bash"
                title="User Roles"
                code={`# Get user's role
./mason perm:user:get-role user@example.com

# Set user's role
./mason perm:user:set-role user@example.com premium

# Check permission
./mason perm:check user@example.com content.create

# Trace permission resolution (debugging)
./mason perm:trace user@example.com content.delete`}
            />

            {/* Trace Output */}
            <Typography variant="h5" gutterBottom sx={{ mt: 3 }}>
                Debugging with Trace
            </Typography>

            <Typography variant="body1" paragraph>
                The <code>perm:trace</code> command provides detailed resolution information:
            </Typography>

            <CodeBlock
                language="plaintext"
                title="Example Trace Output"
                code={`🔍 Permission Trace
════════════════════════════════════════════════════════════════════════════════

User ID: user-123
Role: premium
Node: content.delete
Result: DENY

Inheritance Chain
  guest (w:0) → user (w:10) → premium (w:50)

Matching Patterns
  ✗ content.delete          [user]    (spec:20, weight:10) ← CHOSEN
  ✓ content.*               [premium] (spec:15, weight:50)
  ✓ *                       [admin]   (spec:0,  weight:100)

Resolution
  Tie-break by specificity: content.delete is more specific`}
            />

            {/* Programmatic Usage */}
            <Typography variant="h4" gutterBottom sx={{ mt: 4 }}>
                Programmatic Usage
            </Typography>

            <CodeBlock
                language="php"
                title="Using the Permissions Facade"
                showLineNumbers
                code={`use BaseApi\\Permissions\\Permissions;

// Check if user has permission
if (Permissions::check($userId, 'content.delete')) {
    // Allow
}

// Check role directly (without user)
if (Permissions::checkRole('admin', 'users.manage')) {
    // Allow
}

// Get all permissions for a role
$permissions = Permissions::getRolePermissions('premium');

// Trace for debugging
$trace = Permissions::trace($userId, 'content.delete');`}
            />

            <Typography variant="body1" paragraph sx={{ mt: 2 }}>
                Using the service directly:
            </Typography>

            <CodeBlock
                language="php"
                title="Using PermissionsService"
                showLineNumbers
                code={`use BaseApi\\Permissions\\PermissionsService;
use BaseApi\\App;

$permissions = App::container()->make(PermissionsService::class);

// Create group
$permissions->createGroup('moderator', weight: 25, inherits: ['user']);

// Grant permission
$permissions->grant('moderator', 'content.moderate', allow: true);

// Check permission
$allowed = $permissions->check($userId, 'content.moderate');

// Get group details
$group = $permissions->getGroup('moderator');

// Validate configuration
$errors = $permissions->validate();
if (!empty($errors)) {
    foreach ($errors as $error) {
        echo $error . "\\n";
    }
}`}
            />

            {/* Best Practices */}
            <Typography variant="h4" gutterBottom sx={{ mt: 4 }}>
                Best Practices
            </Typography>

            <Box component="ol" sx={{ pl: 3, mb: 2 }}>
                <li>
                    <Typography variant="body1" paragraph>
                        <strong>Use hierarchical naming:</strong> Structure permissions as <code>domain.resource.action</code>
                        for clarity and wildcard matching.
                    </Typography>
                </li>
                <li>
                    <Typography variant="body1" paragraph>
                        <strong>Start specific, expand carefully:</strong> Grant specific permissions first,
                        use wildcards only when necessary.
                    </Typography>
                </li>
                <li>
                    <Typography variant="body1" paragraph>
                        <strong>Use weight for precedence:</strong> Assign logical weights (guest:0, user:10, premium:50, admin:100)
                        to establish clear hierarchy.
                    </Typography>
                </li>
                <li>
                    <Typography variant="body1" paragraph>
                        <strong>Leverage inheritance:</strong> Create base groups (guest, user) and inherit to avoid duplication.
                    </Typography>
                </li>
                <li>
                    <Typography variant="body1" paragraph>
                        <strong>Test with trace:</strong> Use <code>perm:trace</code> to verify permission resolution
                        before deploying changes.
                    </Typography>
                </li>
                <li>
                    <Typography variant="body1" paragraph>
                        <strong>Validate regularly:</strong> Run <code>perm:validate</code> to catch configuration errors.
                    </Typography>
                </li>
                <li>
                    <Typography variant="body1" paragraph>
                        <strong>Explicit deny for security:</strong> Use explicit deny (<code>--deny</code>) to override
                        inherited allows for sensitive operations.
                    </Typography>
                </li>
            </Box>

            {/* Security Considerations */}
            <Typography variant="h4" gutterBottom sx={{ mt: 4 }}>
                Security Considerations
            </Typography>

            <Callout type="warning">
                <Typography variant="body1" paragraph>
                    <strong>Production deployment:</strong>
                </Typography>
                <Box component="ul" sx={{ pl: 3, mt: 1 }}>
                    <li><Typography variant="body2">Restrict file permissions on <code>storage/permissions/</code> (0600 or 0640)</Typography></li>
                    <li><Typography variant="body2">Never commit <code>permissions.json</code> to version control if it contains sensitive data</Typography></li>
                    <li><Typography variant="body2">Audit wildcard permissions regularly</Typography></li>
                    <li><Typography variant="body2">Use explicit deny rules for high-risk operations</Typography></li>
                    <li><Typography variant="body2">Limit CLI access to authorized administrators only</Typography></li>
                </Box>
            </Callout>

            {/* File Structure */}
            <Typography variant="h4" gutterBottom sx={{ mt: 4 }}>
                File Structure
            </Typography>

            <Typography variant="body1" paragraph>
                The permissions configuration is stored in JSON format:
            </Typography>

            <CodeBlock
                language="json"
                title="storage/permissions/permissions.json"
                code={`{
  "groups": {
    "guest": {
      "inherits": [],
      "weight": 0,
      "permissions": {
        "auth.login": true,
        "auth.signup": true,
        "content.read": true
      }
    },
    "user": {
      "inherits": ["guest"],
      "weight": 10,
      "permissions": {
        "content.create": true,
        "content.edit": true,
        "profile.edit": true
      }
    },
    "admin": {
      "inherits": ["user"],
      "weight": 100,
      "permissions": {
        "*": true
      }
    }
  },
  "meta": {
    "createdAt": "2025-01-15T10:30:00+00:00",
    "updatedAt": "2025-01-15T14:45:00+00:00"
  }
}`}
            />

            <Callout type="info" title="Concurrency Safe">
                All file operations use <code>flock()</code> for locking and <code>fsync()</code> for durability,
                making the system safe for concurrent CLI commands and runtime checks.
            </Callout>

            {/* Advanced Examples */}
            <Typography variant="h4" gutterBottom sx={{ mt: 4 }}>
                Advanced Examples
            </Typography>

            <Typography variant="h5" gutterBottom sx={{ mt: 3 }}>
                Complex Role Hierarchy
            </Typography>

            <CodeBlock
                language="bash"
                code={`# Create a complex role structure
./mason perm:group:create content-viewer --weight=5
./mason perm:group:create content-editor --weight=15
./mason perm:group:create content-moderator --weight=30
./mason perm:group:create content-admin --weight=60

# Set up inheritance
./mason perm:group:add-parent content-editor content-viewer
./mason perm:group:add-parent content-moderator content-editor
./mason perm:group:add-parent content-admin content-moderator

# Grant permissions
./mason perm:grant content-viewer 'content.read'
./mason perm:grant content-editor 'content.create'
./mason perm:grant content-editor 'content.edit'
./mason perm:grant content-moderator 'content.moderate'
./mason perm:grant content-moderator 'content.delete'
./mason perm:grant content-admin 'content.*' --force`}
            />

            <Typography variant="h5" gutterBottom sx={{ mt: 3 }}>
                Conditional Access with Multiple Permissions
            </Typography>

            <CodeBlock
                language="php"
                title="Advanced Route Protection"
                code={`// Require multiple permissions (AND logic)
$router->post('/admin/users/delete', [
    CombinedAuthMiddleware::class,
    PermissionsMiddleware::class => ['requiresAll' => [
        'admin.users.manage',
        'admin.users.delete',
        'admin.destructive.operations'
    ]],
    AdminUserController::class,
]);

// Allow access if user has any of these (OR logic)
$router->get('/reports', [
    CombinedAuthMiddleware::class,
    PermissionsMiddleware::class => ['requiresAny' => [
        'reports.view',
        'admin.reports',
        'management.dashboard'
    ]],
    ReportsController::class,
]);`}
            />

            {/* Troubleshooting */}
            <Typography variant="h4" gutterBottom sx={{ mt: 4 }}>
                Troubleshooting
            </Typography>

            <Typography variant="h5" gutterBottom sx={{ mt: 3 }}>
                Permission Denied Unexpectedly
            </Typography>

            <CodeBlock
                language="bash"
                code={`# Use trace to debug
./mason perm:trace user@example.com content.delete

# Check user's actual role
./mason perm:user:get-role user@example.com

# Verify group permissions
./mason perm:group:show user

# Validate configuration
./mason perm:validate`}
            />

            <Typography variant="h5" gutterBottom sx={{ mt: 3 }}>
                Wildcard Not Working
            </Typography>

            <Typography variant="body1" paragraph>
                Ensure specificity is correctly calculated. More specific permissions always win:
            </Typography>

            <CodeBlock
                language="bash"
                code={`# Example: content.delete (specific) overrides content.* (wildcard)
./mason perm:grant user 'content.*'
./mason perm:grant user 'content.delete' --deny

# Result: content.delete is DENIED, other content.* is ALLOWED`}
            />

            <Typography variant="h5" gutterBottom sx={{ mt: 3 }}>
                Cannot Grant Wildcard Permission
            </Typography>

            <Typography variant="body1" paragraph>
                Low-weight groups (&lt;50) require <code>--force</code>:
            </Typography>

            <CodeBlock
                language="bash"
                code={`# This fails (user weight is 10)
./mason perm:grant user 'admin.*'

# This succeeds
./mason perm:grant user 'admin.*' --force

# Or increase weight first
./mason perm:group:set-weight user 50
./mason perm:grant user 'admin.*'`}
            />

            {/* Performance */}
            <Typography variant="h4" gutterBottom sx={{ mt: 4 }}>
                Performance
            </Typography>

            <Typography variant="body1" paragraph>
                The permissions system is optimized for runtime performance:
            </Typography>

            <Box component="ul" sx={{ pl: 3, mb: 2 }}>
                <li><Typography variant="body1">Permissions are cached per-role in memory</Typography></li>
                <li><Typography variant="body1">File reads use shared locks (non-blocking for concurrent reads)</Typography></li>
                <li><Typography variant="body1">Inheritance chains are resolved once and cached</Typography></li>
                <li><Typography variant="body1">Pattern matching uses optimized regex compilation</Typography></li>
                <li><Typography variant="body1">Typical permission check: &lt;0.1ms</Typography></li>
            </Box>

            <Callout type="tip">
                For high-traffic applications, consider caching the entire permissions.json file
                in Redis and reloading only on changes.
            </Callout>

            {/* Next Steps */}
            <Typography variant="h4" gutterBottom sx={{ mt: 4 }}>
                Next Steps
            </Typography>

            <Box component="ul" sx={{ pl: 3, mb: 2 }}>
                <li><Typography variant="body1">Learn about <a href="/security/api-token-auth">API Token Authentication</a></Typography></li>
                <li><Typography variant="body1">Explore <a href="/fundamentals/routing">Routing and Middleware</a></Typography></li>
                <li><Typography variant="body1">Read about <a href="/security/overview">Security Best Practices</a></Typography></li>
            </Box>
        </Box>
    );
}


