<?php

namespace BaseApi\Support\Translation;

use CurlHandle;
use Override;
use BaseApi\App;

class DeepLProvider implements TranslationProvider
{
    private readonly string $apiKey;

    private readonly string $formality;

    private string $baseUrl = 'https://api-free.deepl.com/v2';
     // Use https://api.deepl.com/v2 for pro
    private int $maxRetries = 6;

    private int $maxDelay = 30;
     // seconds
    private static CurlHandle|bool|null $curlHandle = null;

    // Supported languages (DeepL language codes)
    private array $supportedLanguages = [
        'bg', 'cs', 'da', 'de', 'el', 'en', 'es', 'et', 'fi', 'fr',
        'hu', 'id', 'it', 'ja', 'ko', 'lt', 'lv', 'nb', 'nl', 'pl',
        'pt', 'ro', 'ru', 'sk', 'sl', 'sv', 'tr', 'uk', 'zh'
    ];

    public function __construct(array $config = [])
    {
        // Load the complete i18n config
        $configPath = App::basePath('config/i18n.php');
        $i18nConfig = file_exists($configPath) ? require $configPath : [];
        $defaultConfig = $i18nConfig['provider_config']['deepl'] ?? [];
        $config = array_merge($defaultConfig, $config);

        $this->apiKey = $config['api_key'] ?? '';
        $this->formality = $config['formality'] ?? 'default';

        if (empty($this->apiKey)) {
            throw new TranslationException('DeepL API key is required');
        }
    }

    #[Override]
    public function translate(string $text, string $from, string $to, array $hints = []): string
    {
        $results = $this->translateBatch([$text], $from, $to, $hints);
        return $results[0];
    }

    #[Override]
    public function translateBatch(array $texts, string $from, string $to, array $hints = []): array
    {
        if ($texts === []) {
            return [];
        }

        // Convert language codes if necessary
        $sourceLanguage = $this->normalizeLanguageCode($from);
        $targetLanguage = $this->normalizeLanguageCode($to);

        if (!$this->supportsLanguagePair($sourceLanguage, $targetLanguage)) {
            throw new TranslationException(sprintf('Unsupported language pair: %s -> %s', $from, $to));
        }

        // Use concurrency control to prevent API bursts
        return $this->withConcurrencyControl(function() use ($texts, $sourceLanguage, $targetLanguage): array {
            // Prepare request data
            $data = [
                'text' => $texts,
                'target_lang' => strtoupper($targetLanguage),
            ];

            // Only add source language if specified (DeepL can auto-detect)
            if ($sourceLanguage !== 'auto') {
                $data['source_lang'] = strtoupper($sourceLanguage);
            }

            // Add formality if target language supports it
            if (in_array($targetLanguage, ['de', 'fr', 'it', 'es', 'nl', 'pl', 'pt', 'ru'])) {
                $data['formality'] = $this->formality;
            }

            // Make API request
            $response = $this->makeRequest('/translate', $data);

            if (!isset($response['translations'])) {
                throw new TranslationException('Invalid response from DeepL API');
            }

            // Extract translated texts
            $results = [];
            foreach ($response['translations'] as $translation) {
                $results[] = $translation['text'];
            }

            return $results;
        });
    }

    #[Override]
    public function supportsLanguagePair(string $from, string $to): bool
    {
        $from = $this->normalizeLanguageCode($from);
        $to = $this->normalizeLanguageCode($to);

        // 'auto' means DeepL will auto-detect source language
        if ($from === 'auto') {
            return in_array($to, $this->supportedLanguages);
        }

        return in_array($from, $this->supportedLanguages) && 
               in_array($to, $this->supportedLanguages) &&
               $from !== $to;
    }

