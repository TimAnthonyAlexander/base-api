<?php

namespace BaseApi\Support\Translation\Concerns;

use BaseApi\Support\Translation\TranslationException;

/**
 * Shared DeepL API plumbing: base-URL derivation, auth header and a plain
 * HTTP request/error-handling helper. Used by DeepLProvider (which layers
 * its own retry/keep-alive logic on top for the high-volume /translate
 * endpoint) and DeepLGlossaryManager (low-volume glossary CRUD, no retries
 * needed).
 */
trait DeepLHttpClient
{
    /**
     * Resolve the DeepL API host — no version segment, e.g.
     * "https://api-free.deepl.com". Callers append their own "/v2" or "/v3"
     * plus path, since a single account (and therefore a single derived
     * host) serves both the v2 /translate endpoint and the v3 glossary
     * endpoints. An explicit `base_url` config entry always wins; otherwise
     * the host is derived from the key shape (free-tier keys end in ":fx",
     * paid keys don't).
     */
    private function resolveDeepLHost(string $apiKey, array $config): string
    {
        if (!empty($config['base_url'])) {
            return rtrim((string) $config['base_url'], '/');
        }

        return str_ends_with($apiKey, ':fx')
            ? 'https://api-free.deepl.com'
            : 'https://api.deepl.com';
    }

    /**
     * Execute a request against the DeepL API and return the raw response body.
     *
     * @param array<string, mixed>|null $jsonBody
     * @param string[] $extraHeaders
     */
    private function executeDeepLRequest(
        string $method,
        string $url,
        string $apiKey,
        ?array $jsonBody = null,
        array $extraHeaders = [],
    ): string {
        $headers = array_merge([
            'Authorization: DeepL-Auth-Key ' . $apiKey,
            'User-Agent: BaseAPI/1.0',
        ], $extraHeaders);

        $curl = curl_init();

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ];

        if ($jsonBody !== null) {
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_POSTFIELDS] = json_encode($jsonBody);
        }

        $options[CURLOPT_HTTPHEADER] = $headers;

        curl_setopt_array($curl, $options);

        $body = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($body === false) {
            throw new TranslationException('cURL error: ' . $curlError);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $decoded = json_decode((string) $body, true);
            $message = is_array($decoded) ? ($decoded['message'] ?? null) : null;
            $message ??= sprintf('DeepL API error (HTTP %s)', $httpCode);
            throw new TranslationException($message);
        }

        return (string) $body;
    }

    /**
     * Decode a JSON response body, raising a TranslationException on garbage input.
     */
    private function decodeDeepLJson(string $body): array
    {
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new TranslationException('Invalid JSON response from DeepL API');
        }

        return $decoded;
    }
}
