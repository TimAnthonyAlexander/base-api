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
    | - gpt-4.1: Full reasoning and multimodal capabilities, 1M-token context
    | - gpt-4.1-mini: Lightweight, cost-efficient version of gpt-4.1
    | - gpt-4.1-nano: Ultra-low-latency for embedded and real-time inference
    | - o1: Reasoning-focused with persistent state and advanced planning
    | - o3: Improved reasoning depth and accuracy, smaller footprint
    | - o4-mini: Next-gen reasoning, optimized for concise answers
    | - gpt-5: Unified multimodal reasoning with extended memory
    | - gpt-5-mini: Speed and cost optimized GPT-5 variant
    | - gpt-5-nano: Lowest-latency GPT-5 for rapid inference
    |
    */
    'default_model' => $_ENV['OPENAI_DEFAULT_MODEL'] ?? 'gpt-4.1-mini',

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

