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
use BaseApi\Support\Translation\ICUValidator;

class I18nLintCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'i18n:lint';
    }

    #[Override]
    public function description(): string
    {
        return 'Lint translation files for errors and inconsistencies';
    }

    private array $errors = [];

    private array $warnings = [];

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        $failOnOrphans = in_array('--fail-on-orphans', $args);
        $allowEmpty = in_array('--allow-empty', $args);

        echo ColorHelper::header("🔍 Linting translation files...") . "\n\n";

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
        $this->displayResults();

        // Return exit code
        return $this->errors === [] ? 0 : 1;
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
                if (!$allowEmpty && $locale !== $defaultLocale && in_array(trim((string) $value), ['', '0'], true)) {
                    $this->warnings[] = sprintf("%s/%s: Empty translation for '%s'", $locale, $namespace, $token);
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
                foreach (array_keys($defaultTranslations) as $token) {
                    if ($token !== '__meta' && !isset($translations[$token])) {
                        $this->warnings[] = sprintf("%s/%s: Missing translation for '%s'", $locale, $namespace, $token);
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
                $this->errors[] = sprintf("%s/%s: ICU validation error in '%s' - %s", $locale, $namespace, $token, $error);
            }
        }
    }

    private function checkPlaceholderParity(string $locale, string $namespace, string $token, string $value, string $defaultValue): void
    {
        // Extract only variable names from ICU format, not the translated content
        $placeholders = $this->extractVariableNames($value);
        $defaultPlaceholders = $this->extractVariableNames($defaultValue);

        $missing = array_diff($defaultPlaceholders, $placeholders);
        $extra = array_diff($placeholders, $defaultPlaceholders);

        foreach ($missing as $placeholder) {
            $this->errors[] = sprintf("%s/%s: Missing variable '%s' in '%s'", $locale, $namespace, $placeholder, $token);
        }

        foreach ($extra as $placeholder) {
            $this->warnings[] = sprintf("%s/%s: Extra variable '%s' in '%s'", $locale, $namespace, $placeholder, $token);
        }
    }

    private function extractVariableNames(string $text): array
    {
        $variables = [];

        // Find all top-level placeholders
        preg_match_all('/\{([^{}]+(?:\{[^{}]*\}[^{}]*)*)\}/', $text, $matches);

        foreach ($matches[1] as $match) {
            // For simple placeholders like {name}, just take the name
            if (!str_contains($match, ',')) {
                $variables[] = trim($match);
            } else {
                // For ICU format like {count, plural, one {...} other {...}}, extract just the variable name
                $parts = explode(',', $match, 2);
                $variables[] = trim($parts[0]);
            }
        }

        return array_unique($variables);
    }

    private function checkOrphans(string $defaultLocale): void
    {
        echo ColorHelper::info("🔍 Checking for orphaned tokens...") . "\n";

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
            $this->errors[] = sprintf("Orphaned token '%s' - not used in code", $orphan);
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

    private function checkDuplicates(string $defaultLocale): void
    {
        $i18n = I18n::getInstance();
        $namespaces = I18n::getAvailableNamespaces($defaultLocale);
        $allTokens = [];

        foreach ($namespaces as $namespace) {
            $translations = $i18n->loadTranslations($defaultLocale, $namespace);
            foreach (array_keys($translations) as $token) {
                if ($token !== '__meta') {
                    if (isset($allTokens[$token])) {
                        $this->errors[] = sprintf("Duplicate token '%s' found in both '%s' and '%s'", $token, $allTokens[$token], $namespace);
                    } else {
                        $allTokens[$token] = $namespace;
                    }
                }
            }
        }
    }

    private function displayResults(): void
    {
        $errorCount = count($this->errors);
        $warningCount = count($this->warnings);
        if ($errorCount > 0) {
            echo ColorHelper::error(sprintf('❌ ERRORS (%d):', $errorCount)) . "\n";
            foreach ($this->errors as $error) {
                echo ColorHelper::error(sprintf('  %s', $error)) . "\n";
            }

            echo "\n";
        }

        if ($warningCount > 0) {
            echo ColorHelper::warning(sprintf('⚠️  WARNINGS (%d):', $warningCount)) . "\n";
            foreach ($this->warnings as $warning) {
                echo ColorHelper::warning(sprintf('  %s', $warning)) . "\n";
            }

            echo "\n";
        }

        if ($errorCount === 0 && $warningCount === 0) {
            echo ColorHelper::success("All translation files are valid!") . "\n";
        } else {
            echo ColorHelper::info("📊 Summary: ") . ColorHelper::colorize(sprintf('%d errors, %d warnings', $errorCount, $warningCount), $errorCount > 0 ? ColorHelper::RED : ColorHelper::YELLOW) . "\n";
        }
    }
}
