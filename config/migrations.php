<?php

/**
 * Migration Configuration
 * 
 * This file defines settings for the migration system, including
 * system tables that should be protected from automatic dropping.
 */

return [
    /**
     * System tables that should never be dropped during migration generation.
     * These are typically framework/infrastructure tables that don't have
     * corresponding model files.
     * 
     * Note: The core system tables (jobs, migrations, schema_info, cache, sessions)
     * are already protected by default. This array allows you to add additional
     * tables that your application creates outside of the model system.
     */
    'system_tables' => [
        'jobs',
    ],

    /**
     * Migration file path (relative to base path)
     */
    'file' => $_ENV['MIGRATIONS_FILE'] ?? 'storage/migrations.json',
];
