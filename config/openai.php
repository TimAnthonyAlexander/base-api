<?php

/**
 * OpenAI Configuration
 * 
 * This file contains the configuration for OpenAI Responses API integration.
 * Configure your API key and default settings for AI-powered features.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key
    |--------------------------------------------------------------------------
    |
    | Your OpenAI API key. Get one at https://platform.openai.com/api-keys
    | IMPORTANT: Never commit your API key to version control.
    |
    */
    'api_key' => $_ENV['OPENAI_API_KEY'] ?? '',

    /*
    |--------------------------------------------------------------------------
    | Default Model
    |--------------------------------------------------------------------------
    |
    | The default model to use for OpenAI API requests. You can override this
    | per-request using the model() method.
    |
    | Available models:
    | - gpt-5.1-mini: Fast, cost-effective for simple tasks
    | - gpt-5.1: Balanced performance and capability
    | - o4-mini: Reasoning-focused model for complex problems
    | - o4: Advanced reasoning model
    |
    */
    'default_model' => $_ENV['OPENAI_DEFAULT_MODEL'] ?? 'gpt-5.1-mini',

    /*
    |--------------------------------------------------------------------------
    | Default Options
    |--------------------------------------------------------------------------
    |
    | Default options applied to all requests unless overridden.
    |
    */
    'temperature' => (float)($_ENV['OPENAI_TEMPERATURE'] ?? 1.0),
    'max_output_tokens' => (int)($_ENV['OPENAI_MAX_TOKENS'] ?? 1000),

    /*
    |--------------------------------------------------------------------------
    | Timeout Settings
    |--------------------------------------------------------------------------
    |
    | HTTP timeout for OpenAI API requests in seconds.
    |
    */
    'timeout' => (int)($_ENV['OPENAI_TIMEOUT'] ?? 30),

    /*
    |--------------------------------------------------------------------------
    | Request Retry
    |--------------------------------------------------------------------------
    |
    | Number of retries for failed requests due to rate limits or network issues.
    |
    */
    'max_retries' => (int)($_ENV['OPENAI_MAX_RETRIES'] ?? 3),
];

