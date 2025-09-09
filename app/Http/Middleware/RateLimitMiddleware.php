<?php

namespace BaseApi\Http\Middleware;

use BaseApi\Http\Request;
use BaseApi\Http\Response;
use BaseApi\Http\JsonResponse;
use BaseApi\Http\Middleware;
use BaseApi\Support\RateLimiter;
use BaseApi\Support\ClientIp;

class RateLimitMiddleware implements Middleware, OptionedMiddleware
{
    private array $options = [];
    private RateLimiter $rateLimiter;

    public function __construct()
    {
        $dir = $_ENV['RATE_LIMIT_DIR'] ?? __DIR__ . '/../../../storage/ratelimits';
        $this->rateLimiter = new RateLimiter($dir);
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function handle(Request $request, callable $next): Response
    {
        // error_log("RateLimitMiddleware called with options: " . json_encode($this->options));
        
        if (empty($this->options['limit'])) {
            // error_log("No limit set, passing through");
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
            
            foreach ($headers as $name => $value) {
                $response->headers[$name] = $value;
            }
            
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
        
        $seconds = $time * ($multipliers[$unit] ?? 1);
        
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
        $method = $request->method;
        $path = $request->path;
        
        return $this->rateLimiter->hashRoute($method, $path);
    }
}
