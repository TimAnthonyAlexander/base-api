<?php

namespace BaseApi\Support\Translation;

use BaseApi\App;

class DeepLProvider implements TranslationProvider
{
    private string $apiKey;
    private string $formality;
    private string $baseUrl = 'https://api-free.deepl.com/v2'; // Use api.deepl.com for pro
    
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
    
    public function translate(string $text, string $from, string $to, array $hints = []): string
    {
        $results = $this->translateBatch([$text], $from, $to, $hints);
        return $results[0];
    }
    
    public function translateBatch(array $texts, string $from, string $to, array $hints = []): array
    {
        if (empty($texts)) {
            return [];
        }
        
        // Convert language codes if necessary
        $sourceLanguage = $this->normalizeLanguageCode($from);
        $targetLanguage = $this->normalizeLanguageCode($to);
        
        if (!$this->supportsLanguagePair($sourceLanguage, $targetLanguage)) {
            throw new TranslationException("Unsupported language pair: {$from} -> {$to}");
        }
        
        // Prepare request data
        $data = [
            'auth_key' => $this->apiKey,
            'text' => $texts,
            'source_lang' => strtoupper($sourceLanguage),
            'target_lang' => strtoupper($targetLanguage),
            'preserve_formatting' => '1',
            'tag_handling' => 'html', // Preserve placeholders like {name}
        ];
        
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
    }
    
    public function supportsLanguagePair(string $from, string $to): bool
    {
        $from = $this->normalizeLanguageCode($from);
        $to = $this->normalizeLanguageCode($to);
        
        return in_array($from, $this->supportedLanguages) && 
               in_array($to, $this->supportedLanguages) &&
               $from !== $to;
    }
    
    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }
    
    /**
     * Normalize language codes for DeepL
     */
    private function normalizeLanguageCode(string $code): string
    {
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
     * Make HTTP request to DeepL API
     */
    private function makeRequest(string $endpoint, array $data): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'User-Agent: BaseAPI/1.0',
                ],
                'content' => http_build_query($data),
                'timeout' => 30,
            ],
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new TranslationException('Failed to connect to DeepL API');
        }
        
        // Check HTTP response code
        $statusLine = $http_response_header[0] ?? '';
        if (!preg_match('/HTTP\/\d\.\d\s+(\d+)/', $statusLine, $matches)) {
            throw new TranslationException('Invalid HTTP response from DeepL API');
        }
        
        $statusCode = (int)$matches[1];
        if ($statusCode !== 200) {
            $error = json_decode($response, true);
            $message = $error['message'] ?? "DeepL API error (HTTP {$statusCode})";
            throw new TranslationException($message);
        }
        
        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new TranslationException('Invalid JSON response from DeepL API');
        }
        
        return $decoded;
    }
}
