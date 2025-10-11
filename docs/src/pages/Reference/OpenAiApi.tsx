
import { Box, Typography, Alert } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

export default function OpenAiApi() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                OpenAI API Reference
            </Typography>
            <Typography variant="h5" color="text.secondary" paragraph>
                Complete API reference for OpenAI Responses API integration
            </Typography>

            <Typography>
                BaseAPI includes a clean, minimal wrapper for OpenAI's Responses API that supports text generation, 
                streaming, function calling (tools), and structured JSON output. The implementation follows KISS principles 
                while providing all essential features for AI-powered applications.
            </Typography>

            <Alert severity="info" sx={{ my: 3 }}>
                The OpenAI module uses the <strong>Responses API</strong> endpoint (not the legacy Chat Completions API). 
                Configure your API key in <code>.env</code> using <code>OPENAI_API_KEY</code>.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Quick Start
            </Typography>

            <Typography>
                First, add your OpenAI API key to your <code>.env</code> file:
            </Typography>

            <CodeBlock language="bash" code={`# Get your key at https://platform.openai.com/api-keys
OPENAI_API_KEY=sk-proj-...
OPENAI_DEFAULT_MODEL=gpt-4.1-mini`} />

            <Typography sx={{ mt: 2 }}>
                Then use the OpenAI class in your application:
            </Typography>

            <CodeBlock language="php" code={`<?php

use BaseApi\\Modules\\OpenAI;

// Initialize with default configuration
$ai = new OpenAI();

// Send a simple text request
$response = $ai->response('Write a haiku about recursion.');

// Extract the generated text
$text = OpenAI::extractText($response);
// "function in function…
// mirrors calling into mirrors—
// ends where it began."`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Basic Text Responses
            </Typography>

            <CodeBlock language="php" code={`<?php

use BaseApi\\Modules\\OpenAI;

$ai = new OpenAI();

// Simple text generation
$response = $ai->response('Explain quantum computing in one sentence.');
$text = OpenAI::extractText($response);

// With custom options
$response = $ai
    ->withOptions([
        'temperature' => 0.7,
        'max_output_tokens' => 500,
    ])
    ->response('Write a creative product description.');

// Use a different model
$response = $ai
    ->model('gpt-4.1')
    ->response('Analyze this complex problem...');

// Full response structure
$response = $ai->response('Hello!');
// Returns:
// [
//   'id' => 'resp_abc123',
//   'model' => 'gpt-4.1-mini',
//   'output' => [
//     ['type' => 'output_text', 'text' => '...'],
//   ],
//   'usage' => [
//     'input_tokens' => 10,
//     'output_tokens' => 28,
//     'total_tokens' => 38,
//   ],
// ]`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Streaming Responses
            </Typography>

            <Typography>
                Stream responses in real-time using Server-Sent Events. The <code>stream()</code> method returns a 
                Generator that yields chunks as they arrive:
            </Typography>

            <CodeBlock language="php" code={`<?php

use BaseApi\\Modules\\OpenAI;
use BaseApi\\Http\\StreamedResponse;

$ai = new OpenAI();

// Stream a response
foreach ($ai->stream('Tell me a story about a robot.') as $chunk) {
    // Process each chunk as it arrives
    if (isset($chunk['delta'])) {
        echo $chunk['delta']; // Output: "Once", " upon", " a", " time", ...
        flush();
    }
}

// Use in a controller to stream to the client
class StreamController extends Controller
{
    public string $prompt = '';
    
    public function get(): StreamedResponse
    {
        $ai = new OpenAI();
        
        return StreamedResponse::sse(function() use ($ai) {
            foreach ($ai->stream($this->prompt) as $chunk) {
                if (isset($chunk['delta'])) {
                    echo "data: " . json_encode($chunk) . "\\n\\n";
                    flush();
                }
            }
        });
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Function Calling (Tools)
            </Typography>

            <Typography>
                Enable function calling to let the AI invoke your application's functions. Define tools with JSON Schema 
                parameters, and the model will return structured tool calls:
            </Typography>

            <CodeBlock language="php" code={`<?php

use BaseApi\\Modules\\OpenAI;

$ai = new OpenAI();

// Define available tools
$tools = [
    [
        'type' => 'function',
        'name' => 'get_weather',
        'description' => 'Get current weather by city name',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'city' => ['type' => 'string'],
                'units' => [
                    'type' => 'string',
                    'enum' => ['metric', 'imperial'],
                ],
            ],
            'required' => ['city'],
        ],
    ],
    [
        'type' => 'function',
        'name' => 'search_database',
        'description' => 'Search the product database',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'query' => ['type' => 'string'],
                'limit' => ['type' => 'integer'],
            ],
            'required' => ['query'],
        ],
    ],
];

