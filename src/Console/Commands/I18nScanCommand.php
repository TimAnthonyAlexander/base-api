<?php

namespace BaseApi\Console\Commands;

use Override;
use BaseApi\Console\Application;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\App;
use BaseApi\Support\I18n;

class I18nScanCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'i18n:scan';
    }
    
    #[Override]
    public function description(): string
    {
        return 'Scan codebase for translation tokens';
    }
    
    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        echo ColorHelper::header("🔍 Scanning for translation tokens...") . "\n";
        
        $write = in_array('--write', $args);
        $showOrphans = in_array('--show-orphans', $args);
        
        // Load the complete i18n config
        $configPath = App::basePath('config/i18n.php');
        $config = file_exists($configPath) ? require $configPath : [];
        $scanPaths = $config['scan_paths'] ?? ['app/'];
        $patterns = $config['token_patterns'] ?? [];
        $defaultLocale = $config['default'] ?? 'en';
        
        // Find all tokens in code
        $foundTokens = $this->scanForTokens($scanPaths, $patterns);
        
        // Get existing tokens from translation files
        $existingTokens = $this->getExistingTokens($defaultLocale);
        
        // Analyze differences
        $newTokens = array_diff($foundTokens, $existingTokens);
        $orphanTokens = array_diff($existingTokens, $foundTokens);
        $missingInDefault = [];
        
        // Check if found tokens exist in default locale
        foreach ($foundTokens as $token) {
            if (!I18n::has($token, $defaultLocale)) {
                $missingInDefault[] = $token;
            }
        }
        
        // Display results
        $this->displayResults($foundTokens, $newTokens, $missingInDefault, $orphanTokens, $showOrphans);
        
        // Write new tokens if requested
        if ($write && $newTokens !== []) {
            $this->writeNewTokens($newTokens, $defaultLocale);
            echo "\n" . ColorHelper::success("✅ Added " . count($newTokens) . " new tokens to translation files.") . "\n";
        }
        
        return 0;
    }
    
    private function scanForTokens(array $scanPaths, array $patterns): array
    {
        $tokens = [];
        
        foreach ($scanPaths as $path) {
            $fullPath = App::basePath($path);
            if (is_dir($fullPath)) {
                $tokens = array_merge($tokens, $this->scanDirectory($fullPath, $patterns));
            }
        }
        
        return array_unique($tokens);
    }
    
    private function scanDirectory(string $directory, array $patterns): array
    {
        $tokens = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/\.(php|html|twig|blade\.php)$/', (string) $file->getFilename())) {
                $tokens = array_merge($tokens, $this->scanFile($file->getPathname(), $patterns));
            }
        }
        
        return $tokens;
    }
    
    private function scanFile(string $filePath, array $patterns): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }
        
        $tokens = [];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $token) {
                    $tokens[] = $token;
                }
            }
        }
        
        return $tokens;
    }
    
    private function getExistingTokens(string $locale): array
    {
        $tokens = [];
        $namespaces = I18n::getAvailableNamespaces($locale);
        
        foreach ($namespaces as $namespace) {
            $translations = I18n::getInstance()->loadTranslations($locale, $namespace);
            $tokens = array_merge($tokens, array_keys($translations));
        }
        
        return $tokens;
    }
    
    private function displayResults(array $foundTokens, array $newTokens, array $missingInDefault, array $orphanTokens, bool $showOrphans): void
    {
        echo ColorHelper::header("📊 Scan Results") . "\n";
        echo ColorHelper::info("  Total tokens found in code: ") . ColorHelper::colorize((string)count($foundTokens), ColorHelper::CYAN) . "\n";
        echo ColorHelper::info("  New tokens: ") . ColorHelper::colorize((string)count($newTokens), ColorHelper::GREEN) . "\n";
        echo ColorHelper::info("  Missing in default locale: ") . ColorHelper::colorize((string)count($missingInDefault), ColorHelper::YELLOW) . "\n";
        echo ColorHelper::info("  Orphaned tokens: ") . ColorHelper::colorize((string)count($orphanTokens), ColorHelper::RED) . "\n\n";
        
        if ($newTokens !== []) {
            echo ColorHelper::success("🆕 New tokens found:") . "\n";
            foreach ($newTokens as $token) {
                echo ColorHelper::success(sprintf('  - %s', $token)) . "\n";
            }

            echo "\n";
        }
        
        if ($missingInDefault !== []) {
            echo ColorHelper::warning("⚠️  Tokens missing in default locale:") . "\n";
            foreach ($missingInDefault as $token) {
                echo ColorHelper::warning(sprintf('  - %s', $token)) . "\n";
            }

            echo "\n";
        }
        
        if ($showOrphans && $orphanTokens !== []) {
            echo ColorHelper::comment("🗑️  Orphaned tokens (not used in code):") . "\n";
            foreach ($orphanTokens as $token) {
                echo ColorHelper::comment(sprintf('  - %s', $token)) . "\n";
            }

            echo "\n";
        }
    }
    
    private function writeNewTokens(array $newTokens, string $locale): void
    {
        $tokensByNamespace = [];
        
        // Group tokens by namespace
        foreach ($newTokens as $token) {
            $namespace = explode('.', (string) $token, 2)[0] ?? 'common';
            if (!isset($tokensByNamespace[$namespace])) {
                $tokensByNamespace[$namespace] = [];
            }

            $tokensByNamespace[$namespace][$token] = '';
        }
        
        // Save to translation files
        $i18n = I18n::getInstance();
        foreach ($tokensByNamespace as $namespace => $tokens) {
            // Load existing translations
            $existingTranslations = $i18n->loadTranslations($locale, $namespace);
            
            // Merge with new tokens
            $updatedTranslations = array_merge($existingTranslations, $tokens);
            
            // Save to file
            $i18n->saveTranslations($locale, $namespace, $updatedTranslations);
        }
    }
}