    #[Override]
    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }

    /**
     * Normalize language codes for DeepL
     */
    private function normalizeLanguageCode(string $code): string
    {
        // Handle special cases
        if ($code === 'auto') {
            return 'auto';
        }

        // Handle common variations
        $mapping = [
            'en-US' => 'en',
            'en-GB' => 'en',
            'pt-BR' => 'pt',
            'pt-PT' => 'pt',
            'zh-CN' => 'zh',
            'zh-TW' => 'zh',
        ];

        return $mapping[$code] ?? $code;
    }

    /**
     * Make HTTP request to DeepL API with retry logic and connection reuse
     */
    private function makeRequest(string $endpoint, array $data): array
    {
        $url = $this->baseUrl . $endpoint;
        $jsonData = json_encode($data);

        // Check payload size (DeepL limit is ~128KB)
        if (strlen($jsonData) > 128 * 1024) {
            throw new TranslationException('Request payload exceeds DeepL 128KB limit');
        }

        $attempt = 0;

        while ($attempt <= $this->maxRetries) {
            $curl = $this->getCurlHandle();

            // Configure request
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_HTTPHEADER => [
                    'Authorization: DeepL-Auth-Key ' . $this->apiKey,
                    'Content-Type: application/json',
                    'User-Agent: BaseAPI/1.0',
                    'Connection: keep-alive',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_HEADER => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

            if ($response === false) {
                $error = curl_error($curl);
                if ($attempt >= $this->maxRetries) {
                    throw new TranslationException('cURL error: ' . $error);
                }

                $attempt++;
                continue;
            }

            $headers = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);

            // Success case
            if ($httpCode === 200) {
                $decoded = json_decode($body, true);
                if (!is_array($decoded)) {
                    throw new TranslationException('Invalid JSON response from DeepL API');
                }

                return $decoded;
            }

            // Don't retry client errors (except 429)
            if ($httpCode >= 400 && $httpCode < 500 && $httpCode !== 429) {
                $error = json_decode($body, true);
                $message = $error['message'] ?? sprintf('DeepL API error (HTTP %s)', $httpCode);
                throw new TranslationException($message);
            }

            // Retry for 429 and 5xx errors
            if ($httpCode === 429 || $httpCode >= 500) {
                if ($attempt >= $this->maxRetries) {
                    $error = json_decode($body, true);
                    $message = $error['message'] ?? sprintf('DeepL API error (HTTP %s)', $httpCode);
                    throw new TranslationException($message);
                }

                // Calculate delay with exponential backoff and jitter
                $retryAfter = $this->extractRetryAfter($headers);
                $baseDelay = max($retryAfter, 1);
                $exponentialDelay = min($this->maxDelay, $baseDelay * (2 ** $attempt));
                $jitter = mt_rand(0, 1000) / 1000; // 0-1 second jitter
                $delay = $exponentialDelay + $jitter;

                usleep((int)($delay * 1_000_000));
                $attempt++;
                continue;
            }

            // Unexpected status code
            $error = json_decode($body, true);
            $message = $error['message'] ?? sprintf('Unexpected DeepL API response (HTTP %s)', $httpCode);
            throw new TranslationException($message);
        }

        throw new TranslationException('Max retries exceeded');
    }

    /**
     * Get or create persistent cURL handle with keep-alive
     */
    private function getCurlHandle()
    {
        if (self::$curlHandle === null) {
            self::$curlHandle = curl_init();

            // Set persistent connection options
            curl_setopt_array(self::$curlHandle, [
                CURLOPT_TCP_KEEPALIVE => 1,
                CURLOPT_TCP_KEEPIDLE => 120,
                CURLOPT_TCP_KEEPINTVL => 60,
                CURLOPT_FORBID_REUSE => false,
                CURLOPT_FRESH_CONNECT => false,
            ]);
        }

        return self::$curlHandle;
    }

    /**
     * Extract Retry-After header value
     */
    private function extractRetryAfter(string $headers): int
    {
        if (preg_match('/retry-after:\s*(\d+)/i', $headers, $matches)) {
            return (int)$matches[1];
        }

        return 0;
    }

    /**
     * Execute callback with concurrency control to prevent API bursts
     */
    private function withConcurrencyControl(callable $callback)
    {
        $lockFile = sys_get_temp_dir() . '/deepl_api.lock';
        $handle = fopen($lockFile, 'c+');

        if (!$handle) {
            throw new TranslationException('Failed to create concurrency lock file');
        }

        try {
            // Acquire exclusive lock
            if (!flock($handle, LOCK_EX)) {
                throw new TranslationException('Failed to acquire concurrency lock');
            }

            // Execute the callback
            return $callback();
        } finally {
            // Always release the lock
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * Clean up cURL handle on destruction
     */
    public function __destruct()
    {
        if (self::$curlHandle !== null) {
            curl_close(self::$curlHandle);
            self::$curlHandle = null;
        }
    }
}