// Send request with tools enabled
$response = $ai
    ->withTools($tools, 'auto') // 'auto', 'required', or 'none'
    ->response('What is the weather in Munich?');

// Extract tool calls
$toolCalls = OpenAI::extractToolCalls($response);
// [
//   [
//     'id' => 'call_123',
//     'name' => 'get_weather',
//     'arguments' => ['city' => 'Munich', 'units' => 'metric'],
//   ],
// ]

// Execute the tool and send result back
foreach ($toolCalls as $toolCall) {
    if ($toolCall['name'] === 'get_weather') {
        $weatherData = getWeather($toolCall['arguments']['city']);
        
        // Send tool result in next request
        $followUp = $ai->response(
            'The weather data is: ' . json_encode($weatherData)
        );
    }
}`} />

            <Callout type="tip">
                <Typography>
                    <strong>Tool Choice Options:</strong> Use <code>'auto'</code> to let the model decide, 
                    <code>'required'</code> to force a tool call, or <code>'none'</code> to prevent tool calling.
                </Typography>
            </Callout>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Structured JSON Output
            </Typography>

            <Typography>
                Enforce strict JSON output validated against a schema. Perfect for parsing, data extraction, 
                and structured data generation:
            </Typography>

            <CodeBlock language="php" code={`<?php

use BaseApi\\Modules\\OpenAI;

$ai = new OpenAI();

// Define JSON schema
$schema = [
    'type' => 'object',
    'properties' => [
        'title' => ['type' => 'string'],
        'sentiment' => [
            'type' => 'string',
            'enum' => ['positive', 'neutral', 'negative'],
        ],
        'score' => [
            'type' => 'number',
            'minimum' => 0,
            'maximum' => 1,
        ],
        'tags' => [
            'type' => 'array',
            'items' => ['type' => 'string'],
        ],
    ],
    'required' => ['title', 'sentiment', 'score'],
    'additionalProperties' => false,
];

// Request structured output
$response = $ai
    ->withJsonSchema('SentimentExtraction', $schema, strict: true)
    ->response('Analyze: "Loved the battery life, hated the camera."');

