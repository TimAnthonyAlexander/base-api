<?php

declare(strict_types=1);

namespace BaseApi\Modules;

use RecursiveIteratorIterator;
use RecursiveArrayIterator;
use Generator;
use CurlHandle;
use BaseApi\App;
use RuntimeException;

final class OpenAI
{
    private const string API_ENDPOINT = 'https://api.openai.com/v1/responses';

    private string $apiKey;

    private string $model;

    private array $options = [];

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = $apiKey ?? App::config('openai.api_key', '');
        $this->model = $model ?? App::config('openai.default_model', 'gpt-4.1-mini');

        if ($this->apiKey === '') {
            throw new RuntimeException('OpenAI API key not configured. Set OPENAI_API_KEY in .env');
        }
    }

    public function response(string|array $input, array $options = []): array
    {
        $payload = $this->normalizePayload($input, $options, false);
        return $this->request($payload);
    }

    public function stream(string|array $input, array $options = []): Generator
    {
        $payload = $this->normalizePayload($input, $options, true);

        // Log the request payload for debugging
        error_log('[OpenAI] Stream request payload: ' . json_encode($payload, JSON_PRETTY_PRINT));

        $ch = $this->buildCurlHandle($payload, true);

        $sink = fopen('php://temp', 'w+');
        if ($sink === false) {
            throw new RuntimeException('Failed to open temp stream');
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_FILE, $sink);

        $mh = curl_multi_init();
        curl_multi_add_handle($mh, $ch);

        $buffer = '';
        $readPos = 0;
        $running = 0;

        do {
            $status = curl_multi_exec($mh, $running);
            if ($status === CURLM_OK) {
                curl_multi_select($mh, 0.01);
            }

            fflush($sink);
            $chunk = stream_get_contents($sink, -1, $readPos);
            if ($chunk !== false && $chunk !== '') {
                $readPos += strlen($chunk);
                $buffer .= $chunk;

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line   = rtrim(substr($buffer, 0, $pos), "\r");
                    $buffer = substr($buffer, $pos + 1);

                    if (!str_starts_with($line, 'data: ')) {
                        continue;
                    }

                    $json = substr($line, 6);
                    if ($json === '[DONE]') {
                        continue;
                    }

                    $decoded = json_decode($json, true);
                    if (is_array($decoded)) {
                        yield $decoded;
                    }
                }

                // If connection ended and buffer still has non-SSE content (likely JSON error), drain it.
                if ($running === 0 && $buffer !== '') {
                    $line = rtrim($buffer, "\r");
                    $buffer = '';
                    if (str_starts_with($line, 'data: ')) {
                        $json = substr($line, 6);
                        if ($json !== '[DONE]') {
                            $decoded = json_decode($json, true);
                            if (is_array($decoded)) {
                                yield $decoded;
                            }
                        }
                    } else {
                        // leave it for HTTP code check below; prevents infinite loop
                    }
                }
            }
        } while ($running > 0 || $buffer !== '');

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // If error, rewind sink and capture the full error body
        $errorBody = '';
        if ($httpCode >= 400) {
            rewind($sink);
            $errorBody = stream_get_contents($sink);
        }

        curl_multi_remove_handle($mh, $ch);
        curl_multi_close($mh);
        curl_close($ch);
        fclose($sink);

        if ($httpCode >= 400) {
            // Try to parse the error JSON
            $errorData = json_decode($errorBody, true);
            $errorMessage = 'HTTP ' . $httpCode;

            if (is_array($errorData)) {
                if (isset($errorData['error']['message'])) {
                    $errorMessage = $errorData['error']['message'];
                } elseif (isset($errorData['error'])) {
                    $errorMessage = is_string($errorData['error']) ? $errorData['error'] : json_encode($errorData['error']);
                }
            }

            // Log full error details
            error_log('[OpenAI] Error ' . $httpCode . ': ' . $errorBody);

            throw new RuntimeException('OpenAI API error: ' . $errorMessage . ' (HTTP ' . $httpCode . ')');
        }
    }

    public function withTools(array $tools, string $toolChoice = 'auto'): self
    {
        $clone = clone $this;
        $clone->options['tools'] = $tools;
        $clone->options['tool_choice'] = $toolChoice;
        return $clone;
    }

    public function withJsonSchema(string $name, array $schema, bool $strict = true): self
    {
        $clone = clone $this;
        $clone->options['response_format'] = [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => $name,
                'strict' => $strict,
                'schema' => $schema,
            ],
        ];
        return $clone;
    }

    public function withReasoning(string $effort = 'medium'): self
    {
        $clone = clone $this;
        $clone->options['reasoning'] = ['effort' => $effort];
        return $clone;
    }

    public function withOptions(array $options): self
    {
        $clone = clone $this;
        $clone->options = array_merge($clone->options, $options);
        return $clone;
    }

    public function withInstructions(string $instructions): self
    {
        $clone = clone $this;
        $clone->options['instructions'] = $instructions;
        return $clone;
    }

    public function model(string $model): self
    {
        $clone = clone $this;
        $clone->model = $model;
        return $clone;
    }

    private function request(array $payload): array
    {
        $ch = $this->buildCurlHandle($payload, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error !== '' && $error !== '0') {
            throw new RuntimeException('cURL error: ' . $error);
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $message = $decoded['error']['message'] ?? 'HTTP ' . $httpCode;
            $code = $decoded['error']['code'] ?? 'unknown';
            throw new RuntimeException(sprintf('OpenAI API error [%s]: %s', $code, $message));
        }

        return $decoded ?? [];
    }

    private function buildCurlHandle(array $payload, bool $stream): CurlHandle
    {
        $ch = curl_init(self::API_ENDPOINT);

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ];
        if ($stream) {
            $headers[] = 'Accept: text/event-stream';
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => $headers,
        ]);

        return $ch;
    }

    private function normalizePayload(string|array $input, array $options, bool $stream): array
    {
        $payload = array_merge(['model' => $this->model], $this->options, $options);
        if ($stream) {
            $payload['stream'] = true;
        }

        if (is_string($input)) {
            $payload['input'] = $input;
            return $payload;
        }

        if (isset($input['messages']) && is_array($input['messages'])) {
            [$messages, $extraInstructions] = $this->convertMessages($input['messages']);
            $payload['input'] = $messages;
            if ($extraInstructions !== '') {
                $payload['instructions'] = trim(($payload['instructions'] ?? '') . "\n" . $extraInstructions);
            }

            return $payload;
        }

        if (is_array($input) && array_is_list($input) && isset($input[0]['role'])) {
            [$messages, $extraInstructions] = $this->convertMessages($input);
            $payload['input'] = $messages;
            if ($extraInstructions !== '') {
                $payload['instructions'] = trim(($payload['instructions'] ?? '') . "\n" . $extraInstructions);
            }

            return $payload;
        }

        $payload['input'] = $input;
        return $payload;
    }

    private function convertMessages(array $messages): array
    {
        $out = [];
        $system = [];

        foreach ($messages as $m) {
            $role = ($m['role'] ?? 'user');
            $content = $m['content'] ?? '';

            if ($role === 'system') {
                if (is_string($content)) {
                    $system[] = $content;
                } elseif (is_array($content)) {
                    $system[] = $this->collectText($content);
                }

                continue;
            }

            // Map role to correct content type: assistant uses output_text, user uses input_text
            $contentType = $role === 'assistant' ? 'output_text' : 'input_text';

            $parts = [];
            if (is_string($content)) {
                $parts[] = ['type' => $contentType, 'text' => $content];
            } elseif (is_array($content)) {
                if ($this->isPartsArray($content)) {
                    $parts = $this->normalizeParts($content, $contentType);
                } else {
                    $parts[] = ['type' => $contentType, 'text' => $this->collectText($content)];
                }
            }

            $out[] = [
                'role' => ($role === 'assistant') ? 'assistant' : 'user',
                'content' => $parts,
            ];
        }

        return [$out, trim(implode("\n", array_filter($system)))];
    }

    private function isPartsArray(array $content): bool
    {
        if (!array_is_list($content)) {
            return false;
        }

        $first = $content[0] ?? null;
        return is_array($first) && isset($first['type']);
    }

    private function normalizeParts(array $parts, string $contentType = 'input_text'): array
    {
        $norm = [];
        foreach ($parts as $p) {
            if (isset($p['type'])) {
                // If it's a generic 'text' type or already has input_text/output_text, normalize to the correct type
                if ($p['type'] === 'text' && isset($p['text'])) {
                    $norm[] = ['type' => $contentType, 'text' => (string)$p['text']];
                } elseif (($p['type'] === 'input_text' || $p['type'] === 'output_text') && isset($p['text'])) {
                    $norm[] = ['type' => $contentType, 'text' => (string)$p['text']];
                } else {
                    $norm[] = $p;
                }
            } else {
                $norm[] = ['type' => $contentType, 'text' => (string)$p];
            }
        }

        return $norm;
    }

    private function collectText(array $content): string
    {
        if ($this->isPartsArray($content)) {
            $texts = [];
            foreach ($content as $p) {
                $type = $p['type'] ?? '';
                if (($type === 'input_text' || $type === 'output_text' || $type === 'text') && isset($p['text'])) {
                    $texts[] = (string)$p['text'];
                }
            }

            return trim(implode("\n", $texts));
        }

        $flat = [];
        $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($content));
        foreach ($it as $v) {
            if (is_string($v)) {
                $flat[] = $v;
            }
        }

        return trim(implode(' ', $flat));
    }

    public static function extractText(array $response): string
    {
        foreach ($response['output'] ?? [] as $item) {
            if (($item['type'] ?? null) === 'output_text') {
                return $item['text'] ?? $item['content'] ?? '';
            }
        }

        return '';
    }

    public static function extractToolCalls(array $response): array
    {
        $tools = [];
        foreach ($response['output'] ?? [] as $item) {
            if (($item['type'] ?? null) === 'tool_call') {
                $tools[] = [
                    'id' => $item['call_id'] ?? '',
                    'name' => $item['tool_name'] ?? $item['name'] ?? '',
                    'arguments' => $item['arguments'] ?? [],
                ];
            }
        }

        return $tools;
    }
}
