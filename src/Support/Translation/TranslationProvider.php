<?php

namespace BaseApi\Support\Translation;

interface TranslationProvider
{
    /**
     * Translate text from one language to another
     *
     * @param string $text The text to translate
     * @param string $from Source language code (e.g., 'en')
     * @param string $to Target language code (e.g., 'de')
     * @param array $hints Optional hints for translation context
     * @return string Translated text
     * @throws TranslationException If translation fails
     */
    public function translate(string $text, string $from, string $to, array $hints = []): string;
    
    /**
     * Translate multiple texts at once for better efficiency
     *
     * @param array $texts Array of texts to translate
     * @param string $from Source language code
     * @param string $to Target language code
     * @param array $hints Optional hints for translation context
     * @return array Array of translated texts in the same order
     * @throws TranslationException If translation fails
     */
    public function translateBatch(array $texts, string $from, string $to, array $hints = []): array;
    
    /**
     * Check if the provider supports the given language pair
     *
     * @param string $from Source language code
     * @param string $to Target language code
     * @return bool True if supported, false otherwise
     */
    public function supportsLanguagePair(string $from, string $to): bool;
    
    /**
     * Get supported languages
     *
     * @return array Array of supported language codes
     */
    public function getSupportedLanguages(): array;
}
