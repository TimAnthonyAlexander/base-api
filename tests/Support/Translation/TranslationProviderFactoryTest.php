<?php

namespace BaseApi\Tests\Support\Translation;

use Override;
use PHPUnit\Framework\TestCase;
use BaseApi\Support\Translation\TranslationException;
use BaseApi\Support\Translation\TranslationProvider;
use BaseApi\Support\Translation\TranslationProviderFactory;

class FakeCustomTranslationProvider implements TranslationProvider
{
    #[Override]
    public function translate(string $text, string $from, string $to, array $hints = []): string
    {
        return $text;
    }

    #[Override]
    public function translateBatch(array $texts, string $from, string $to, array $hints = []): array
    {
        return $texts;
    }

    #[Override]
    public function supportsLanguagePair(string $from, string $to): bool
    {
        return true;
    }

    #[Override]
    public function getSupportedLanguages(): array
    {
        return ['en', 'de'];
    }
}

class NotATranslationProvider
{
}

class TranslationProviderFactoryTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        TranslationProviderFactory::reset();
    }

    #[Override]
    protected function tearDown(): void
    {
        TranslationProviderFactory::reset();
        parent::tearDown();
    }

    public function testResolvesFullyQualifiedClassNameImplementingTranslationProvider(): void
    {
        $provider = TranslationProviderFactory::create(FakeCustomTranslationProvider::class);

        $this->assertInstanceOf(FakeCustomTranslationProvider::class, $provider);
    }

    public function testThrowsForClassThatDoesNotImplementTranslationProvider(): void
    {
        $this->expectException(TranslationException::class);

        TranslationProviderFactory::create(NotATranslationProvider::class);
    }

    public function testThrowsForUnknownNonClassProviderString(): void
    {
        $this->expectException(TranslationException::class);

        TranslationProviderFactory::create('not-a-real-provider');
    }

    public function testReturnsNullWhenProviderIsEmpty(): void
    {
        $this->assertNull(TranslationProviderFactory::create(''));
    }
}
