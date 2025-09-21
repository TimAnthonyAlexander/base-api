<?php

namespace BaseApi\Console\Commands;

use Override;
use BaseApi\Console\Application;
use Exception;
use BaseApi\Console\Command;
use BaseApi\App;
use BaseApi\Support\I18n;
use BaseApi\Support\Translation\TranslationProviderFactory;

class I18nAddLangCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'i18n:add-lang';
    }
    
    #[Override]
    public function description(): string
    {
        return 'Add new language(s) to the translation system';
    }
    
    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        if ($args === []) {
            echo "Usage: php bin/console i18n:add-lang <lang1> [lang2] [lang3] [options]\n";
            echo "Options:\n";
            echo "  --seed    Copy default locale values to aid translators\n";
            echo "  --auto    Auto-translate using configured provider\n";
            return 1;
        }
        
        $seed = in_array('--seed', $args);
        $auto = in_array('--auto', $args);
        
        // Filter out option flags to get language codes
        $languages = array_filter($args, fn($arg): bool => !str_starts_with((string) $arg, '--'));
        
        // Load the complete i18n config
        $configPath = App::basePath('config/i18n.php');
        $config = file_exists($configPath) ? require $configPath : [];
        $defaultLocale = $config['default'] ?? 'en';
        
        foreach ($languages as $language) {
            $this->addLanguage($language, $defaultLocale, $seed, $auto);
        }
        
        return 0;
    }
    
    private function addLanguage(string $language, string $defaultLocale, bool $seed, bool $auto): void
    {
        echo sprintf('Adding language: %s%s', $language, PHP_EOL);
        
        // Create language directory
        $languageDir = App::basePath('translations/' . $language);
        if (!is_dir($languageDir)) {
            mkdir($languageDir, 0755, true);
            echo sprintf('  ✅ Created directory: %s%s', $languageDir, PHP_EOL);
        } else {
            echo sprintf('  ℹ️  Directory already exists: %s%s', $languageDir, PHP_EOL);
        }
        
        // Get all namespaces from default locale
        $namespaces = I18n::getAvailableNamespaces($defaultLocale);
        
        if ($namespaces === []) {
            echo "  ⚠️  No translation files found in default locale ({$defaultLocale})\n";
            return;
        }
        
        $i18n = I18n::getInstance();
        $provider = null;
        
        if ($auto) {
            try {
                $provider = TranslationProviderFactory::create();
                if ($provider && !$provider->supportsLanguagePair($defaultLocale, $language)) {
                    echo sprintf("  ⚠️  Provider doesn't support %s -> %s%s", $defaultLocale, $language, PHP_EOL);
                    $provider = null;
                }
            } catch (Exception $e) {
                echo "  ⚠️  Translation provider error: " . $e->getMessage() . "\n";
                $provider = null;
            }
        }
        
        foreach ($namespaces as $namespace) {
            echo sprintf('  Processing namespace: %s%s', $namespace, PHP_EOL);
            
            // Load default translations
            $defaultTranslations = $i18n->loadTranslations($defaultLocale, $namespace);
            
            // Load existing translations for this language (if any)
            $existingTranslations = $i18n->loadTranslations($language, $namespace);
            
            $newTranslations = [];
            
            foreach ($defaultTranslations as $token => $value) {
                if (isset($existingTranslations[$token])) {
                    // Keep existing translation
                    $newTranslations[$token] = $existingTranslations[$token];
                } elseif ($auto && $provider) {
                    // Auto-translate
                    try {
                        $translated = $provider->translate($value, $defaultLocale, $language);
                        $newTranslations[$token] = $translated;
                        echo sprintf('    ✨ Auto-translated: %s%s', $token, PHP_EOL);
                    } catch (Exception $e) {
                        $newTranslations[$token] = $seed ? $value : '';

                        echo sprintf('    ⚠️  Failed to auto-translate %s: ', $token) . $e->getMessage() . "\n";
                    }
                } elseif ($seed) {
                    // Copy default value
                    $newTranslations[$token] = $value;
                } else {
                    // Empty value for manual translation
                    $newTranslations[$token] = '';
                }
            }
            
            // Add metadata if auto-translated
            if ($auto && $provider) {
                $metadata = $existingTranslations['__meta'] ?? [];
                $metadata['needs_review'] = true;
                $metadata['last_sync'] = date('c');
                $newTranslations = ['__meta' => $metadata] + $newTranslations;
            }
            
            // Save translations
            $i18n->saveTranslations($language, $namespace, $newTranslations);
            
            $newCount = count($defaultTranslations) - count($existingTranslations);
            if ($newCount > 0) {
                echo "    ✅ Added {$newCount} translations to {$namespace}.json\n";
            } else {
                echo "    ℹ️  No new translations needed for {$namespace}.json\n";
            }
        }
        
        echo "  ✅ Language {$language} setup complete\n\n";
    }
}
