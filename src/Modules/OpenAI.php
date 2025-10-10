<?php

declare(strict_types=1);

namespace BaseApi\Modules;

use Generator;
use CurlHandle;
use BaseApi\App;
use RuntimeException;

/**
 * OpenAI Responses API Client
 * 
 * A minimal wrapper for OpenAI's Responses API endpoint.
 * Supports text responses, streaming, tool calling, and structured JSON output.
 */
final class OpenAI
{
    private const string API_ENDPOINT = 'https://api.openai.com/v1/responses';

    private string $apiKey;

    private string $model;

    private array $options = [];

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = $apiKey ?? App::config('openai.api_key', '');
        $this->model = $model ?? App::config('openai.default_model', 'gpt-5.1-mini');

        if (empty($this->apiKey)) {
            throw new RuntimeException('OpenAI API key not configured. Set OPENAI_API_KEY in .env');
        }
    }

    /**
     * Send a simple text prompt and get a response
     */
    public function response(string $input, array $options = []): array
    {
        $payload = array_merge([
            'model' => $this->model,
            'input' => $input,
        ], $this->options, $options);

        return $this->request($payload);
    }

    /**
     * Stream a response with Server-Sent Events
     * Returns a generator that yields chunks as they arrive
     */
    public function stream(string $input, array $options = []): Generator
    {
        $payload = array_merge([
            'model'  => $this->model,
            'input'  => $input,
            'stream' => true,
        ], $this->options, $options);

        $ch = $this->buildCurlHandle($payload);

        // Collect body into a seekable buffer we can read incrementally
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
                curl_multi_select($mh, 0.1);
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
            }
        } while ($running > 0 || $buffer !== '');

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_multi_remove_handle($mh, $ch);
        curl_multi_close($mh);
        curl_close($ch);
        fclose($sink);

        if ($httpCode >= 400) {
            throw new RuntimeException('OpenAI API error: HTTP ' . $httpCode);
        }
    }

    /**
     * Enable function calling with tools
     */
    public function withTools(array $tools, string $toolChoice = 'auto'): self
    {
        $clone = clone $this;
        $clone->options['tools'] = $tools;
        $clone->options['tool_choice'] = $toolChoice;
        return $clone;
    }

    /**
     * Enforce structured JSON output with a schema
     */
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

    /**
     * Enable reasoning mode (for o-series models)
     */
    public function withReasoning(string $effort = 'medium'): self
    {
        $clone = clone $this;
        $clone->options['reasoning'] = ['effort' => $effort];
        return $clone;
    }

    /**
     * Set model-specific options (temperature, max_output_tokens, etc.)
     */
    public function withOptions(array $options): self
    {
        $clone = clone $this;
        $clone->options = array_merge($clone->options, $options);
        return $clone;
    }

    /**
     * Change the model for this request
     */
    public function model(string $model): self
    {
        $clone = clone $this;
        $clone->model = $model;
        return $clone;
    }

    /**
     * Execute the HTTP request to OpenAI
     */
    private function request(array $payload): array
    {
        $ch = $this->buildCurlHandle($payload);

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

    /**
     * Build a configured cURL handle
     */
    private function buildCurlHandle(array $payload): CurlHandle
    {
        $ch = curl_init(self::API_ENDPOINT);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
        ]);

        return $ch;
    }

    /**
     * Extract the text content from a response
     */
    public static function extractText(array $response): string
    {
        foreach ($response['output'] ?? [] as $item) {
            if ($item['type'] === 'output_text') {
                return $item['text'] ?? $item['content'] ?? '';
            }
        }

        return '';
    }

    /**
     * Extract tool calls from a response
     */
    public static function extractToolCalls(array $response): array
    {
        $tools = [];

        foreach ($response['output'] ?? [] as $item) {
            if ($item['type'] === 'tool_call') {
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
