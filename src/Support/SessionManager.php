<?php

namespace BaseApi\Support;

/**
 * Session management helper for secure session operations.
 * 
 * Provides session ID regeneration for authentication boundaries
 * (login, privilege escalation, etc.) to prevent session fixation.
 */
class SessionManager
{
    /**
     * Regenerate session ID and rotate CSRF token.
     * 
     * Call this on:
     * - Password login success
     * - SSO callback success
     * - Role/privilege escalation
     * - Logout (before destroying session)
     * 
     * This prevents session fixation attacks.
     */
    public function regenerate(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        // Regenerate session ID (delete old session file)
        if (!session_regenerate_id(true)) {
            return false;
        }

        // Rotate CSRF token on session ID regeneration
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Update last activity timestamp
        $_SESSION['_last_activity'] = time();

        return true;
    }

    /**
     * Destroy current session completely.
     * 
     * Call this on logout to ensure clean session termination.
     */
    public function destroy(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        // Clear session data
        $_SESSION = [];

        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                ['expires' => time() - 42000, 'path' => $params['path'], 'domain' => $params['domain'], 'secure' => $params['secure'], 'httponly' => $params['httponly']]
            );
        }

        // Destroy session file
        return session_destroy();
    }

    /**
     * Get current session ID.
     */
    public function id(): string
    {
        return session_id();
    }

    /**
     * Check if session is active.
     */
    public function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Set a session value.
     */
    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session has a key.
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session value.
     */
    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Get all session data.
     */
    public function all(): array
    {
        return $_SESSION;
    }

    /**
     * Flash data for next request only.
     */
    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Get flashed data and remove it.
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}