$text = OpenAI::extractText($response);
$data = json_decode($text, true);
// [
//   'title' => 'Battery vs. Camera',
//   'sentiment' => 'mixed',
//   'score' => 0.62,
//   'tags' => ['battery', 'camera', 'product-review'],
// ]`} />

            <Callout type="info">
                <Typography>
                    <strong>Guaranteed Valid JSON:</strong> When <code>strict: true</code> is used, the model will 
                    always return valid JSON matching your schema. No parsing errors!
                </Typography>
            </Callout>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Reasoning Mode (o-series models)
            </Typography>

            <Typography>
                For complex problem-solving, use reasoning models with configurable effort levels:
            </Typography>

            <CodeBlock language="php" code={`<?php

use BaseApi\\Modules\\OpenAI;

$ai = new OpenAI();

// Enable reasoning mode with o-series models
$response = $ai
    ->model('o4-mini') // or 'o1', 'o3'
    ->withReasoning('medium') // 'low', 'medium', 'high'
    ->withOptions(['max_output_tokens' => 500])
    ->response('Explain the difference between A* and Dijkstra algorithm.');

$text = OpenAI::extractText($response);`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Method Chaining
            </Typography>

            <Typography>
                All configuration methods return a cloned instance, allowing fluent method chaining:
            </Typography>

            <CodeBlock language="php" code={`<?php

use BaseApi\\Modules\\OpenAI;

$ai = new OpenAI();

// Chain multiple configuration methods
$response = $ai
    ->model('gpt-5')
    ->withOptions([
        'temperature' => 0.3,
        'max_output_tokens' => 1500,
        'top_p' => 0.9,
    ])
    ->withJsonSchema('ProductReview', $schema)
    ->response('Generate a product review...');`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Complete API Reference
            </Typography>

            <Typography variant="h6" gutterBottom sx={{ mt: 3, fontWeight: 600 }}>
                Constructor
            </Typography>

            <CodeBlock language="php" code={`// Use default configuration from .env
$ai = new OpenAI();

// Override API key and model
$ai = new OpenAI(
    apiKey: 'sk-proj-...',
    model: 'gpt-4.1'
);`} />

            <Typography variant="h6" gutterBottom sx={{ mt: 3, fontWeight: 600 }}>
                Instance Methods
            </Typography>

            <CodeBlock language="php" code={`// Send a text request
response(string $input, array $options = []): array

// Stream a response
stream(string $input, array $options = []): \\Generator

// Configure tools (function calling)
withTools(array $tools, string $toolChoice = 'auto'): self

// Enforce JSON schema
withJsonSchema(string $name, array $schema, bool $strict = true): self

// Enable reasoning mode
withReasoning(string $effort = 'medium'): self

// Set custom options (temperature, max_output_tokens, etc.)
withOptions(array $options): self

// Change the model
model(string $model): self`} />

            <Typography variant="h6" gutterBottom sx={{ mt: 3, fontWeight: 600 }}>
                Static Helper Methods
            </Typography>

            <CodeBlock language="php" code={`// Extract text content from response
OpenAI::extractText(array $response): string

// Extract tool calls from response
OpenAI::extractToolCalls(array $response): array`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Practical Examples
            </Typography>

            <Typography variant="h6" gutterBottom sx={{ mt: 3, fontWeight: 600 }}>
                Content Summarization
            </Typography>

            <CodeBlock language="php" code={`<?php

class SummaryController extends Controller
{
    public string $content = '';
    
    public function post(): JsonResponse
    {
        $this->validate(['content' => 'required|string']);
        
        $ai = new OpenAI();
        
        $response = $ai
            ->withOptions(['temperature' => 0.3])
            ->withJsonSchema('Summary', [
                'type' => 'object',
                'properties' => [
                    'summary' => ['type' => 'string'],
                    'key_points' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'word_count' => ['type' => 'integer'],
                ],
                'required' => ['summary', 'key_points'],
            ])
            ->response("Summarize this content: {$this->content}");
        
        $data = json_decode(OpenAI::extractText($response), true);
        
        return JsonResponse::success($data);
    }
}`} />

            <Typography variant="h6" gutterBottom sx={{ mt: 3, fontWeight: 600 }}>
                AI-Powered Search
            </Typography>

            <CodeBlock language="php" code={`<?php

class SearchController extends Controller
{
    public string $query = '';
    
    private function searchDatabase(string $query): array
    {
        return Product::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->get();
    }
    
    public function get(): JsonResponse
    {
        $ai = new OpenAI();
        
        $tools = [
            [
                'type' => 'function',
                'name' => 'search_database',
                'description' => 'Search products by name or description',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string'],
                    ],
                    'required' => ['query'],
                ],
            ],
        ];
        
        $response = $ai
            ->withTools($tools)
            ->response($this->query);
        
        $toolCalls = OpenAI::extractToolCalls($response);
        
        foreach ($toolCalls as $call) {
            if ($call['name'] === 'search_database') {
                $results = $this->searchDatabase($call['arguments']['query']);
                return JsonResponse::success($results);
            }
        }
        
        return JsonResponse::success(['message' => OpenAI::extractText($response)]);
    }
}`} />

            <Typography variant="h6" gutterBottom sx={{ mt: 3, fontWeight: 600 }}>
                Translation Service
            </Typography>

            <CodeBlock language="php" code={`<?php

class TranslationService
{
    private OpenAI $ai;
    
    public function __construct()
    {
        $this->ai = new OpenAI();
    }
    
    public function translate(string $text, string $targetLang): string
    {
        $response = $this->ai
            ->withOptions(['temperature' => 0.3])
            ->response("Translate to {$targetLang}: {$text}");
        
        return OpenAI::extractText($response);
    }
    
    public function translateBatch(array $texts, string $targetLang): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'translations' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => ['translations'],
        ];
        
        $response = $this->ai
            ->withJsonSchema('BatchTranslation', $schema)
            ->response(
                "Translate these to {$targetLang}: " . 
                json_encode($texts)
            );
        
        $data = json_decode(OpenAI::extractText($response), true);
        return $data['translations'] ?? [];
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Error Handling
            </Typography>

            <CodeBlock language="php" code={`<?php

use BaseApi\\Modules\\OpenAI;
use RuntimeException;

try {
    $ai = new OpenAI();
    $response = $ai->response('Generate content...');
    
} catch (RuntimeException $e) {
    // Handle API errors
    if (str_contains($e->getMessage(), 'rate_limit_exceeded')) {
        // Handle rate limiting
        return JsonResponse::error('Too many requests', 429);
    }
    
    if (str_contains($e->getMessage(), 'context_length_exceeded')) {
        // Handle token limit
        return JsonResponse::error('Input too long', 400);
    }
    
    // Log other errors
    error_log($e->getMessage());
    return JsonResponse::error('AI service unavailable', 503);
}`} />

            <Callout type="warning">
                <Typography>
                    <strong>API Key Security:</strong> Never expose your OpenAI API key in client-side code or 
                    version control. Always keep it in <code>.env</code> and add <code>.env</code> to your 
                    <code>.gitignore</code>.
                </Typography>
            </Callout>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Configuration Options
            </Typography>

            <Typography>
                Available configuration options in <code>.env</code>:
            </Typography>

            <CodeBlock language="bash" code={`# Required: Your OpenAI API key
OPENAI_API_KEY=sk-proj-...

# Optional: Default model
OPENAI_DEFAULT_MODEL=gpt-4.1-mini

# Optional: Default temperature (0.0 - 2.0)
OPENAI_TEMPERATURE=1.0

# Optional: Default max tokens
OPENAI_MAX_TOKENS=1000

# Optional: Request timeout in seconds
OPENAI_TIMEOUT=30`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Best Practices
            </Typography>

            <Box component="ul" sx={{ pl: 3 }}>
                <Typography component="li" paragraph>
                    <strong>Use structured output:</strong> Always use <code>withJsonSchema()</code> when you need 
                    parseable results. This guarantees valid JSON and eliminates parsing errors.
                </Typography>
                <Typography component="li" paragraph>
                    <strong>Lower temperature for consistency:</strong> Use <code>temperature: 0.3</code> or lower 
                    for deterministic tasks like extraction, summarization, or translation.
                </Typography>
                <Typography component="li" paragraph>
                    <strong>Stream long responses:</strong> Use <code>stream()</code> for better UX when generating 
                    long-form content. Users see progress immediately.
                </Typography>
                <Typography component="li" paragraph>
                    <strong>Cache expensive calls:</strong> Store AI responses in your cache when appropriate to 
                    reduce API costs and improve response times.
                </Typography>
                <Typography component="li" paragraph>
                    <strong>Handle rate limits:</strong> Implement proper error handling for rate limits and 
                    consider using queues for bulk processing.
                </Typography>
                <Typography component="li" paragraph>
                    <strong>Monitor token usage:</strong> Track <code>usage.total_tokens</code> in responses to 
                    manage costs and optimize prompts.
                </Typography>
            </Box>

            <Callout type="info">
                <Typography>
                    <strong>Learn More:</strong> For detailed information about the Responses API, models, and 
                    advanced features, visit the{' '}
                    <a href="https://platform.openai.com/docs" target="_blank" rel="noopener noreferrer">
                        OpenAI Platform Documentation
                    </a>.
                </Typography>
            </Callout>
        </Box>
    );
}

