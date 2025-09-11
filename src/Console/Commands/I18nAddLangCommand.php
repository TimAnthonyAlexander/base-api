<?php

namespace BaseApi\Console\Commands;

use BaseApi\Console\Command;
use BaseApi\App;
use BaseApi\Support\I18n;
use BaseApi\Support\Translation\TranslationProviderFactory;

class I18nAddLangCommand implements Command
{
    public function name(): string
    {
        return 'i18n:add-lang';
    }
    
    public function description(): string
    {
        return 'Add new language(s) to the translation system';
    }
    
    public function execute(array $args, ?\BaseApi\Console\Application $app = null): int
    {
        if (empty($args)) {
            echo "Usage: php bin/console i18n:add-lang <lang1> [lang2] [lang3] [options]\n";
            echo "Options:\n";
            echo "  --seed    Copy default locale values to aid translators\n";
            echo "  --auto    Auto-translate using configured provider\n";
            return 1;
        }
        
        $seed = in_array('--seed', $args);
        $auto = in_array('--auto', $args);
        
        // Filter out option flags to get language codes
        $languages = array_filter($args, fn($arg) => !str_starts_with($arg, '--'));
        
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
        echo "Adding language: {$language}\n";
        
        // Create language directory
        $languageDir = App::basePath("translations/{$language}");
        if (!is_dir($languageDir)) {
            mkdir($languageDir, 0755, true);
            echo "  ✅ Created directory: {$languageDir}\n";
        } else {
            echo "  ℹ️  Directory already exists: {$languageDir}\n";
        }
        
        // Get all namespaces from default locale
        $namespaces = I18n::getAvailableNamespaces($defaultLocale);
        
        if (empty($namespaces)) {
            echo "  ⚠️  No translation files found in default locale ({$defaultLocale})\n";
            return;
        }
        
        $i18n = I18n::getInstance();
        $provider = null;
        
        if ($auto) {
            try {
                $provider = TranslationProviderFactory::create();
                if ($provider && !$provider->supportsLanguagePair($defaultLocale, $language)) {
                    echo "  ⚠️  Provider doesn't support {$defaultLocale} -> {$language}\n";
                    $provider = null;
                }
            } catch (\Exception $e) {
                echo "  ⚠️  Translation provider error: " . $e->getMessage() . "\n";
                $provider = null;
            }
        }
        
        foreach ($namespaces as $namespace) {
            echo "  Processing namespace: {$namespace}\n";
            
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
                        echo "    ✨ Auto-translated: {$token}\n";
                    } catch (\Exception $e) {
                        if ($seed) {
                            $newTranslations[$token] = $value;
                        } else {
                            $newTranslations[$token] = '';
                        }
                        echo "    ⚠️  Failed to auto-translate {$token}: " . $e->getMessage() . "\n";
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
