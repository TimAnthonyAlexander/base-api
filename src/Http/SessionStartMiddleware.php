<?php

namespace BaseApi\Http;

use Override;
use BaseApi\App;

class SessionStartMiddleware implements Middleware
{
    #[Override]
    public function handle(Request $req, callable $next): Response
    {
        // Start a writable session
        // Note: If SessionStartAndReadMiddleware ran first, the session will be
        // in NONE status again after read_and_close, so this will start a new writable session
        if (session_status() === PHP_SESSION_NONE) {
            $this->configureSession();
            session_start();
        }

        // Attach session to request (will be updated by reference in controller if needed)
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
