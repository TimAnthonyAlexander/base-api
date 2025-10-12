<?php

namespace BaseApi\Console\Commands;

use Override;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;
use BaseApi\App;
use BaseApi\Support\I18n;

class I18nHashCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'i18n:hash';
    }
    
    #[Override]
    public function description(): string
    {
        return 'Generate hash for translation bundles';
    }
    
    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        // Load the complete i18n config
        $configPath = App::basePath('config/i18n.php');
        $config = file_exists($configPath) ? require $configPath : [];
        
        // Parse arguments
        $locale = $config['default'];
        $namespaces = [];
        $showAll = false;
        
        foreach ($args as $arg) {
            if (str_starts_with((string) $arg, '--lang=')) {
                $locale = substr((string) $arg, 7);
            } elseif (str_starts_with((string) $arg, '--ns=')) {
                $namespaces = array_filter(array_map('trim', explode(',', substr((string) $arg, 5))));
            } elseif ($arg === '--all') {
                $showAll = true;
            }
        }
        
        // Validate locale
        if (!in_array($locale, $config['locales'])) {
            echo ColorHelper::error(sprintf('❌ Invalid locale: %s', $locale)) . "\n";
            echo ColorHelper::info("Available locales: ") . ColorHelper::colorize(implode(', ', $config['locales']), ColorHelper::YELLOW) . "\n";
            return 1;
        }
        
        // If no namespaces specified, use all available
        if ($namespaces === []) {
            $namespaces = I18n::getAvailableNamespaces($locale);
        }
        
        if ($showAll) {
            $this->showAllHashes($config['locales']);
        } else {
            $this->showHash($locale, $namespaces);
        }
        
        return 0;
    }
    
    private function showHash(string $locale, array $namespaces): void
    {
        if ($namespaces === []) {
            echo ColorHelper::warning(sprintf('⚠️  No translation namespaces found for locale: %s', $locale)) . "\n";
            return;
        }
        
        $hash = I18n::getInstance()->generateETag($locale, $namespaces);
        
        echo ColorHelper::info('Locale: ') . ColorHelper::colorize($locale, ColorHelper::CYAN) . "\n";
        echo ColorHelper::info('Namespaces: ') . ColorHelper::colorize(implode(', ', $namespaces), ColorHelper::YELLOW) . "\n";
        echo ColorHelper::info('Hash: ') . ColorHelper::colorize($hash, ColorHelper::MAGENTA) . "\n";
        
        // Also show bundle information
        $bundle = I18n::bundle($locale, $namespaces);
        $tokenCount = count($bundle['tokens']);
        echo ColorHelper::info('Tokens: ') . ColorHelper::colorize((string)$tokenCount, ColorHelper::GREEN) . "\n";
    }
    
    private function showAllHashes(array $locales): void
    {
        echo ColorHelper::header("🏷️  Translation Bundle Hashes") . "\n";
        echo str_repeat('─', 80) . "\n\n";
        
        foreach ($locales as $locale) {
            $namespaces = I18n::getAvailableNamespaces($locale);
            
            if ($namespaces === []) {
                echo $locale . ': No translation files
';
                continue;
            }
            
            // Show hash for all namespaces
            $allHash = I18n::getInstance()->generateETag($locale, $namespaces);
            $bundle = I18n::bundle($locale, $namespaces);
            $tokenCount = count($bundle['tokens']);
            
            echo "{$locale}: {$allHash} ({$tokenCount} tokens)\n";
            
            // Show hash per namespace
            foreach ($namespaces as $namespace) {
                $nsHash = I18n::getInstance()->generateETag($locale, [$namespace]);
                $nsBundle = I18n::bundle($locale, [$namespace]);
                $nsTokenCount = count($nsBundle['tokens']);
                
                echo "  {$namespace}: {$nsHash} ({$nsTokenCount} tokens)\n";
            }
            
            echo "\n";
        }
    }
}
