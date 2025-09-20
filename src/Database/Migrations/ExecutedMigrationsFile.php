<?php

namespace BaseApi\Database\Migrations;

class ExecutedMigrationsFile
{
    public static function read(string $path): array
    {
        if (!file_exists($path)) {
            return ['executed' => [], 'last_executed_at' => null];
        }
        
        $content = file_get_contents($path);
        if ($content === false) {
            return ['executed' => [], 'last_executed_at' => null];
        }
        
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['executed' => [], 'last_executed_at' => null];
        }
        
        return [
            'executed' => $data['executed'] ?? [],
            'last_executed_at' => $data['last_executed_at'] ?? null
        ];
    }

    public static function write(string $path, array $executed, ?string $lastExecutedAt = null): void
    {
        // Ensure directory exists
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $data = [
            'executed' => $executed,
            'last_executed_at' => $lastExecutedAt ?? date('c')
        ];
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($path, $json);
    }

    public static function addExecuted(string $path, string $migrationId): void
    {
        $data = self::read($path);
        
        if (!in_array($migrationId, $data['executed'])) {
            $data['executed'][] = $migrationId;
            self::write($path, $data['executed']);
        }
    }

    public static function addMultipleExecuted(string $path, array $migrationIds): void
    {
        $data = self::read($path);
        
        $updated = false;
        foreach ($migrationIds as $migrationId) {
            if (!in_array($migrationId, $data['executed'])) {
                $data['executed'][] = $migrationId;
                $updated = true;
            }
        }
        
        if ($updated) {
            self::write($path, $data['executed']);
        }
    }

    public static function isExecuted(string $path, string $migrationId): bool
    {
        $data = self::read($path);
        return in_array($migrationId, $data['executed']);
    }

    public static function getPendingMigrations(array $allMigrations, string $executedPath): array
    {
        $executed = self::read($executedPath)['executed'];
        
        return array_filter($allMigrations, function($migration) use ($executed) {
            return !in_array($migration['id'], $executed);
        });
    }
}
