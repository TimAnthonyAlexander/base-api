<?php

namespace BaseApi\Console;

use BaseApi\Console\Commands\ServeCommand;
use BaseApi\Console\Commands\MakeControllerCommand;
use BaseApi\Console\Commands\MakeModelCommand;
use BaseApi\Console\Commands\MigrateGenerateCommand;
use BaseApi\Console\Commands\MigrateApplyCommand;
use BaseApi\Console\Commands\TypesGenerateCommand;
use BaseApi\Console\Commands\I18nScanCommand;
use BaseApi\Console\Commands\I18nAddLangCommand;
use BaseApi\Console\Commands\I18nFillCommand;
use BaseApi\Console\Commands\I18nLintCommand;
use BaseApi\Console\Commands\I18nHashCommand;
use BaseApi\Console\Commands\QueueWorkCommand;
use BaseApi\Console\Commands\QueueStatusCommand;
use BaseApi\Console\Commands\JobMakeCommand;
use BaseApi\Console\Commands\QueueRetryCommand;
use BaseApi\Console\Commands\CreateJobsTableCommand;
use BaseApi\Console\Commands\StorageLinkCommand;
use BaseApi\Console\Commands\CacheClearCommand;
use BaseApi\Console\Commands\CacheCleanupCommand;
use BaseApi\Console\Commands\CacheStatsCommand;
use Dotenv\Dotenv;

/**
 * Mason CLI Application Bootstrap
 * 
 * Handles environment setup, command registration, and application execution.
 */
class MasonBootstrap
{
    public function __construct(private readonly string $baseAppPath)
    {
    }
    
    /**
     * Run the Mason CLI application
     */
    public function run(array $argv): int
    {
        // Load environment if available
        $this->loadEnvironment();
        
        // Create application instance
        $app = new Application($this->baseAppPath);
        
        // Register all commands
        $this->registerCommands($app);
        
        // Run the application
        return $app->run($argv);
    }
    
    /**
     * Load environment variables from .env file
     */
    private function loadEnvironment(): void
    {
        $envPath = $this->baseAppPath . '/.env';
        
        if (file_exists($envPath)) {
            $dotenv = Dotenv::createImmutable($this->baseAppPath);
            $dotenv->load();
        }
    }
    
    /**
     * Register all available commands
     */
    private function registerCommands(Application $app): void
    {
        // Core commands
        $app->register('serve', new ServeCommand());
        $app->register('make:controller', new MakeControllerCommand());
        $app->register('make:model', new MakeModelCommand());
        $app->register('migrate:generate', new MigrateGenerateCommand());
        $app->register('migrate:apply', new MigrateApplyCommand());
        $app->register('types:generate', new TypesGenerateCommand());
        
        // i18n commands
        $app->register('i18n:scan', new I18nScanCommand());
        $app->register('i18n:add-lang', new I18nAddLangCommand());
        $app->register('i18n:fill', new I18nFillCommand());
        $app->register('i18n:lint', new I18nLintCommand());
        $app->register('i18n:hash', new I18nHashCommand());
        
        // Queue commands
        $app->register('queue:work', new QueueWorkCommand());
        $app->register('queue:status', new QueueStatusCommand());
        $app->register('queue:retry', new QueueRetryCommand());
        $app->register('queue:install', new CreateJobsTableCommand());
        $app->register('make:job', new JobMakeCommand());
        
        // Storage commands
        $app->register('storage:link', new StorageLinkCommand());
        
        // Cache commands
        $app->register('cache:clear', new CacheClearCommand());
        $app->register('cache:cleanup', new CacheCleanupCommand());
        $app->register('cache:stats', new CacheStatsCommand());
    }
    
    /**
     * Detect the base application path
     * 
     * Walks up the directory tree looking for a composer.json with type "project"
     */
    public static function detectBasePath(): string
    {
        $currentPath = getcwd();
        
        // Look for composer.json that indicates this is an application (not the framework itself)
        while ($currentPath !== '/') {
            $composerFile = $currentPath . '/composer.json';
            
            if (file_exists($composerFile)) {
                $composer = json_decode(file_get_contents($composerFile), true);
                
                if (($composer['type'] ?? 'library') === 'project') {
                    return $currentPath;
                }
            }
            
            $currentPath = dirname($currentPath);
        }
        
        // If we can't detect, use current working directory
        return getcwd();
    }
    
    /**
     * Locate the composer autoloader
     * 
     * Tries multiple common locations for the autoloader file
     */
    public static function locateAutoloader(string $baseAppPath): ?string
    {
        $autoloaderPaths = [
            $baseAppPath . '/vendor/autoload.php',
            __DIR__ . '/../vendor/autoload.php',
            __DIR__ . '/../../autoload.php',
            __DIR__ . '/../../../autoload.php'
        ];
        
        foreach ($autoloaderPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return null;
    }
}
