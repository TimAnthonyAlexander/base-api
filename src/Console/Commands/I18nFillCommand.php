<?php

namespace BaseApi\Console\Commands;

use Override;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\App;
use BaseApi\Support\I18n;
use BaseApi\Support\Translation\TranslationProviderFactory;
use BaseApi\Support\Translation\TranslationException;
use BaseApi\Support\Translation\TranslationProvider;

class I18nFillCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'i18n:fill';
    }

    #[Override]
    public function description(): string
    {
        return 'Fill missing translations using machine translation';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        // Load the complete i18n config
        $configPath = App::basePath('config/i18n.php');
        $config = file_exists($configPath) ? require $configPath : [];
        $defaultLocale = $config['default'] ?? 'en';

        // Parse arguments
        $targetLocales = [];
        $force = in_array('--force', $args);

        foreach ($args as $arg) {
            if (str_starts_with((string) $arg, '--to=')) {
                $locales = explode(',', substr((string) $arg, 5));
                $targetLocales = array_merge($targetLocales, array_map('trim', $locales));
            }
        }

        // If no target locales specified, use all available except default
        if ($targetLocales === []) {
            $targetLocales = array_filter($config['locales'], fn($locale): bool => $locale !== $defaultLocale);
        }

        if ($targetLocales === []) {
            echo "No target locales specified or available.\n";
            return 1;
        }

        // Check if translation provider is available
        try {
            $provider = TranslationProviderFactory::create();
            if (!$provider instanceof TranslationProvider) {
                echo "No translation provider configured. Please set I18N_PROVIDER in your .env file.\n";
                return 1;
            }
        } catch (TranslationException $translationException) {
            echo "Translation provider error: " . $translationException->getMessage() . "\n";
            return 1;
        }

        echo "Using translation provider to fill missing translations...\n";
        echo sprintf('Default locale: %s%s', $defaultLocale, PHP_EOL);
        echo "Target locales: " . implode(', ', $targetLocales) . "\n\n";

        $totalFilled = 0;

        foreach ($targetLocales as $locale) {
            $filled = $this->fillLocale($locale, $defaultLocale, $provider, $force);
            $totalFilled += $filled;
        }

        echo "\n✅ Filled {$totalFilled} missing translations across all locales.\n";

        return 0;
    }

    private function fillLocale(string $locale, string $defaultLocale, TranslationProvider $provider, bool $force): int
    {
        echo sprintf('Filling missing translations for: %s%s', $locale, PHP_EOL);

        if (!$provider->supportsLanguagePair($defaultLocale, $locale)) {
            echo sprintf("  ⚠️  Provider doesn't support %s -> %s%s", $defaultLocale, $locale, PHP_EOL);
            return 0;
        }

        $i18n = I18n::getInstance();
        $namespaces = I18n::getAvailableNamespaces($defaultLocale);
        $filledCount = 0;

        foreach ($namespaces as $namespace) {
            echo sprintf('  Processing namespace: %s%s', $namespace, PHP_EOL);

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
                } elseif (in_array(trim((string) $targetTranslations[$token]), ['', '0'], true)) {
                    // Empty translation
                    $shouldTranslate = true;
                } elseif ($force) {
                    // Force retranslation
                    $shouldTranslate = true;
                }

                if ($shouldTranslate && !in_array(trim((string) $value), ['', '0'], true)) {
                    $toTranslate[$token] = $value;
                }
            }

            if ($toTranslate === []) {
                echo sprintf('    ℹ️  No missing translations in %s%s', $namespace, PHP_EOL);
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

                    // Protect placeholders before translation
                    $protectedTexts = [];
                    $placeholderMaps = [];
                    
                    foreach ($texts as $idx => $text) {
                        $protected = $this->protectPlaceholders($text);
                        $protectedTexts[$idx] = $protected['text'];
                        $placeholderMaps[$idx] = $protected['map'];
                    }

                    $results = $provider->translateBatch($protectedTexts, $defaultLocale, $locale);

                    foreach ($tokens as $index => $token) {
                        $translatedText = $results[$index] ?? '';
                        // Restore placeholders after translation
                        $restoredText = $this->restorePlaceholders($translatedText, $placeholderMaps[$index]);
                        $translated[$token] = $restoredText;
                        echo sprintf('    ✨ Translated: %s%s', $token, PHP_EOL);
                    }
                } catch (TranslationException $e) {
                    echo "    ⚠️  Batch translation failed: " . $e->getMessage() . "\n";

                    // Fall back to individual translations
                    foreach ($batch as $token => $text) {
                        try {
                            // Protect placeholders before translation
                            $protected = $this->protectPlaceholders($text);
                            $result = $provider->translate($protected['text'], $defaultLocale, $locale);
                            // Restore placeholders after translation
                            $result = $this->restorePlaceholders($result, $protected['map']);
                            $translated[$token] = $result;
                            echo sprintf('    ✨ Translated: %s%s', $token, PHP_EOL);
                        } catch (TranslationException $e2) {
                            echo sprintf('    ❌ Failed to translate %s: ', $token) . $e2->getMessage() . "\n";
                        }
                    }
                }
            }

            if ($translated !== []) {
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

    /**
     * Protect placeholders by replacing them with safe tokens before translation
     */
    private function protectPlaceholders(string $text): array
    {
        $placeholderMap = [];
        $counter = 0;

        // Find all placeholders (including nested ICU format)
        $pattern = '/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/';
        
        $protectedText = preg_replace_callback($pattern, function (array $matches) use (&$placeholderMap, &$counter): string {
            $placeholder = $matches[0];
            $token = 'PLACEHOLDER_TOKEN_' . $counter;
            $placeholderMap[$token] = $placeholder;
            $counter++;
            return $token;
        }, $text);

        return [
            'text' => $protectedText,
            'map' => $placeholderMap
        ];
    }

    /**
     * Restore original placeholders after translation
     */
    private function restorePlaceholders(string $text, array $placeholderMap): string
    {
        $restoredText = $text;
        
        foreach ($placeholderMap as $token => $placeholder) {
            $restoredText = str_replace($token, $placeholder, $restoredText);
        }
        
        return $restoredText;
    }
}
