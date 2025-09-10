<?php

namespace BaseApi\Console\Commands;

use BaseApi\Console\Command;
use BaseApi\App;
use BaseApi\Support\I18n;
use BaseApi\Support\Translation\TranslationProviderFactory;
use BaseApi\Support\Translation\TranslationException;

class I18nFillCommand implements Command
{
    public function name(): string
    {
        return 'i18n:fill';
    }
    
    public function description(): string
    {
        return 'Fill missing translations using machine translation';
    }
    
    public function execute(array $args = []): int
    {
        // Load the complete i18n config
        $configPath = App::basePath('config/i18n.php');
        $config = file_exists($configPath) ? require $configPath : [];
        $defaultLocale = $config['default'] ?? 'en';
        
        // Parse arguments
        $targetLocales = [];
        $force = in_array('--force', $args);
        
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--to=')) {
                $locales = explode(',', substr($arg, 5));
                $targetLocales = array_merge($targetLocales, array_map('trim', $locales));
            }
        }
        
        // If no target locales specified, use all available except default
        if (empty($targetLocales)) {
            $targetLocales = array_filter($config['locales'], fn($locale) => $locale !== $defaultLocale);
        }
        
        if (empty($targetLocales)) {
            echo "No target locales specified or available.\n";
            return 1;
        }
        
        // Check if translation provider is available
        try {
            $provider = TranslationProviderFactory::create();
            if (!$provider) {
                echo "No translation provider configured. Please set I18N_PROVIDER in your .env file.\n";
                return 1;
            }
        } catch (TranslationException $e) {
            echo "Translation provider error: " . $e->getMessage() . "\n";
            return 1;
        }
        
        echo "Using translation provider to fill missing translations...\n";
        echo "Default locale: {$defaultLocale}\n";
        echo "Target locales: " . implode(', ', $targetLocales) . "\n\n";
        
        $totalFilled = 0;
        
        foreach ($targetLocales as $locale) {
            $filled = $this->fillLocale($locale, $defaultLocale, $provider, $force);
            $totalFilled += $filled;
        }
        
        echo "\n✅ Filled {$totalFilled} missing translations across all locales.\n";
        
        return 0;
    }
    
    private function fillLocale(string $locale, string $defaultLocale, $provider, bool $force): int
    {
        echo "Filling missing translations for: {$locale}\n";
        
        if (!$provider->supportsLanguagePair($defaultLocale, $locale)) {
            echo "  ⚠️  Provider doesn't support {$defaultLocale} -> {$locale}\n";
            return 0;
        }
        
        $i18n = I18n::getInstance();
        $namespaces = I18n::getAvailableNamespaces($defaultLocale);
        $filledCount = 0;
        
        foreach ($namespaces as $namespace) {
            echo "  Processing namespace: {$namespace}\n";
            
            // Load translations
            $defaultTranslations = $i18n->loadTranslations($defaultLocale, $namespace);
            $targetTranslations = $i18n->loadTranslations($locale, $namespace);
            
            // Find missing or empty translations
            $toTranslate = [];
            foreach ($defaultTranslations as $token => $value) {
                $shouldTranslate = false;
                
                if (!isset($targetTranslations[$token])) {
                    // Missing translation
                    $shouldTranslate = true;
                } elseif (empty(trim($targetTranslations[$token]))) {
                    // Empty translation
                    $shouldTranslate = true;
                } elseif ($force) {
                    // Force retranslation
                    $shouldTranslate = true;
                }
                
                if ($shouldTranslate && !empty(trim($value))) {
                    $toTranslate[$token] = $value;
                }
            }
            
            if (empty($toTranslate)) {
                echo "    ℹ️  No missing translations in {$namespace}\n";
                continue;
            }
            
            echo "    Found " . count($toTranslate) . " missing translations\n";
            
            // Translate in batches for better performance
            $batchSize = 10;
            $batches = array_chunk($toTranslate, $batchSize, true);
            $translated = [];
            
            foreach ($batches as $batch) {
                try {
                    $texts = array_values($batch);
                    $tokens = array_keys($batch);
                    
                    $results = $provider->translateBatch($texts, $defaultLocale, $locale);
                    
                    foreach ($tokens as $index => $token) {
                        $translated[$token] = $results[$index] ?? '';
                        echo "    ✨ Translated: {$token}\n";
                    }
                } catch (TranslationException $e) {
                    echo "    ⚠️  Batch translation failed: " . $e->getMessage() . "\n";
                    
                    // Fall back to individual translations
                    foreach ($batch as $token => $text) {
                        try {
                            $result = $provider->translate($text, $defaultLocale, $locale);
                            $translated[$token] = $result;
                            echo "    ✨ Translated: {$token}\n";
                        } catch (TranslationException $e2) {
                            echo "    ❌ Failed to translate {$token}: " . $e2->getMessage() . "\n";
                        }
                    }
                }
            }
            
            if (!empty($translated)) {
                // Merge with existing translations
                $updatedTranslations = array_merge($targetTranslations, $translated);
                
                // Update metadata
                $metadata = $updatedTranslations['__meta'] ?? [];
                $metadata['needs_review'] = true;
                $metadata['last_sync'] = date('c');
                $updatedTranslations['__meta'] = $metadata;
                
                // Save translations
                $i18n->saveTranslations($locale, $namespace, $updatedTranslations);
                
                $filledCount += count($translated);
                echo "    ✅ Filled " . count($translated) . " translations in {$namespace}.json\n";
            }
        }
        
        echo "  Total filled for {$locale}: {$filledCount}\n\n";
        return $filledCount;
    }
}
