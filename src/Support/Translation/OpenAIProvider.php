<?php

namespace BaseApi\Support\Translation;

use Override;
use BaseApi\App;

class OpenAIProvider implements TranslationProvider
{
    private readonly string $apiKey;

    private readonly string $model;

    private readonly float $temperature;

    private string $baseUrl = 'https://api.openai.com/v1';

    // Common language codes that OpenAI supports
    private array $supportedLanguages = [
        'af', 'ar', 'bg', 'bn', 'ca', 'cs', 'da', 'de', 'el', 'en',
        'es', 'et', 'fi', 'fr', 'he', 'hi', 'hr', 'hu', 'id', 'it',
        'ja', 'ko', 'lt', 'lv', 'ms', 'mt', 'nl', 'no', 'pl', 'pt',
        'ro', 'ru', 'sk', 'sl', 'sv', 'th', 'tr', 'uk', 'vi', 'zh'
    ];

    public function __construct(array $config = [])
    {
        // Load the complete i18n config
        $configPath = App::basePath('config/i18n.php');
        $i18nConfig = file_exists($configPath) ? require $configPath : [];
        $defaultConfig = $i18nConfig['provider_config']['openai'] ?? [];
        $config = array_merge($defaultConfig, $config);

        $this->apiKey = $config['api_key'] ?? '';
        $this->model = $config['model'] ?? 'gpt-4o-mini';
        $this->temperature = $config['temperature'] ?? 0.3;

        if (empty($this->apiKey)) {
            throw new TranslationException('OpenAI API key is required');
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

        if (!$this->supportsLanguagePair($from, $to)) {
            throw new TranslationException(sprintf('Unsupported language pair: %s -> %s', $from, $to));
        }

        // Build system prompt
        $systemPrompt = $this->buildSystemPrompt($from, $to, $hints);

        // Prepare texts for translation
        $userPrompt = $this->buildUserPrompt($texts);

        // Make API request
        $response = $this->makeRequest([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => $this->temperature,
            'max_tokens' => $this->calculateMaxTokens($texts),
        ]);

        if (!isset($response['choices'][0]['message']['content'])) {
            throw new TranslationException('Invalid response from OpenAI API');
        }

        $content = trim((string) $response['choices'][0]['message']['content']);

        // Parse the response
        return $this->parseTranslationResponse($content, count($texts));
    }

    #[Override]
    public function supportsLanguagePair(string $from, string $to): bool
    {
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
     * Build system prompt for translation
     */
    private function buildSystemPrompt(string $from, string $to, array $hints): string
    {
        $fromLang = $this->getLanguageName($from);
        $toLang = $this->getLanguageName($to);

        $prompt = sprintf('You are a professional translator. Translate the following text(s) from %s to %s.', $fromLang, $toLang);
        $prompt .= "\n\nIMPORTANT RULES:";
        $prompt .= "\n1. Preserve all placeholders in curly braces exactly as they are (e.g., {name}, {count}, {app_name})";
        $prompt .= "\n2. Maintain ICU MessageFormat syntax for plurals and selects";
        $prompt .= "\n3. Keep the same tone and style as the original";
        $prompt .= "\n4. Return only the translated text(s), no explanations";
        $prompt .= "\n5. For multiple texts, return each translation on a separate line";

        if (!empty($hints['context'])) {
            $prompt .= "\n6. Context: " . $hints['context'];
        }

        if (!empty($hints['formality'])) {
            $prompt .= "\n7. Use " . $hints['formality'] . " tone";
        }

        return $prompt;
    }

    /**
     * Build user prompt with texts to translate
     */
    private function buildUserPrompt(array $texts): string
    {
        if (count($texts) === 1) {
            return $texts[0];
        }

        $prompt = "Translate the following texts:\n\n";
        foreach ($texts as $i => $text) {
            $prompt .= ($i + 1) . ". " . $text . "\n";
        }

        return $prompt;
    }

    /**
     * Parse translation response
     */
    private function parseTranslationResponse(string $content, int $expectedCount): array
    {
        if ($expectedCount === 1) {
            return [$content];
        }

        // Split by lines and clean up
        $lines = array_filter(array_map('trim', explode("\n", $content)));

        // Remove numbering if present
        $translations = [];
        foreach ($lines as $line) {
            $line = preg_replace('/^\d+\.\s*/', '', $line);
            if (!empty($line)) {
                $translations[] = $line;
            }
        }

        // Ensure we have the expected number of translations
        if (count($translations) !== $expectedCount) {
            throw new TranslationException(sprintf('Expected %s translations, got ', $expectedCount) . count($translations));
        }

        return $translations;
    }

    /**
     * Calculate max tokens based on input length
     */
    private function calculateMaxTokens(array $texts): int
    {
        $totalLength = array_sum(array_map('strlen', $texts));
        // Rough estimate: 1 token per 4 characters, with 2x multiplier for translation
        return min(4096, max(100, (int)($totalLength / 2)));
    }

    /**
     * Get human-readable language name
     */
    private function getLanguageName(string $code): string
    {
        $names = [
            'en' => 'English',
            'de' => 'German',
            'fr' => 'French',
            'es' => 'Spanish',
            'it' => 'Italian',
            'nl' => 'Dutch',
            'pl' => 'Polish',
            'pt' => 'Portuguese',
            'ru' => 'Russian',
            'zh' => 'Chinese',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'ar' => 'Arabic',
            'hi' => 'Hindi',
            'th' => 'Thai',
            'vi' => 'Vietnamese',
        ];

        return $names[$code] ?? $code;
    }

    /**
     * Make HTTP request to OpenAI API
     */
    private function makeRequest(array $data): array
    {
        $url = $this->baseUrl . '/chat/completions';

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey,
                    'User-Agent: BaseAPI/1.0',
                ],
                'content' => json_encode($data),
                'timeout' => 60,
            ],
        ]);

        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            throw new TranslationException('Failed to connect to OpenAI API');
        }

        // Check HTTP response code
        $statusLine = $http_response_header[0] ?? '';
        if (!preg_match('/HTTP\/\d\.\d\s+(\d+)/', $statusLine, $matches)) {
            throw new TranslationException('Invalid HTTP response from OpenAI API');
        }

        $statusCode = (int)$matches[1];
        if ($statusCode !== 200) {
            $error = json_decode($response, true);
            $message = $error['error']['message'] ?? sprintf('OpenAI API error (HTTP %s)', $statusCode);
            throw new TranslationException($message);
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new TranslationException('Invalid JSON response from OpenAI API');
        }

        return $decoded;
    }
}
