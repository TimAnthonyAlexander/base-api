<?php

namespace BaseApi\Controllers;

use BaseApi\Support\I18n;
use BaseApi\Http\JsonResponse;
use BaseApi\Http\Caching\CacheHelper;

class I18nController extends Controller
{
    public string $lang = '';
    public string $ns = '';
    public bool $flat = true;
    public string $v = '';
    
    /**
     * Get translation bundle
     */
    public function get(): JsonResponse
    {
        // Validate locale
        $locale = $this->lang ?: I18n::getDefaultLocale();
        if (!in_array($locale, I18n::getAvailableLocales())) {
            return JsonResponse::badRequest("Invalid locale: {$locale}");
        }
        
        // Parse namespaces
        $namespaces = [];
        if ($this->ns) {
            $namespaces = array_filter(array_map('trim', explode(',', $this->ns)));
        }
        
        // If no namespaces specified, get all available ones
        if (empty($namespaces)) {
            $namespaces = I18n::getAvailableNamespaces($locale);
        }
        
        // Validate namespaces exist
        $availableNamespaces = I18n::getAvailableNamespaces($locale);
        foreach ($namespaces as $namespace) {
            if (!in_array($namespace, $availableNamespaces)) {
                return JsonResponse::badRequest("Invalid namespace: {$namespace}");
            }
        }
        
        // Generate ETag
        $etag = I18n::getInstance()->generateETag($locale, $namespaces);
        
        // Check if client has cached version
        $ifNoneMatch = $this->request->headers['If-None-Match'] ?? null;
        if ($ifNoneMatch === '"' . $etag . '"') {
            return new JsonResponse(null, 304, [
                'ETag' => '"' . $etag . '"',
                'Cache-Control' => 'public, max-age=300',
            ]);
        }
        
        // Get the bundle
        $bundle = I18n::bundle($locale, $namespaces);
        
        // Format response based on flat parameter
        $responseData = $this->flat ? $bundle : $this->formatNested($bundle);
        
        // Create response with caching headers
        $response = JsonResponse::ok($responseData);
        $response->headers['ETag'] = '"' . $etag . '"';
        $response->headers['Cache-Control'] = 'public, max-age=300';
        
        return $response;
    }
    
    /**
     * Update translations (admin only)
     * TODO: Add authentication and CSRF protection
     */
    public function post(): JsonResponse
    {
        // Parse JSON body
        $data = json_decode($this->request->rawBody ?? '', true);
        if (!is_array($data)) {
            return JsonResponse::badRequest('Invalid JSON body');
        }
        
        // Validate required fields
        if (!isset($data['lang']) || !isset($data['namespace']) || !isset($data['updates'])) {
            return JsonResponse::badRequest('Missing required fields: lang, namespace, updates');
        }
        
        $locale = $data['lang'];
        $namespace = $data['namespace'];
        $updates = $data['updates'];
        
        // Validate locale
        if (!in_array($locale, I18n::getAvailableLocales())) {
            return JsonResponse::badRequest("Invalid locale: {$locale}");
        }
        
        // Validate updates is an array
        if (!is_array($updates)) {
            return JsonResponse::badRequest('Updates must be an array of key-value pairs');
        }
        
        // Load existing translations
        $existingTranslations = I18n::getInstance()->loadTranslations($locale, $namespace);
        
        // Merge updates
        $translations = array_merge($existingTranslations, $updates);
        
        // Save translations
        try {
            I18n::getInstance()->saveTranslations($locale, $namespace, $translations);
            
            return JsonResponse::ok([
                'message' => 'Translations updated successfully',
                'locale' => $locale,
                'namespace' => $namespace,
                'updated_count' => count($updates),
            ]);
        } catch (\Exception $e) {
            return JsonResponse::error('Failed to save translations: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Format bundle in nested structure by namespace
     */
    private function formatNested(array $bundle): array
    {
        $nested = [
            'lang' => $bundle['lang'],
            'namespaces' => $bundle['namespaces'],
            'tokens' => [],
        ];
        
        foreach ($bundle['tokens'] as $token => $translation) {
            $namespace = explode('.', $token, 2)[0] ?? 'common';
            
            if (!isset($nested['tokens'][$namespace])) {
                $nested['tokens'][$namespace] = [];
            }
            
            $nested['tokens'][$namespace][$token] = $translation;
        }
        
        return $nested;
    }
}
