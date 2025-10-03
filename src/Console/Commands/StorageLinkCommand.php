<?php

namespace BaseApi\Console\Commands;

use Override;
use BaseApi\Console\Application;
use BaseApi\Console\Command;
use BaseApi\Console\ColorHelper;

class StorageLinkCommand implements Command
{
    #[Override]
    public function name(): string
    {
        return 'storage:link';
    }

    #[Override]
    public function description(): string
    {
        return 'Create a symbolic link from "public/storage" to "storage/public"';
    }

    #[Override]
    public function execute(array $args, ?Application $app = null): int
    {
        $basePath = $app?->basePath() ?? getcwd();
        
        $storagePublicPath = $basePath . '/storage/public';
        $publicStoragePath = $basePath . '/public/storage';
        
        // Create storage/public directory if it doesn't exist
        if (!is_dir($storagePublicPath)) {
            if (!mkdir($storagePublicPath, 0755, true)) {
                echo ColorHelper::error('❌ Failed to create storage/public directory') . "\n";
                return 1;
            }
            echo ColorHelper::success('✓ Created storage/public directory') . "\n";
        }
        
        // Check if public/storage already exists
        if (file_exists($publicStoragePath) || is_link($publicStoragePath)) {
            if (is_link($publicStoragePath)) {
                $linkTarget = readlink($publicStoragePath);
                if (realpath($linkTarget) === realpath($storagePublicPath)) {
                    echo ColorHelper::comment('ℹ The "public/storage" link already exists and points to the correct location') . "\n";
                    return 0;
                }
                
                // Remove incorrect symlink
                if (!unlink($publicStoragePath)) {
                    echo ColorHelper::error('❌ Failed to remove existing symlink') . "\n";
                    return 1;
                }
                echo ColorHelper::comment('⚠ Removed incorrect symlink') . "\n";
            } else {
                echo ColorHelper::error('❌ The public/storage path already exists as a file or directory (not a symlink)') . "\n";
                echo ColorHelper::comment('   Please remove it manually before running this command') . "\n";
                return 1;
            }
        }
        
        // Create the symlink
        if (!symlink($storagePublicPath, $publicStoragePath)) {
            echo ColorHelper::error('❌ Failed to create symlink') . "\n";
            return 1;
        }
        
        echo ColorHelper::success('✓ The [public/storage] link has been connected to [storage/public]') . "\n";
        
        return 0;
    }
}

