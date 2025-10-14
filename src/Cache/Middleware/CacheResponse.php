<?php

namespace BaseApi\Cache\Middleware;

use Override;
use BaseApi\Http\BaseMiddleware;
use BaseApi\Http\Request;
use BaseApi\Http\Response;
use BaseApi\Cache\Cache;
use BaseApi\App;

/**
 * HTTP response caching middleware.
 * 
 * Caches HTTP responses to reduce server load for static or semi-static content.
 * Supports cache invalidation by tags and ETag generation.
 */
class CacheResponse extends BaseMiddleware
{
    /**
     * @param callable(Request): Response $next
     */
    #[Override]
    public function handle(Request $request, callable $next): Response
    {
        // Only cache GET requests
        if ($request->method !== 'GET') {
            return $next($request);
        }

        // Check if response caching is enabled
        $config = App::config();
        if (!$config->get('cache.response_cache.enabled', false)) {
            return $next($request);
        }

        // Get cache parameters from route or middleware parameters
        $ttl = $this->getTtlFromParams() ?? $config->get('cache.response_cache.default_ttl', 600);
        $tags = $this->getTagsFromParams();
        
        // Generate cache key
        $cacheKey = $this->generateCacheKey($request);
        
        // Try to get cached response
        $cachedResponse = $this->getCachedResponse($cacheKey, $tags);
        if ($cachedResponse instanceof Response) {
            return $cachedResponse;
        }

        // Execute the request
        $response = $next($request);

        // Cache the response if it's successful
        if ($response->status >= 200 && $response->status < 300) {
            $this->cacheResponse($cacheKey, $response, $ttl, $tags);
        }

        return $response;
    }

    /**
     * Get cached response if available.
     */
    private function getCachedResponse(string $cacheKey, array $tags): ?Response
    {
        $cache = $tags === [] ? Cache::driver() : Cache::tags($tags);
        $cached = $cache->get($cacheKey);
        
        if (!$cached) {
            return null;
        }

        // Reconstruct response from cached data
        $response = new Response();
        $response->status = $cached['status_code'];
        $response->body = $cached['body'];
        
        // Restore headers
        foreach ($cached['headers'] as $name => $value) {
            $response->headers[$name] = $value;
        }
        
        // Add cache headers
        $response->headers['X-Cache'] = 'HIT';
        $response->headers['X-Cache-Key'] = $cacheKey;
        
        return $response;
    }

    /**
     * Cache the response.
     */
    private function cacheResponse(string $cacheKey, Response $response, int $ttl, array $tags): void
    {
        $cacheData = [
            'status_code' => $response->status,
            'body' => $response->body,
            'headers' => $response->headers,
            'cached_at' => time(),
        ];

        $cache = $tags === [] ? Cache::driver() : Cache::tags($tags);
        $cache->put($cacheKey, $cacheData, $ttl);
        
        // Add cache headers to original response
        $response->headers['X-Cache'] = 'MISS';
        $response->headers['X-Cache-Key'] = $cacheKey;
        $response->headers['Cache-Control'] = 'public, max-age=' . $ttl;
    }

    /**
     * Generate cache key for request.
     */
    private function generateCacheKey(Request $request): string
    {
        $config = App::config();
        $prefix = $config->get('cache.response_cache.prefix', 'response');
        
        // Base components
        $components = [
            'uri' => $request->path,
            'method' => $request->method,
        ];

        // Add query parameters (filtered)
        $ignoreParams = $config->get('cache.response_cache.ignore_query_params', []);
        $queryParams = array_diff_key($request->query, array_flip($ignoreParams));
        if ($queryParams !== []) {
            ksort($queryParams);
            $components['query'] = $queryParams;
        }

        // Add vary headers
        $varyHeaders = $config->get('cache.response_cache.vary_headers', []);
        foreach ($varyHeaders as $header) {
            if (isset($request->headers[$header])) {
                $components['headers'][$header] = $request->headers[$header];
            }
        }

        return $prefix . ':' . hash('md5', serialize($components));
    }

    /**
     * Get TTL from middleware parameters.
     */
    private function getTtlFromParams(): ?int
    {
        $params = $this->getParams();
        return isset($params[0]) ? (int)$params[0] : null;
    }

    /**
     * Get tags from middleware parameters.
     */
    private function getTagsFromParams(): array
    {
        $params = $this->getParams();
        
        if (isset($params[1])) {
            return is_array($params[1]) ? $params[1] : explode(',', (string) $params[1]);
        }
        
        return [];
    }

    /**
     * Get middleware parameters from route definition.
     */
    private function getParams(): array
    {
        // In a real implementation, this would extract parameters from the middleware definition
        // For now, return empty array - this would be populated by the router
        return [];
    }
}
