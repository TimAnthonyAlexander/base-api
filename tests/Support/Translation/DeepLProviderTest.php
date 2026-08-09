<?php

namespace BaseApi\Tests\Support\Translation;

use ReflectionClass;
use PHPUnit\Framework\TestCase;
use BaseApi\Support\Translation\DeepLProvider;

class DeepLProviderTest extends TestCase
{
    private function makeProvider(array $config): DeepLProvider
    {
        return new DeepLProvider($config);
    }

    private function resolveGlossaryId(DeepLProvider $provider, string $targetLanguage, string $sourceLanguage, array $hints = []): ?string
    {
        $reflection = new ReflectionClass($provider);
        $method = $reflection->getMethod('resolveGlossaryId');
        $method->setAccessible(true);

        return $method->invoke($provider, $targetLanguage, $sourceLanguage, $hints);
    }

    private function baseUrlOf(DeepLProvider $provider): string
    {
        $reflection = new ReflectionClass($provider);
        $property = $reflection->getProperty('baseUrl');
        $property->setAccessible(true);

        return $property->getValue($provider);
    }

    public function testGlossaryIdAttachedWhenConfiguredForTargetLanguage(): void
    {
        $provider = $this->makeProvider([
            'api_key' => 'test-key:fx',
            'glossaries' => ['en' => 'glossary-en-123'],
        ]);

        $result = $this->resolveGlossaryId($provider, 'en', 'de');

        $this->assertSame('glossary-en-123', $result);
    }

    public function testGlossaryIdIsNullWhenNoneConfiguredForTargetLanguage(): void
    {
        $provider = $this->makeProvider([
            'api_key' => 'test-key:fx',
            'glossaries' => ['fr' => 'glossary-fr-999'],
        ]);

        $result = $this->resolveGlossaryId($provider, 'en', 'de');

        $this->assertNull($result);
    }

    public function testGlossaryIdNotAttachedWhenSourceLanguageIsAuto(): void
    {
        $provider = $this->makeProvider([
            'api_key' => 'test-key:fx',
            'glossaries' => ['en' => 'glossary-en-123'],
        ]);

        $result = $this->resolveGlossaryId($provider, 'en', 'auto');

        $this->assertNull($result);
    }

    public function testHintsGlossaryIdOverridesConfiguredMap(): void
    {
        $provider = $this->makeProvider([
            'api_key' => 'test-key:fx',
            'glossaries' => ['en' => 'glossary-from-config'],
        ]);

        $result = $this->resolveGlossaryId($provider, 'en', 'de', ['glossary_id' => 'glossary-from-hint']);

        $this->assertSame('glossary-from-hint', $result);
    }

    public function testHintsGlossaryIdIsAlsoSkippedWhenSourceIsAuto(): void
    {
        $provider = $this->makeProvider([
            'api_key' => 'test-key:fx',
        ]);

        $result = $this->resolveGlossaryId($provider, 'en', 'auto', ['glossary_id' => 'glossary-from-hint']);

        $this->assertNull($result);
    }

    public function testDefaultsToEmptyGlossaryMapWhenNotConfigured(): void
    {
        $provider = $this->makeProvider([
            'api_key' => 'test-key:fx',
        ]);

        $result = $this->resolveGlossaryId($provider, 'en', 'de');

        $this->assertNull($result);
    }

    public function testSingleGlossaryIdFallbackUsedWhenNoPerTargetMapEntry(): void
    {
        // The realistic Free-tier setup: one multilingual glossary id covering
        // every target language, no per-target overrides configured.
        $provider = $this->makeProvider([
            'api_key' => 'test-key:fx',
            'glossary_id' => 'account-wide-glossary',
        ]);

        $this->assertSame('account-wide-glossary', $this->resolveGlossaryId($provider, 'en', 'de'));
        $this->assertSame('account-wide-glossary', $this->resolveGlossaryId($provider, 'fr', 'de'));
    }

    public function testPerTargetMapEntryWinsOverSingleGlossaryId(): void
    {
        $provider = $this->makeProvider([
            'api_key' => 'test-key:fx',
            'glossary_id' => 'account-wide-glossary',
            'glossaries' => ['en' => 'glossary-en-only'],
        ]);

        // 'en' has a specific override in the map
        $this->assertSame('glossary-en-only', $this->resolveGlossaryId($provider, 'en', 'de'));
        // 'fr' isn't in the map, so it falls back to the single glossary_id
        $this->assertSame('account-wide-glossary', $this->resolveGlossaryId($provider, 'fr', 'de'));
    }

    public function testHintsWinsOverBothMapAndSingleGlossaryId(): void
    {
        $provider = $this->makeProvider([
            'api_key' => 'test-key:fx',
            'glossary_id' => 'account-wide-glossary',
            'glossaries' => ['en' => 'glossary-en-only'],
        ]);

        $result = $this->resolveGlossaryId($provider, 'en', 'de', ['glossary_id' => 'glossary-from-hint']);

        $this->assertSame('glossary-from-hint', $result);
    }

    public function testSingleGlossaryIdSkippedWhenSourceIsAuto(): void
    {
        $provider = $this->makeProvider([
            'api_key' => 'test-key:fx',
            'glossary_id' => 'account-wide-glossary',
        ]);

        $result = $this->resolveGlossaryId($provider, 'en', 'auto');

        $this->assertNull($result);
    }

    public function testBaseUrlDerivesFreeTierFromKeySuffix(): void
    {
        $provider = $this->makeProvider(['api_key' => 'abc123:fx']);

        $this->assertSame('https://api-free.deepl.com/v2', $this->baseUrlOf($provider));
    }

    public function testBaseUrlDerivesProTierWhenKeyHasNoFreeSuffix(): void
    {
        $provider = $this->makeProvider(['api_key' => 'abc123']);

        $this->assertSame('https://api.deepl.com/v2', $this->baseUrlOf($provider));
    }

    public function testBaseUrlConfigOverrideWins(): void
    {
        // base_url is a host override (no version segment) — the provider
        // appends its own "/v2".
        $provider = $this->makeProvider([
            'api_key' => 'abc123:fx',
            'base_url' => 'https://custom.example.com/',
        ]);

        $this->assertSame('https://custom.example.com/v2', $this->baseUrlOf($provider));
    }
}
