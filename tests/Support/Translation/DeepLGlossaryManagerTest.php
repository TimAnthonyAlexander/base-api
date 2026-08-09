<?php

namespace BaseApi\Tests\Support\Translation;

use ReflectionClass;
use PHPUnit\Framework\TestCase;
use BaseApi\Support\Translation\TranslationException;
use BaseApi\Support\Translation\DeepLGlossaryManager;

class DeepLGlossaryManagerTest extends TestCase
{
    private function toTsv(DeepLGlossaryManager $manager, array $entries): string
    {
        $reflection = new ReflectionClass($manager);
        $method = $reflection->getMethod('toTsv');
        $method->setAccessible(true);

        return $method->invoke($manager, $entries);
    }

    private function serializeDictionary(DeepLGlossaryManager $manager, array $dictionary): array
    {
        $reflection = new ReflectionClass($manager);
        $method = $reflection->getMethod('serializeDictionary');
        $method->setAccessible(true);

        return $method->invoke($manager, $dictionary);
    }

    private function hostOf(DeepLGlossaryManager $manager): string
    {
        $reflection = new ReflectionClass($manager);
        $property = $reflection->getProperty('host');
        $property->setAccessible(true);

        return $property->getValue($manager);
    }

    public function testConstructorThrowsWithoutApiKey(): void
    {
        $this->expectException(TranslationException::class);

        new DeepLGlossaryManager([]);
    }

    public function testTsvSerialisesSourceTargetPairsTabSeparated(): void
    {
        $manager = new DeepLGlossaryManager(['api_key' => 'test-key:fx']);

        $tsv = $this->toTsv($manager, [
            'Klemmbaustein' => 'building brick',
            'Klemmbausteine' => 'building bricks',
        ]);

        $this->assertSame("Klemmbaustein\tbuilding brick\nKlemmbausteine\tbuilding bricks", $tsv);
    }

    public function testTsvRejectsTabCharacterInSourceTerm(): void
    {
        $manager = new DeepLGlossaryManager(['api_key' => 'test-key:fx']);

        $this->expectException(TranslationException::class);

        $this->toTsv($manager, ["bad\tterm" => 'value']);
    }

    public function testTsvRejectsNewlineCharacterInTargetTerm(): void
    {
        $manager = new DeepLGlossaryManager(['api_key' => 'test-key:fx']);

        $this->expectException(TranslationException::class);

        $this->toTsv($manager, ['term' => "bad\nvalue"]);
    }

    public function testSerializeDictionaryProducesV3Shape(): void
    {
        $manager = new DeepLGlossaryManager(['api_key' => 'test-key:fx']);

        $result = $this->serializeDictionary($manager, [
            'source_lang' => 'de',
            'target_lang' => 'en',
            'entries' => ['Klemmbaustein' => 'building brick'],
        ]);

        $this->assertSame([
            'source_lang' => 'DE',
            'target_lang' => 'EN',
            'entries' => "Klemmbaustein\tbuilding brick",
            'entries_format' => 'tsv',
        ], $result);
    }

    public function testHostDerivationMatchesProvider(): void
    {
        $manager = new DeepLGlossaryManager(['api_key' => 'abc123']);

        $this->assertSame('https://api.deepl.com', $this->hostOf($manager));
    }

    public function testHostDerivesFreeTierFromKeySuffix(): void
    {
        $manager = new DeepLGlossaryManager(['api_key' => 'abc123:fx']);

        $this->assertSame('https://api-free.deepl.com', $this->hostOf($manager));
    }
}
