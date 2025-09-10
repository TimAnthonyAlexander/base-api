<?php

namespace BaseApi\Console\Commands;

use BaseApi\Console\Command;
use BaseApi\App;
use BaseApi\Support\I18n;
use BaseApi\Support\Translation\ICUValidator;

class I18nLintCommand implements Command
{
    public function name(): string
    {
        return 'i18n:lint';
    }
    
    public function description(): string
    {
        return 'Lint translation files for errors and inconsistencies';
    }
    
    private array $errors = [];
    private array $warnings = [];
    
    public function execute(array $args = []): int
    {
        $failOnOrphans = in_array('--fail-on-orphans', $args);
        $allowEmpty = in_array('--allow-empty', $args);
        
        echo "Linting translation files...\n\n";
        
        // Load the complete i18n config
        $configPath = App::basePath('config/i18n.php');
        $config = file_exists($configPath) ? require $configPath : [];
        $defaultLocale = $config['default'] ?? 'en';
        $locales = $config['locales'] ?? ['en'];
        
        // Reset error/warning arrays
        $this->errors = [];
        $this->warnings = [];
        
        // Check each locale
        foreach ($locales as $locale) {
            $this->lintLocale($locale, $defaultLocale, $allowEmpty);
        }
        
        // Check for orphaned tokens if requested
        if ($failOnOrphans) {
            $this->checkOrphans($defaultLocale);
        }
        
        // Check for duplicate tokens across namespaces
        $this->checkDuplicates($defaultLocale);
        
        // Display results
        $this->displayResults($failOnOrphans);
        
        // Return exit code
        return empty($this->errors) ? 0 : 1;
    }
    
    private function lintLocale(string $locale, string $defaultLocale, bool $allowEmpty): void
    {
        $namespaces = I18n::getAvailableNamespaces($locale);
        $i18n = I18n::getInstance();
        
        foreach ($namespaces as $namespace) {
            $translations = $i18n->loadTranslations($locale, $namespace);
            $defaultTranslations = [];
            
            if ($locale !== $defaultLocale) {
                $defaultTranslations = $i18n->loadTranslations($defaultLocale, $namespace);
            }
            
            foreach ($translations as $token => $value) {
                // Skip metadata
                if ($token === '__meta') {
                    continue;
                }
                
                // Check for empty translations (non-default locales)
                if (!$allowEmpty && $locale !== $defaultLocale && empty(trim($value))) {
                    $this->warnings[] = "{$locale}/{$namespace}: Empty translation for '{$token}'";
                }
                
                // Validate ICU message format
                $this->validateICU($locale, $namespace, $token, $value);
                
                // Check placeholder parity with default locale
                if ($locale !== $defaultLocale && isset($defaultTranslations[$token])) {
                    $this->checkPlaceholderParity($locale, $namespace, $token, $value, $defaultTranslations[$token]);
                }
            }
            
            // Check for missing translations in non-default locales
            if ($locale !== $defaultLocale) {
                foreach ($defaultTranslations as $token => $value) {
                    if ($token !== '__meta' && !isset($translations[$token])) {
                        $this->warnings[] = "{$locale}/{$namespace}: Missing translation for '{$token}'";
                    }
                }
            }
        }
    }
    
    private function validateICU(string $locale, string $namespace, string $token, string $value): void
    {
        $validator = new ICUValidator();
        
        if (!$validator->validate($value)) {
            foreach ($validator->getErrors() as $error) {
                $this->errors[] = "{$locale}/{$namespace}: ICU validation error in '{$token}' - {$error}";
            }
        }
    }
    
    private function checkPlaceholderParity(string $locale, string $namespace, string $token, string $value, string $defaultValue): void
    {
        $placeholders = $this->extractPlaceholders($value);
        $defaultPlaceholders = $this->extractPlaceholders($defaultValue);
        
        $missing = array_diff($defaultPlaceholders, $placeholders);
        $extra = array_diff($placeholders, $defaultPlaceholders);
        
        foreach ($missing as $placeholder) {
            $this->errors[] = "{$locale}/{$namespace}: Missing placeholder '{$placeholder}' in '{$token}'";
        }
        
        foreach ($extra as $placeholder) {
            $this->warnings[] = "{$locale}/{$namespace}: Extra placeholder '{$placeholder}' in '{$token}'";
        }
    }
    
    private function extractPlaceholders(string $text): array
    {
        preg_match_all('/\{([^}]+)\}/', $text, $matches);
        $placeholders = [];
        
        foreach ($matches[1] as $match) {
            // For ICU format like {count, plural, ...}, we only care about the variable name
            $parts = explode(',', $match);
            $placeholders[] = trim($parts[0]);
        }
        
        return array_unique($placeholders);
    }
    
    private function checkOrphans(string $defaultLocale): void
    {
        echo "Checking for orphaned tokens...\n";
        
        // Get all tokens from translation files
        $i18n = I18n::getInstance();
        $namespaces = I18n::getAvailableNamespaces($defaultLocale);
        $existingTokens = [];
        
        foreach ($namespaces as $namespace) {
            $translations = $i18n->loadTranslations($defaultLocale, $namespace);
            foreach (array_keys($translations) as $token) {
                if ($token !== '__meta') {
                    $existingTokens[] = $token;
                }
            }
        }
        
        // Scan code for used tokens
        // Load the complete i18n config
        $configPath = App::basePath('config/i18n.php');
        $config = file_exists($configPath) ? require $configPath : [];
        $usedTokens = $this->scanForUsedTokens($config['scan_paths'] ?? ['app/'], $config['token_patterns'] ?? []);
        
        // Find orphans
        $orphans = array_diff($existingTokens, $usedTokens);
        
        foreach ($orphans as $orphan) {
            $this->errors[] = "Orphaned token '{$orphan}' - not used in code";
        }
    }
    
    private function scanForUsedTokens(array $scanPaths, array $patterns): array
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
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/\.(php|html|twig|blade\.php)$/', $file->getFilename())) {
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
    
    private function checkDuplicates(string $defaultLocale): void
    {
        $i18n = I18n::getInstance();
        $namespaces = I18n::getAvailableNamespaces($defaultLocale);
        $allTokens = [];
        
        foreach ($namespaces as $namespace) {
            $translations = $i18n->loadTranslations($defaultLocale, $namespace);
            foreach ($translations as $token => $value) {
                if ($token !== '__meta') {
                    if (isset($allTokens[$token])) {
                        $this->errors[] = "Duplicate token '{$token}' found in both '{$allTokens[$token]}' and '{$namespace}'";
                    } else {
                        $allTokens[$token] = $namespace;
                    }
                }
            }
        }
    }
    
    private function displayResults(bool $failOnOrphans): void
    {
        $errorCount = count($this->errors);
        $warningCount = count($this->warnings);
        
        if ($errorCount > 0) {
            echo "❌ ERRORS ({$errorCount}):\n";
            foreach ($this->errors as $error) {
                echo "  {$error}\n";
            }
            echo "\n";
        }
        
        if ($warningCount > 0) {
            echo "⚠️  WARNINGS ({$warningCount}):\n";
            foreach ($this->warnings as $warning) {
                echo "  {$warning}\n";
            }
            echo "\n";
        }
        
        if ($errorCount === 0 && $warningCount === 0) {
            echo "✅ All translation files are valid!\n";
        } else {
            echo "📊 Summary: {$errorCount} errors, {$warningCount} warnings\n";
        }
    }
}
