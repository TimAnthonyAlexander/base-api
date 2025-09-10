<?php

namespace BaseApi\Http\Caching;

use BaseApi\Http\Request;
use BaseApi\Http\Response;

/**
 * Tiny helpers for ETag and Cache-Control headers.
 */
class CacheHelper
{
    /**
     * Generate a strong ETag from content.
     * Returns quoted hex hash as HTTP expects.
     */
    public static function strongEtag(string $content): string
    {
        return '"' . md5($content) . '"';
    }

    /**
     * Check If-None-Match header and return 304 if ETag matches.
     * 
     * @param Request $req The request object
     * @param Response $res The response object to modify
     * @param string $etag The ETag to compare (should include quotes)
     * @return Response|null Returns 304 response if matches, null if no match
     */
    public static function notModifiedIfMatches(Request $req, Response $res, string $etag): ?Response
    {
        $ifNoneMatch = $req->headers['If-None-Match'] ?? null;
        
        if ($ifNoneMatch !== null && $ifNoneMatch === $etag) {
            // Return 304 response with ETag header and empty body
            $notModified = $res->withStatus(304)->withHeader('ETag', $etag);
            $notModified->body = '';
            return $notModified;
        }

        // No match - set ETag for future requests
        return $res->withHeader('ETag', $etag);
    }

    /**
     * Set Cache-Control header with max-age and public/private directive.
     */
    public static function cacheControl(Response $res, int $maxAge, bool $public = true): Response
    {
        $directive = $public ? 'public' : 'private';
        return $res->withHeader('Cache-Control', "{$directive}, max-age={$maxAge}");
    }
}
