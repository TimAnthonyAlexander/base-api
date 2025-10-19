# Session Security Hardening

## Overview

BaseAPI has been hardened with production-grade session handling, following industry best practices for security, performance, and concurrent request handling.

## Critical Fixes

### 1. Session Reference Bug (FIXED)

**Before:**
```php
$req->session = $_SESSION; // Copy - writes don't persist!
```

**After:**
```php
$req->session = &$_SESSION; // Reference - writes persist correctly
```

This critical fix ensures CSRF tokens and other session writes actually persist to the session.

### 2. Conditional Session Starting

Sessions now only start when needed:
- ✅ When session cookie already exists (resume session)
- ✅ When route explicitly requires session
- ❌ **NEVER** for OPTIONS requests
- ❌ **NEVER** for API token authentication (stateless)
- ❌ **NEVER** for anonymous public endpoints

**Route-level control:**
```php
// Routes requiring session (login, CSRF-protected)
$router->post('/login', [LoginController::class], needsSession: true);
$router->post('/profile', [AuthMiddleware::class, ProfileController::class], needsSession: true);

// Routes NOT needing session (API tokens, public endpoints)
$router->get('/api/users', [ApiTokenAuthMiddleware::class, UserController::class]); // needsSession defaults to false
```

### 3. Session Lock Release

Sessions are now **automatically closed** after controller execution using `finally` blocks. This prevents head-of-line blocking under concurrent requests.

**Benefits:**
- Multiple requests from same user can process in parallel
- No more request queueing behind long-running operations
- Better performance under load

### 4. Session Security Enhancements

#### Idle Timeout
Sessions automatically expire after inactivity:
```env
SESSION_IDLE_TIMEOUT=1800  # 30 minutes default
```

When a session expires, it's destroyed and the user must re-authenticate (no silent session creation).

#### SameSite=None Guard
The framework **refuses to boot** with invalid cookie configurations:
```env
SESSION_SAMESITE=None
SESSION_SECURE=false  # ❌ REJECTED - None requires Secure
```

#### High Entropy Session IDs
```php
ini_set('session.sid_length', '48'); // 48 chars instead of default 32
```

#### Strict Security Settings
```php
ini_set('session.use_strict_mode', '1');    // Reject unknown session IDs
ini_set('session.use_only_cookies', '1');   // No URL session IDs
ini_set('session.cookie_httponly', '1');    // Prevent XSS access
```

## Configuration

### Environment Variables

```env
# Session cookie settings
SESSION_NAME=BASEAPISESSID
SESSION_LIFETIME=0                # 0 = session cookie, >0 = persistent
SESSION_SECURE=true               # HTTPS only (required in production)
SESSION_SAMESITE=Lax             # Lax|Strict|None

# Security settings
SESSION_IDLE_TIMEOUT=1800         # 30 minutes default, 0 to disable
SESSION_COOKIE_DOMAIN=            # Optional: .example.com for cross-subdomain

# Rate limiting
RATE_LIMIT_TRUST_PROXY=false     # true if behind proxy
```

## Session Manager

Use `SessionManager` for secure session operations:

```php
use BaseApi\Support\SessionManager;

class LoginController extends Controller
{
    public function post(): JsonResponse
    {
        // ... validate credentials ...

        $sessionManager = new SessionManager();
        
        // CRITICAL: Regenerate session ID on login to prevent fixation
        $sessionManager->regenerate();
        
        // Set user ID in session
        $sessionManager->put('user_id', $user['id']);
        
        return JsonResponse::ok(['message' => 'Logged in successfully']);
    }
}
```

### When to Regenerate Session ID

**Always regenerate on authentication boundaries:**
- ✅ Password login success
- ✅ SSO callback success (SAML, OIDC)
- ✅ Privilege escalation (e.g., user becomes admin)
- ✅ Before logout

**Example:**
```php
// Login
$sessionManager->regenerate();
$_SESSION['user_id'] = $userId;

// Logout
$sessionManager->regenerate(); // Rotate before destroying
$sessionManager->destroy();
```

## CSRF Protection

CSRF is now intelligently scoped:

### When CSRF is Enforced
- ✅ POST/PUT/PATCH/DELETE with session authentication
- ✅ State-changing operations via cookies

### When CSRF is Skipped
- ✅ GET/HEAD/OPTIONS (safe methods)
- ✅ API token authentication (`Authorization: Bearer`)
- ✅ Webhooks (use signature verification instead)
- ✅ SSO callbacks (use nonce/state verification instead)

**The middleware automatically detects auth method:**
```php
// Session auth → CSRF required
$router->post('/profile', [AuthMiddleware::class, ProfileController::class]);

// API token auth → CSRF skipped automatically
$router->post('/api/users', [ApiTokenAuthMiddleware::class, UserController::class]);

// Combined auth → CSRF required only for session, skipped for token
$router->post('/items', [CombinedAuthMiddleware::class, ItemController::class]);
```

## Rate Limiting

Rate limiting now uses proper identity precedence:

