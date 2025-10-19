<?php

namespace BaseApi\Http;

use RuntimeException;
use Override;
use BaseApi\App;

class SessionStartMiddleware implements Middleware
{
    #[Override]
    public function handle(Request $req, callable $next): Response
    {
        // Determine if we should start a session
        $shouldStartSession = $this->shouldStartSession($req);

        if ($shouldStartSession && session_status() === PHP_SESSION_NONE) {
            $this->configureSession();
            session_start();

            // Check idle timeout (only for session-auth flows, not API tokens)
            if (!$this->isApiTokenRequest($req)) {
                $this->checkIdleTimeout();
            }
        }

        $req->session = &$_SESSION;

        return $next($req);
    }

    /**
     * Determine if session should be started
     * - Never start for OPTIONS requests
     * - Never start if API token authentication is being used
     * - Start if session cookie exists (resume existing session)
     * - Start if route explicitly needs session
     */
    private function shouldStartSession(Request $req): bool
    {
        // Never start session for OPTIONS
        if ($req->method === 'OPTIONS') {
            return false;
        }

        // Never start session if API token is present (stateless)
        if ($this->isApiTokenRequest($req)) {
            return false;
        }

        // Start if session cookie already exists (resume session)
        $config = App::config();
        $sessionName = $config->get('SESSION_NAME', 'BASEAPISESSID');
        if (isset($_COOKIE[$sessionName])) {
            return true;
        }

        // Start if route explicitly needs session
        return $req->needsSession;
    }

    /**
     * Check if this is an API token request (Authorization: Bearer header)
     */
    private function isApiTokenRequest(Request $req): bool
    {
        $authHeader = $req->headers['Authorization'] ?? $req->headers['AUTHORIZATION'] ?? null;
        return $authHeader !== null && str_starts_with((string) $authHeader, 'Bearer ');
    }

    /**
     * Check and enforce idle timeout
     * Destroys session if last activity was too long ago
     */
    private function checkIdleTimeout(): void
    {
        $config = App::config();
        $idleTimeout = $config->int('SESSION_IDLE_TIMEOUT', 1800); // 30 minutes default

        if ($idleTimeout <= 0) {
            return; // Timeout disabled
        }

        $lastActivity = $_SESSION['_last_activity'] ?? null;
        $now = time();

        if ($lastActivity !== null && ($now - $lastActivity) > $idleTimeout) {
            // Session expired - destroy and force re-auth
            session_destroy();
            session_start(); // Start fresh session
            $_SESSION = []; // Clear all data
            return;
        }

        // Update last activity timestamp
        $_SESSION['_last_activity'] = $now;
    }

    private function configureSession(): void
    {
        $config = App::config();

        // Session name and cookie settings
        ini_set('session.name', $config->get('SESSION_NAME', 'BASEAPISESSID'));
        ini_set('session.cookie_httponly', '1');

        // Validate SameSite=None requires Secure
        $sameSite = $config->get('SESSION_SAMESITE', 'Lax');
        $secure = $config->bool('SESSION_SECURE', false);

        if ($sameSite === 'None' && !$secure) {
            throw new RuntimeException('SESSION_SAMESITE=None requires SESSION_SECURE=true. This combination is invalid and refused for security.');
        }

        ini_set('session.cookie_secure', $secure ? '1' : '0');
        ini_set('session.cookie_samesite', $sameSite);

        // Security settings
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        // Lifetime
        $lifetime = $config->int('SESSION_LIFETIME', 0); // 0 = session cookie
        ini_set('session.cookie_lifetime', (string) $lifetime);

        // Cookie domain (optional, for cross-subdomain)
        $domain = $config->get('SESSION_COOKIE_DOMAIN', null);
        if ($domain !== null) {
            ini_set('session.cookie_domain', $domain);
        }
    }
}
