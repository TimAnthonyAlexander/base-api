<?php

return [
    // Default locale
    'default' => $_ENV['I18N_DEFAULT_LOCALE'] ?? 'en',
    
    // Available locales
    'locales' => explode(',', $_ENV['I18N_LOCALES'] ?? 'en,de,fr'),
    
    // Predefined namespaces (null allows dynamic namespaces)
    'namespaces' => null, // or ['common', 'emails', 'errors', 'admin']
    
    // Translation provider for auto-translation
    'provider' => $_ENV['I18N_PROVIDER'] ?? null, // 'deepl', 'openai', or null
    
    // Provider configuration
    'provider_config' => [
        'deepl' => [
            'api_key' => $_ENV['DEEPL_API_KEY'] ?? '',
            'formality' => 'default', // 'more', 'less', 'default'
        ],
        'openai' => [
            'api_key' => $_ENV['OPENAI_API_KEY'] ?? '',
            'model' => 'gpt-4o-mini',
            'temperature' => 0.3,
        ],
    ],
    
    // Fallback behavior when translation is missing
    'fallback_behavior' => 'default_locale', // 'key' or 'default_locale'
    
    // ETag strategy for caching
    'etag_strategy' => 'content_sha256',
    
    // Allow dynamic namespaces (derived from token prefixes)
    'allow_dynamic_namespaces' => true,
    
    // Warn when new namespaces are detected
    'warn_on_new_namespace' => true,
    
    // Optional namespace whitelist for strict governance
    'namespaces_whitelist' => null, // or ['common', 'emails', 'errors', 'admin']
    
    // Paths to scan for translation tokens
    'scan_paths' => [
        'app/',
        'resources/',
    ],
    
    // Token patterns to search for
    'token_patterns' => [
        "/t\(['\"]([^'\"]+)['\"]\)/", // t('token')
        "/T::t\(['\"]([^'\"]+)['\"]\)/", // T::t('token')
        "/@t\(['\"]([^'\"]+)['\"]\)/", // @t('token') in templates
        "/__\(['\"]([^'\"]+)['\"]\)/", // __('token') Laravel style
    ],
    
    // Cache settings
    'cache' => [
        'ttl' => 300, // 5 minutes
        'enabled' => true,
    ],
    
    // File format settings
    'pretty_print' => true,
    'sort_keys' => true,
];