**Priority:**
1. **API token ID** (most specific)
2. **Session user ID** (user-specific)
3. **Client IP** (fallback for anonymous)

This ensures:
- Users can't bypass rate limits by switching between session and API token
- API tokens are rate-limited separately from session access
- Shared IPs don't create hot spots

## API Token Authentication

Applications define their own `ApiToken` model and middleware. BaseAPI provides:
- Session reference fix (tokens work correctly)
- CSRF skip for token auth
- Rate limiting by token ID
- Conditional session starting (no sessions for token requests)

**Example application middleware** (user creates this):
```php
namespace App\Middleware;

class ApiTokenAuthMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $token = $this->extractToken($request);
        $apiToken = ApiToken::findByToken($token);
        
        if ($apiToken === null || $apiToken->isExpired()) {
            return JsonResponse::error('Unauthorized', 401);
        }
        
        $request->user = $userProvider->byId($apiToken->user_id);
        $request->authMethod = 'api_token'; // Enables CSRF skip, rate limit by token
        $request->apiToken = $apiToken;
        
        $apiToken->updateLastUsed();
        
        return $next($request);
    }
}
```

## Testing Checklist

### Critical Tests

- [ ] **Session writes persist**: CSRF token generated in one request is valid in the next
- [ ] **No session for OPTIONS**: `OPTIONS /api/users` does not create session cookie
- [ ] **No session for API tokens**: `GET /api/users` with `Authorization: Bearer` does not create session
- [ ] **Session resumes correctly**: Existing session cookie resumes session
- [ ] **Idle timeout works**: Session expires after `SESSION_IDLE_TIMEOUT` seconds
- [ ] **SameSite=None guard**: App refuses to boot with `SESSION_SAMESITE=None` and `SESSION_SECURE=false`
- [ ] **Concurrent requests**: Two simultaneous requests from same user don't serialize
- [ ] **CSRF skipped for tokens**: API token requests bypass CSRF validation
- [ ] **Rate limit by token**: API token requests use token ID for rate limiting

### Performance Tests

- [ ] **Parallel requests**: Multiple authenticated requests process concurrently (no head-of-line blocking)
- [ ] **No unnecessary sessions**: Public endpoints don't create session files

## Migration Notes

### For Existing Applications

1. **Automatic compatibility**: Existing routes continue working (needsSession defaults to false)
2. **Session writes now work**: CSRF and other session mutations now persist correctly
3. **Mark session routes**: Add `needsSession: true` to routes that require sessions:
   ```php
   // Before
   $router->post('/login', [LoginController::class]);
   
   // After
   $router->post('/login', [LoginController::class], needsSession: true);
   ```

4. **Add session regeneration**: Update login/logout to regenerate session IDs:
   ```php
   $sessionManager = new SessionManager();
   $sessionManager->regenerate(); // On login
   ```

5. **Configure timeouts**: Add to `.env`:
   ```env
   SESSION_IDLE_TIMEOUT=1800
   SESSION_SECURE=true  # In production
   ```

### No Breaking Changes

- Routes without `needsSession` continue working
- Session authentication (`AuthMiddleware`) unchanged
- CSRF middleware automatically adapts to auth method
- Rate limiting automatically prefers token ID

## Security Best Practices

1. **Always regenerate on login**: Prevents session fixation
2. **Set SESSION_SECURE=true in production**: HTTPS-only cookies
3. **Use SameSite=Lax or Strict**: Prevents CSRF (Lax is usually correct)
4. **Enable idle timeout**: Force re-auth after inactivity
5. **Use API tokens for machine clients**: Stateless, no session overhead
6. **Never mix session and API token per user**: Pick one auth method per client type
7. **Rotate tokens regularly**: Set expiration dates on API tokens
8. **Monitor rate limits**: Adjust per-route limits based on actual usage

## Troubleshooting

### "CSRF token mismatch" after upgrade
**Cause:** Existing sessions have old CSRF tokens  
**Fix:** Users need to log out and log back in once (or clear cookies)

### "Session expired" too quickly
**Cause:** `SESSION_IDLE_TIMEOUT` too short  
**Fix:** Increase timeout in `.env`: `SESSION_IDLE_TIMEOUT=3600` (1 hour)

### Rate limits hit unexpectedly
**Cause:** Multiple users behind same IP  
**Fix:** Use `CombinedAuthMiddleware` so authenticated users aren't IP-limited

### Sessions not starting
**Cause:** Route doesn't have `needsSession: true` and no session cookie exists  
**Fix:** Add `needsSession: true` to routes that need sessions (login, CSRF-protected)

## Performance Impact

**Positive:**
- ✅ No sessions for API token requests (stateless)
- ✅ No sessions for OPTIONS requests
- ✅ Concurrent requests no longer serialize (session lock released)
- ✅ Fewer session files created for anonymous traffic

**Neutral:**
- Session-based routes behave the same (but now with better security)

**Recommendations:**
- Use API tokens for machine-to-machine communication
- Use sessions for browser-based user authentication
- Enable Redis session handler for multi-server deployments

