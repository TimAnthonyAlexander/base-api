<?php

namespace BaseApi\Support\Translation;

use BaseApi\App;
use BaseApi\Support\Translation\Concerns\DeepLHttpClient;

/**
 * Wraps the DeepL v3 multilingual glossary REST endpoints, so a host app can
 * create/inspect glossaries (e.g. to stop "Klemmbaustein" from translating
 * as "terminal block") without hand-rolling curl requests.
 *
 * DeepL Free allows exactly ONE glossary per account (a second `create()`
 * returns HTTP 456 "Too many glossaries"), so a per-language-pair v2
 * glossary stops working the moment more than one target language is
 * needed. The v3 API is the fix: one glossary holds many "dictionaries"
 * (each its own source_lang/target_lang pair + entries), and /v2/translate
 * accepts that single glossary_id and picks the matching dictionary from
 * the request's target_lang automatically. `replace()` is the entry point
 * a Free-tier account should use — it upserts the existing glossary's
 * dictionaries in place instead of trying to create a second glossary.
 *
 * This class does not translate anything itself — see DeepLProvider, which
 * reads a `glossary_id` (single, account-wide) and/or a per-target
 * `glossaries` map from config and attaches the right one to /translate
 * requests.
 */
class DeepLGlossaryManager
{
    use DeepLHttpClient;

    private readonly string $apiKey;

    private readonly string $host;

    public function __construct(array $config = [])
    {
        // Load the complete i18n config
        $configPath = App::basePath('config/i18n.php');
        $i18nConfig = file_exists($configPath) ? require $configPath : [];
        $defaultConfig = $i18nConfig['provider_config']['deepl'] ?? [];
        $config = array_merge($defaultConfig, $config);

        $this->apiKey = $config['api_key'] ?? '';

        if (empty($this->apiKey)) {
            throw new TranslationException('DeepL API key is required');
        }

        $this->host = $this->resolveDeepLHost($this->apiKey, $config);
    }

    /**
     * Create a new multilingual glossary from scratch.
     *
     * @param array<int, array{source_lang: string, target_lang: string, entries: array<string, string>}> $dictionaries
     *   Each dictionary's `entries` is a source term => target term map.
     */
    public function create(string $name, array $dictionaries): array
    {
        $payload = [
            'name' => $name,
            'dictionaries' => array_map($this->serializeDictionary(...), $dictionaries),
        ];

        $body = $this->executeDeepLRequest('POST', $this->host . '/v3/glossaries', $this->apiKey, $payload);

        return $this->decodeDeepLJson($body);
    }

    /**
     * List all glossaries for the account.
     */
    public function list(): array
    {
        $body = $this->executeDeepLRequest('GET', $this->host . '/v3/glossaries', $this->apiKey);

        return $this->decodeDeepLJson($body);
    }

    /**
     * Fetch metadata for a single glossary (including its dictionaries).
     */
    public function get(string $glossaryId): array
    {
        $body = $this->executeDeepLRequest('GET', $this->host . '/v3/glossaries/' . rawurlencode($glossaryId), $this->apiKey);

        return $this->decodeDeepLJson($body);
    }

    /**
     * Fetch the entries of one dictionary (source_lang/target_lang pair)
     * inside a multilingual glossary, as raw TSV text.
     */
    public function entries(string $glossaryId, string $sourceLang, string $targetLang): string
    {
        $url = $this->host . '/v3/glossaries/' . rawurlencode($glossaryId) . '/entries'
            . '?source_lang=' . rawurlencode(strtoupper($sourceLang))
            . '&target_lang=' . rawurlencode(strtoupper($targetLang));

        return $this->executeDeepLRequest('GET', $url, $this->apiKey, null, ['Accept: text/tab-separated-values']);
    }

    /**
     * Upsert one dictionary inside an existing glossary — creates it if the
     * source_lang/target_lang pair doesn't exist yet, replaces its entries
     * if it does — without touching the glossary's other dictionaries or
     * recreating the glossary itself.
     *
     * Note the shape: this endpoint takes ONE dictionary object directly and
     * answers to PUT. It is not the `{"dictionaries": [...]}` envelope used
     * when creating a whole glossary, and PATCH here returns HTTP 405.
     *
     * @param array<string, string> $entries
     */
    public function upsertDictionary(string $glossaryId, string $sourceLang, string $targetLang, array $entries): array
    {
        $payload = $this->serializeDictionary([
            'source_lang' => $sourceLang,
            'target_lang' => $targetLang,
            'entries' => $entries,
        ]);

        $body = $this->executeDeepLRequest(
            'PUT',
            $this->host . '/v3/glossaries/' . rawurlencode($glossaryId) . '/dictionaries',
            $this->apiKey,
            $payload,
        );

        return $this->decodeDeepLJson($body);
    }

    /**
     * Delete a glossary entirely.
     */
    public function delete(string $glossaryId): void
    {
        $this->executeDeepLRequest('DELETE', $this->host . '/v3/glossaries/' . rawurlencode($glossaryId), $this->apiKey);
    }

    /**
     * Free-tier-safe way to (re)build a glossary: find an existing glossary
     * by name and upsert its dictionaries in place; only create a new one if
     * none exists yet. Never attempts to hold two glossaries at once, which
     * the Free tier does not allow.
     *
     * @param array<int, array{source_lang: string, target_lang: string, entries: array<string, string>}> $dictionaries
     */
    public function replace(string $name, array $dictionaries): array
    {
        $existing = $this->findByName($name);

        if ($existing === null) {
            return $this->create($name, $dictionaries);
        }

        $glossaryId = $existing['glossary_id'];
        foreach ($dictionaries as $dictionary) {
            $this->upsertDictionary($glossaryId, $dictionary['source_lang'], $dictionary['target_lang'], $dictionary['entries']);
        }

        return $this->get($glossaryId);
    }

    /**
     * List the source/target language pairs glossaries currently support.
     */
    public function supportedLanguagePairs(): array
    {
        $body = $this->executeDeepLRequest('GET', $this->host . '/v2/glossary-language-pairs', $this->apiKey);

        return $this->decodeDeepLJson($body);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findByName(string $name): ?array
    {
        $glossaries = $this->list()['glossaries'] ?? [];
        foreach ($glossaries as $glossary) {
            if (($glossary['name'] ?? null) === $name) {
                return $glossary;
            }
        }

        return null;
    }

    /**
     * @param array{source_lang: string, target_lang: string, entries: array<string, string>} $dictionary
     * @return array{source_lang: string, target_lang: string, entries: string, entries_format: string}
     */
    private function serializeDictionary(array $dictionary): array
    {
        return [
            'source_lang' => strtoupper($dictionary['source_lang']),
            'target_lang' => strtoupper($dictionary['target_lang']),
            'entries' => $this->toTsv($dictionary['entries']),
            'entries_format' => 'tsv',
        ];
    }

    /**
     * Serialise a source-term => target-term map into DeepL's TSV entries format.
     *
     * @param array<string, string> $entries
     */
    private function toTsv(array $entries): string
    {
        $lines = [];
        foreach ($entries as $sourceTerm => $targetTerm) {
            $sourceTerm = (string) $sourceTerm;
            $targetTerm = (string) $targetTerm;

            if (str_contains($sourceTerm, "\t") || str_contains($sourceTerm, "\n")
                || str_contains($targetTerm, "\t") || str_contains($targetTerm, "\n")) {
                throw new TranslationException('Glossary entries must not contain tab or newline characters');
            }

            $lines[] = $sourceTerm . "\t" . $targetTerm;
        }

        return implode("\n", $lines);
    }
}
