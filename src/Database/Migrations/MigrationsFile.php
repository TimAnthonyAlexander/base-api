<?php

namespace BaseApi\Database\Migrations;

class MigrationsFile
{
    public static function read(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }
        
        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }
        
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        return $data;
    }

    public static function write(string $path, array $data): void
    {
        // Ensure directory exists
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($path, $json);
    }

    /**
     * Read migrations in new format
     */
    public static function readMigrations(string $path): array
    {
        $data = self::read($path);
        if ($data === null) {
            return [];
        }
        
        // Handle new format
        if (isset($data['migrations']) && isset($data['version'])) {
            return $data['migrations'];
        }
        
        // Handle legacy format - convert old plan format to individual migrations
        if (isset($data['plan'])) {
            return self::convertLegacyPlan($data['plan']);
        }
        
        return [];
    }

    /**
     * Write migrations in new format
     */
    public static function writeMigrations(string $path, array $migrations): void
    {
        $data = [
            'version' => '1.0',
            'migrations' => $migrations
        ];
        
        self::write($path, $data);
    }

    /**
     * Append new migrations to existing file
     */
    public static function appendMigrations(string $path, array $newMigrations): void
    {
        $existing = self::readMigrations($path);
        $existingIds = array_column($existing, 'id');
        
        // Filter out duplicates
        $toAdd = array_filter($newMigrations, function($migration) use ($existingIds) {
            return !in_array($migration['id'], $existingIds);
        });
        
        if (!empty($toAdd)) {
            $combined = array_merge($existing, $toAdd);
            self::writeMigrations($path, $combined);
        }
    }

    /**
     * Generate unique ID for a migration
     */
    public static function generateMigrationId(string $sql, ?string $table, string $operation): string
    {
        // Create a hash based on sql content and current timestamp for uniqueness
        $content = $sql . ($table ?? 'unknown') . $operation . microtime(true);
        return 'mig_' . substr(md5($content), 0, 12);
    }

    /**
     * Convert legacy plan format to individual migration statements
     */
    private static function convertLegacyPlan(array $plan): array
    {
        $migrations = [];
        
        foreach ($plan as $operation) {
            $sql = "-- Legacy operation: " . $operation['op'];
            $migrations[] = [
                'id' => self::generateMigrationId($sql, $operation['table'] ?? 'unknown', $operation['op']),
                'sql' => $sql,
                'destructive' => $operation['destructive'] ?? false,
                'generated_at' => date('c'),
                'table' => $operation['table'] ?? null,
                'operation' => $operation['op']
            ];
        }
        
        return $migrations;
    }

    // Legacy methods for backward compatibility
    public static function markApplied(string $path): void
    {
        $data = self::read($path);
        if ($data === null) {
            return;
        }
        
        $data['applied_at'] = date('c');
        self::write($path, $data);
    }

    public static function isApplied(string $path): bool
    {
        $data = self::read($path);
        return $data !== null && isset($data['applied_at']);
    }
}
