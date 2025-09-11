<?php

namespace BaseApi\Console\Commands;

use BaseApi\Console\Command;
use BaseApi\App;
use BaseApi\Support\I18n;

class I18nHashCommand implements Command
{
    public function name(): string
    {
        return 'i18n:hash';
    }
    
    public function description(): string
    {
        return 'Generate hash for translation bundles';
    }
    
    public function execute(array $args, ?\BaseApi\Console\Application $app = null): int
    {
        // Load the complete i18n config
        $configPath = App::basePath('config/i18n.php');
        $config = file_exists($configPath) ? require $configPath : [];
        
        // Parse arguments
        $locale = $config['default'];
        $namespaces = [];
        $showAll = false;
        
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--lang=')) {
                $locale = substr($arg, 7);
            } elseif (str_starts_with($arg, '--ns=')) {
                $namespaces = array_filter(array_map('trim', explode(',', substr($arg, 5))));
            } elseif ($arg === '--all') {
                $showAll = true;
            }
        }
        
        // Validate locale
        if (!in_array($locale, $config['locales'])) {
            echo "Invalid locale: {$locale}\n";
            echo "Available locales: " . implode(', ', $config['locales']) . "\n";
            return 1;
        }
        
        // If no namespaces specified, use all available
        if (empty($namespaces)) {
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
        if (empty($namespaces)) {
            echo "No translation namespaces found for locale: {$locale}\n";
            return;
        }
        
        $hash = I18n::getInstance()->generateETag($locale, $namespaces);
        
        echo "Locale: {$locale}\n";
        echo "Namespaces: " . implode(', ', $namespaces) . "\n";
        echo "Hash: {$hash}\n";
        
        // Also show bundle information
        $bundle = I18n::bundle($locale, $namespaces);
        $tokenCount = count($bundle['tokens']);
        echo "Tokens: {$tokenCount}\n";
    }
    
    private function showAllHashes(array $locales): void
    {
        echo "Translation bundle hashes:\n\n";
        
        foreach ($locales as $locale) {
            $namespaces = I18n::getAvailableNamespaces($locale);
            
            if (empty($namespaces)) {
                echo "{$locale}: No translation files\n";
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
