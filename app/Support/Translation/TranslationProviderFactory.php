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
        
        if (self::$instance !== null) {
            return self::$instance;
        }
        
        switch (strtolower($provider)) {
            case 'deepl':
                self::$instance = new DeepLProvider();
                break;
                
            case 'openai':
                self::$instance = new OpenAIProvider();
                break;
                
            default:
                throw new TranslationException("Unknown translation provider: {$provider}");
        }
        
        return self::$instance;
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
