<?php

namespace BaseApi\Support;

use BaseApi\App;
use BaseApi\Support\Translation\ICUValidator;
use RuntimeException;

class I18n
{
    private static ?I18n $instance = null;

    private array $config;

    private array $cache = [];

    private array $fileCache = [];

    private readonly string $translationsPath;

    public function __construct()
    {
        // Load the complete config file
        $configPath = App::basePath('config/i18n.php');
        $this->config = file_exists($configPath) ? require $configPath : [];
        $this->translationsPath = App::basePath('translations');
    }

    public static function getInstance(): self
    {
        if (!self::$instance instanceof \BaseApi\Support\I18n) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Translate a token with optional parameters and locale override
     */
    public static function t(string $token, array $params = [], ?string $locale = null): string
    {
        return self::getInstance()->translate($token, $params, $locale);
    }

    /**
     * Get a translation bundle for a locale and namespaces
     */
    public static function bundle(string $locale, array $namespaces = []): array
    {
        return self::getInstance()->getBundle($locale, $namespaces);
    }

    /**
     * Check if a translation exists
     */
    public static function has(string $token, ?string $locale = null): bool
    {
        return self::getInstance()->hasTranslation($token, $locale);
    }

    /**
     * Get available locales
     */
    public static function getAvailableLocales(): array
    {
        return self::getInstance()->config['locales'];
    }

    /**
     * Get default locale
     */
    public static function getDefaultLocale(): string
    {
        return self::getInstance()->config['default'];
    }

    /**
     * Get available namespaces for a locale
     */
    public static function getAvailableNamespaces(string $locale): array
    {
        return self::getInstance()->getNamespacesForLocale($locale);
    }

    /**
     * Internal translation method
     */
    public function translate(string $token, array $params = [], ?string $locale = null): string
    {
        $locale ??= $this->config['default'];

        // Get the namespace from the token (part before first dot)
        $namespace = $this->extractNamespace($token);

        // Load translations for this locale and namespace
        $translations = $this->loadTranslations($locale, $namespace);

        // Try to find the translation
        $translation = $translations[$token] ?? null;

        // If not found, try fallback
        if ($translation === null) {
            $translation = $this->getFallbackTranslation($token, $locale);
        }

        // If still not found, handle according to fallback behavior
        if ($translation === null) {
            $translation = $this->handleMissingTranslation($token, $locale);
        }

        // Format the translation with parameters
        return $this->formatMessage($translation, $params);
    }

    /**
     * Get translation bundle
     */
    public function getBundle(string $locale, array $namespaces = []): array
    {
        if ($namespaces === []) {
            $namespaces = $this->getNamespacesForLocale($locale);
        }

        $bundle = [
            'lang' => $locale,
            'namespaces' => $namespaces,
            'tokens' => [],
        ];

        foreach ($namespaces as $namespace) {
            $translations = $this->loadTranslations($locale, $namespace);
            $bundle['tokens'] = array_merge($bundle['tokens'], $translations);
        }

        return $bundle;
    }

    /**
     * Check if translation exists
     */
    public function hasTranslation(string $token, ?string $locale = null): bool
    {
        $locale ??= $this->config['default'];
        $namespace = $this->extractNamespace($token);
        $translations = $this->loadTranslations($locale, $namespace);

        return isset($translations[$token]);
    }

    /**
     * Get namespaces for a locale
     */
    public function getNamespacesForLocale(string $locale): array
    {
        $localeDir = $this->translationsPath . '/' . $locale;

        if (!is_dir($localeDir)) {
            return [];
        }

        $namespaces = [];
        $files = glob($localeDir . '/*.json');

        foreach ($files as $file) {
            $namespace = basename($file, '.json');
            $namespaces[] = $namespace;
        }

        return $namespaces;
    }

    /**
     * Load translations for a locale and namespace
     */
    public function loadTranslations(string $locale, string $namespace): array
    {
        $cacheKey = sprintf('%s.%s', $locale, $namespace);

        // Check memory cache first
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $filePath = $this->translationsPath . sprintf('/%s/%s.json', $locale, $namespace);

        // Check if file exists
        if (!file_exists($filePath)) {
            $this->cache[$cacheKey] = [];
            return [];
        }

        // Check file cache with mtime
        $mtime = filemtime($filePath);
        if (isset($this->fileCache[$cacheKey]) && $this->fileCache[$cacheKey]['mtime'] === $mtime) {
            $this->cache[$cacheKey] = $this->fileCache[$cacheKey]['data'];
            return $this->cache[$cacheKey];
        }

        // Load from file
        $content = file_get_contents($filePath);
        if ($content === false) {
            $this->cache[$cacheKey] = [];
            return [];
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            $this->cache[$cacheKey] = [];
            return [];
        }

        // Remove metadata
        if (isset($data['__meta'])) {
            unset($data['__meta']);
        }

        // Cache the data
        $this->cache[$cacheKey] = $data;
        $this->fileCache[$cacheKey] = [
            'data' => $data,
            'mtime' => $mtime,
        ];

        return $data;
    }

    /**
     * Extract namespace from token
     */
    private function extractNamespace(string $token): string
    {
        $parts = explode('.', $token, 2);
        return $parts[0] ?? 'common';
    }

    /**
     * Get fallback translation
     */
    private function getFallbackTranslation(string $token, string $locale): ?string
    {
        if ($locale === $this->config['default']) {
            return null; // Already tried default locale
        }

        $namespace = $this->extractNamespace($token);
        $defaultTranslations = $this->loadTranslations($this->config['default'], $namespace);

        return $defaultTranslations[$token] ?? null;
    }

    /**
     * Handle missing translation according to fallback behavior
     */
    private function handleMissingTranslation(string $token, string $locale): string
    {
        $behavior = $this->config['fallback_behavior'];

        switch ($behavior) {
            case 'key':
            default:
                return $token;
            case 'default_locale':
                if ($locale !== $this->config['default']) {
                    return $this->translate($token, [], $this->config['default']);
                }

                return $token;
        }
    }

    /**
     * Format message with parameters using ICU MessageFormat
     */
    private function formatMessage(string $message, array $params): string
    {
        if ($params === []) {
            return $message;
        }

        // Validate ICU format
        $validator = new ICUValidator();
        if (!$validator->validate($message)) {
            // Log error and fall back to simple replacement
            error_log(sprintf('ICU format validation failed for message: %s. Error: ', $message) . $validator->getLastError());
            return $this->simpleFormatMessage($message, $params);
        }

        // For now, implement basic ICU formatting
        // Full ICU MessageFormat support would require a more complex parser
        return $this->basicICUFormat($message, $params);
    }

    /**
     * Simple placeholder replacement fallback
     */
    private function simpleFormatMessage(string $message, array $params): string
    {
        foreach ($params as $key => $value) {
            $message = str_replace(sprintf('{%s}', $key), (string)$value, $message);
        }

        return $message;
    }

    /**
     * Basic ICU message formatting (simplified implementation)
     */
    private function basicICUFormat(string $message, array $params): string
    {
        // Handle simple variables like {name}
        foreach ($params as $key => $value) {
            $message = str_replace(sprintf('{%s}', $key), (string)$value, $message);
        }

        // Handle basic plural forms
        $message = preg_replace_callback(
            '/\{(\w+),\s*plural,\s*([^}]+)\}/',
            function (array $matches) use ($params): string {
                $variable = $matches[1];
                $options = $matches[2];
                $count = $params[$variable] ?? 0;

                return $this->formatPlural($count, $options);
            },
            $message
        );

        // Handle basic select forms
        $message = preg_replace_callback(
            '/\{(\w+),\s*select,\s*([^}]+)\}/',
            function (array $matches) use ($params): string {
                $variable = $matches[1];
                $options = $matches[2];
                $value = $params[$variable] ?? '';

                return $this->formatSelect($value, $options);
            },
            (string) $message
        );

        return $message;
    }

    /**
     * Format plural forms (basic implementation)
     */
    private function formatPlural(int|float $count, string $options): string
    {
        $count = (int)$count;

        // Parse options
        if (preg_match_all('/(\w+)\s*\{([^}]*)\}/', $options, $matches, PREG_SET_ORDER)) {
            $cases = [];
            foreach ($matches as $match) {
                $case = $match[1];
                $text = $match[2];
                // Replace # with actual count
                $text = str_replace('#', (string)$count, $text);
                $cases[$case] = $text;
            }

            // Simple plural rules for English
            if ($count === 1 && isset($cases['one'])) {
                return $cases['one'];
            }

            // Simple plural rules for English
            if (isset($cases['other'])) {
                return $cases['other'];
            }
        }

        return (string)$count;
    }

    /**
     * Format select forms (basic implementation)
     */
    private function formatSelect(mixed $value, string $options): string
    {
        $value = (string)$value;

        // Parse options
        if (preg_match_all('/(\w+)\s*\{([^}]*)\}/', $options, $matches, PREG_SET_ORDER)) {
            $cases = [];
            foreach ($matches as $match) {
                $case = $match[1];
                $text = $match[2];
                $cases[$case] = $text;
            }

            // Return matching case or 'other'
            return $cases[$value] ?? $cases['other'] ?? $value;
        }

        return $value;
    }

    /**
     * Generate ETag for a bundle
     */
    public function generateETag(string $locale, array $namespaces): string
    {
        $bundle = $this->getBundle($locale, $namespaces);
        // Sort the tokens for consistent hashing
        $tokens = $bundle['tokens'];
        ksort($tokens);
        $content = json_encode($tokens, JSON_UNESCAPED_UNICODE);
        return hash('sha256', $content);
    }

    /**
     * Save translations to file
     */
    public function saveTranslations(string $locale, string $namespace, array $translations): void
    {
        $localeDir = $this->translationsPath . '/' . $locale;

        // Create directory if it doesn't exist
        if (!is_dir($localeDir)) {
            mkdir($localeDir, 0755, true);
        }

        $filePath = $localeDir . '/' . $namespace . '.json';

        // Load existing data to preserve metadata
        $existingData = [];
        if (file_exists($filePath)) {
            $existingContent = file_get_contents($filePath);
            if ($existingContent !== false) {
                $existingData = json_decode($existingContent, true) ?? [];
            }
        }

        // Preserve metadata
        $data = $translations;
        if (isset($existingData['__meta'])) {
            $data['__meta'] = $existingData['__meta'];
        }

        // Sort keys if configured
        if ($this->config['sort_keys']) {
            ksort($data);
        }

        // Encode with pretty printing if configured
        $flags = JSON_UNESCAPED_UNICODE;
        if ($this->config['pretty_print']) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $content = json_encode($data, $flags);

        if (file_put_contents($filePath, $content) === false) {
            throw new RuntimeException('Failed to write translation file: ' . $filePath);
        }

        // Clear cache
        $cacheKey = sprintf('%s.%s', $locale, $namespace);
        unset($this->cache[$cacheKey]);
        unset($this->fileCache[$cacheKey]);
    }
}
