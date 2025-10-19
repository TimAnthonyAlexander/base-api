<?php

namespace BaseApi\Http;

use Override;
use BaseApi\App;

/**
 * Middleware that starts session in read-only mode.
 * 
 * This middleware opens the session with read_and_close option, which:
 * - Reads session data into $_SESSION
 * - Immediately closes and releases the session lock
 * - Prevents writing to the session
 * 
 * Use this for endpoints that only need to READ session data (e.g., for authentication).
 * Use SessionStartMiddleware for endpoints that need to WRITE to the session.
 * 
 * Benefits:
 * - Prevents session locking bottlenecks
 * - Improves concurrency for read-heavy workloads
 * - Reduces latency for parallel requests from same client
 */
class SessionStartAndReadMiddleware implements Middleware
{
    #[Override]
    public function handle(Request $req, callable $next): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            $this->configureSession();
            session_start(['read_and_close' => true]);
        }

        // Attach session to request (read-only, cannot be modified)
        $req->session = $_SESSION;

        return $next($req);
    }

    private function configureSession(): void
    {
        $config = App::config();

        ini_set('session.name', $config->get('SESSION_NAME', 'BASEAPISESSID'));
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $config->bool('SESSION_SECURE') ? '1' : '0');
        ini_set('session.cookie_samesite', $config->get('SESSION_SAMESITE', 'Lax'));
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
    }
}

