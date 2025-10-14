<?php

namespace BaseApi\Http\Middleware;

use Override;
use BaseApi\Http\Request;
use BaseApi\Http\Response;
use BaseApi\Http\JsonResponse;
use BaseApi\Http\Middleware;
use BaseApi\Support\RateLimiter;
use BaseApi\Support\ClientIp;
use BaseApi\App;

class RateLimitMiddleware implements Middleware, OptionedMiddleware
{
    private array $options = [];

    private readonly RateLimiter $rateLimiter;

    public function __construct()
    {
        $dir = $_ENV['RATE_LIMIT_DIR'] ?? 'storage/ratelimits';

        // Convert to absolute path if relative
        if (!str_starts_with((string) $dir, '/')) {
            $dir = App::basePath($dir);
        }

        $this->rateLimiter = new RateLimiter($dir);
    }

    #[Override]
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    #[Override]
    public function handle(Request $request, callable $next): Response
    {
        if (empty($this->options['limit'])) {
            return $next($request);
        }

        $limit = $this->parseLimit($this->options['limit']);
        if (!$limit) {
            return $next($request);
        }

        [$maxRequests, $windowSeconds] = $limit;
        $windowStart = $this->getWindowStart($windowSeconds);

        // Generate rate limit key
        $key = $this->getRateLimitKey($request);

        // Generate route ID
        $routeId = $this->getRouteId($request);

        // Check and increment counter
        $result = $this->rateLimiter->hit($routeId, $key, $windowStart, $maxRequests);

        $headers = [
            'X-RateLimit-Limit' => (string) $maxRequests,
            'X-RateLimit-Remaining' => (string) $result['remaining'],
            'X-RateLimit-Reset' => (string) ($windowStart + $windowSeconds)
        ];

        // If over limit, return 429
        if ($result['count'] > $maxRequests) {
            $retryAfter = ($windowStart + $windowSeconds) - time();
            $headers['Retry-After'] = (string) max($retryAfter, 0);

            $response = new JsonResponse([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.'
            ], 429);

            // Add rate limit headers
            foreach ($headers as $name => $value) {
                $response->headers[$name] = $value;
            }

            // Preserve essential headers from request (set by earlier middleware)
            if ($request->requestId !== '' && $request->requestId !== '0') {
                $response->headers['X-Request-Id'] = $request->requestId;
            }

            // Add CORS headers for 429 responses
            $this->addCorsHeaders($response, $request);

            return $response;
        }

        // Continue with request and add headers to response
        $response = $next($request);

        foreach ($headers as $name => $value) {
            $response->headers[$name] = $value;
        }

        return $response;
    }

    private function parseLimit(string $limit): ?array
    {
        if (!preg_match('/^(\d+)\/(\d+)([smhd])$/', $limit, $matches)) {
            return null;
        }

        $requests = (int) $matches[1];
        $time = (int) $matches[2];
        $unit = $matches[3];

        $multipliers = [
            's' => 1,
            'm' => 60,
            'h' => 3600,
            'd' => 86400
        ];

        $seconds = $time * $multipliers[$unit];

        return [$requests, $seconds];
    }

    private function getWindowStart(int $windowSeconds): int
    {
        return (int) (floor(time() / $windowSeconds) * $windowSeconds);
    }

    private function getRateLimitKey(Request $request): string
    {
        // Use session user_id if available, otherwise client IP
        if (!empty($_SESSION['user_id'])) {
            return 'user:' . $_SESSION['user_id'];
        }

        $trustProxy = ($_ENV['APP_TRUST_PROXY'] ?? 'false') === 'true';
        $ip = ClientIp::from($request, $trustProxy);

        return 'ip:' . $ip;
    }

    private function getRouteId(Request $request): string
    {
        // Use route pattern if available (for stable hashing), otherwise fall back to concrete path
        $method = $request->routeMethod ?? $request->method;
        $path = $request->routePattern ?? $request->path;

        return $this->rateLimiter->hashRoute($method, $path);
    }

    private function addCorsHeaders(Response $response, Request $request): void
    {
        $config = App::config();
        $allowlist = $config->list('CORS_ALLOWLIST');

        $origin = $request->headers['ORIGIN'] ?? $request->headers['Origin'] ?? null;
        $isAllowed = $origin && in_array($origin, $allowlist);

        if ($isAllowed) {
            $response->headers['Access-Control-Allow-Origin'] = $origin;
            $response->headers['Access-Control-Allow-Credentials'] = 'true';
            $response->headers['Access-Control-Expose-Headers'] = 'X-Request-Id, ETag, X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset, Retry-After';
            $response->headers['Vary'] = 'Origin';
        } elseif ($origin) {
            // Always add Vary: Origin when Origin header is present to avoid cache poisoning
            $response->headers['Vary'] = 'Origin';
        }
    }
}
