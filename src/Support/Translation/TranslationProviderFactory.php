<?php

namespace BaseApi\Support\Translation;

use BaseApi\App;

class TranslationProviderFactory
{
    private static ?TranslationProvider $instance = null;
    
    /**
     * Create translation provider instance
     */
    public static function create(?string $provider = null): ?TranslationProvider
    {
        if ($provider === null) {
            // Load the complete i18n config
            $configPath = App::basePath('config/i18n.php');
            $i18nConfig = file_exists($configPath) ? require $configPath : [];
            $provider = $i18nConfig['provider'] ?? null;
        }
        
        if (empty($provider)) {
            return null;
        }
        
        if (self::$instance instanceof TranslationProvider) {
            return self::$instance;
        }
        
        self::$instance = match (strtolower((string) $provider)) {
            'deepl' => new DeepLProvider(),
            'openai' => new OpenAIProvider(),
            default => self::createFromClassName($provider),
        };
        
        return self::$instance;
    }

    /**
     * Instantiate a custom provider given as a fully-qualified class name.
     * Falls through to the "unknown provider" exception for anything that
     * isn't an existing class implementing TranslationProvider.
     */
    private static function createFromClassName(string $provider): TranslationProvider
    {
        if (class_exists($provider) && is_a($provider, TranslationProvider::class, true)) {
            return new $provider();
        }

        throw new TranslationException('Unknown translation provider: ' . $provider);
    }

    /**
     * Reset the singleton instance (useful for testing)
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
    
    /**
     * Check if a provider is configured and available
     */
    public static function isAvailable(): bool
    {
        // Load the complete i18n config
        $configPath = App::basePath('config/i18n.php');
        $i18nConfig = file_exists($configPath) ? require $configPath : [];
        $provider = $i18nConfig['provider'] ?? null;
        return !empty($provider);
    }
}
