<?php

declare(strict_types=1);

namespace Tests;

use BaseApi\Modules\OpenAI;
use BaseApi\App;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Test suite for OpenAI Responses API Client
 */
class OpenAITest extends TestCase
{
    public function test_constructor_accepts_custom_api_key_and_model(): void
    {
        $openai = new OpenAI('custom-key', 'gpt-4');
        
        $this->assertInstanceOf(OpenAI::class, $openai);
    }
    
    public function test_constructor_throws_exception_when_api_key_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API key not configured');
        
        new OpenAI();
    }
    
    public function test_with_tools_returns_new_instance(): void
    {
        $openai = new OpenAI('test-api-key');
        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_weather',
                    'description' => 'Get current weather',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'location' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
        
        $withTools = $openai->withTools($tools);
        
        $this->assertInstanceOf(OpenAI::class, $withTools);
        $this->assertNotSame($openai, $withTools);
    }
    
    public function test_with_tools_accepts_tool_choice(): void
    {
        $openai = new OpenAI('test-api-key');
        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'calculate',
                    'parameters' => ['type' => 'object'],
                ],
            ],
        ];
        
        $withTools = $openai->withTools($tools, 'required');
        
        $this->assertInstanceOf(OpenAI::class, $withTools);
    }
    
    public function test_with_json_schema_returns_new_instance(): void
    {
        $openai = new OpenAI('test-api-key');
        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'age' => ['type' => 'integer'],
            ],
            'required' => ['name'],
            'additionalProperties' => false,
        ];
        
        $withSchema = $openai->withJsonSchema('user_data', $schema);
        
        $this->assertInstanceOf(OpenAI::class, $withSchema);
        $this->assertNotSame($openai, $withSchema);
    }
    
    public function test_with_json_schema_respects_strict_parameter(): void
    {
        $openai = new OpenAI('test-api-key');
        $schema = ['type' => 'object'];
        
        $withSchema = $openai->withJsonSchema('test', $schema, false);
        
        $this->assertInstanceOf(OpenAI::class, $withSchema);
    }
    
    public function test_with_reasoning_returns_new_instance(): void
    {
        $openai = new OpenAI('test-api-key');
        
        $withReasoning = $openai->withReasoning('high');
        
        $this->assertInstanceOf(OpenAI::class, $withReasoning);
        $this->assertNotSame($openai, $withReasoning);
    }
    
    public function test_with_reasoning_default_effort(): void
    {
        $openai = new OpenAI('test-api-key');
        
        $withReasoning = $openai->withReasoning();
        
        $this->assertInstanceOf(OpenAI::class, $withReasoning);
    }
    
    public function test_with_options_returns_new_instance(): void
    {
        $openai = new OpenAI('test-api-key');
        $options = [
            'temperature' => 0.7,
            'max_output_tokens' => 1000,
            'top_p' => 0.9,
        ];
        
        $withOptions = $openai->withOptions($options);
        
        $this->assertInstanceOf(OpenAI::class, $withOptions);
        $this->assertNotSame($openai, $withOptions);
    }
    
    public function test_with_options_merges_with_existing_options(): void
    {
        $openai = new OpenAI('test-api-key');
        
        $withOptions = $openai
            ->withOptions(['temperature' => 0.5])
            ->withOptions(['max_output_tokens' => 500]);
        
        $this->assertInstanceOf(OpenAI::class, $withOptions);
    }
    
    public function test_model_returns_new_instance(): void
    {
        $openai = new OpenAI('test-api-key');
        
        $withModel = $openai->model('gpt-4o');
        
        $this->assertInstanceOf(OpenAI::class, $withModel);
        $this->assertNotSame($openai, $withModel);
    }
    
    public function test_extract_text_from_response(): void
    {
        $response = [
            'output' => [
                [
                    'type' => 'output_text',
                    'text' => 'Hello, world!',
                ],
            ],
        ];
        
        $text = OpenAI::extractText($response);
        
        $this->assertEquals('Hello, world!', $text);
    }
    
    public function test_extract_text_with_content_field(): void
    {
        $response = [
            'output' => [
                [
                    'type' => 'output_text',
                    'content' => 'Alternative content field',
                ],
            ],
        ];
        
        $text = OpenAI::extractText($response);
        
        $this->assertEquals('Alternative content field', $text);
    }
    
    public function test_extract_text_returns_empty_when_no_text_output(): void
    {
        $response = [
            'output' => [
                [
                    'type' => 'tool_call',
                    'name' => 'some_function',
                ],
            ],
        ];
        
        $text = OpenAI::extractText($response);
        
        $this->assertEquals('', $text);
    }
    
    public function test_extract_text_returns_empty_for_empty_response(): void
    {
        $text = OpenAI::extractText([]);
        
        $this->assertEquals('', $text);
    }
    
    public function test_extract_tool_calls_from_response(): void
    {
        $response = [
            'output' => [
                [
                    'type' => 'tool_call',
                    'call_id' => 'call_123',
                    'tool_name' => 'get_weather',
                    'arguments' => [
                        'location' => 'San Francisco',
                        'unit' => 'celsius',
                    ],
                ],
                [
                    'type' => 'tool_call',
                    'call_id' => 'call_456',
                    'tool_name' => 'search_web',
                    'arguments' => [
                        'query' => 'latest news',
                    ],
                ],
            ],
        ];
        
        $tools = OpenAI::extractToolCalls($response);
        
        $this->assertCount(2, $tools);
        $this->assertEquals('call_123', $tools[0]['id']);
        $this->assertEquals('get_weather', $tools[0]['name']);
        $this->assertEquals(['location' => 'San Francisco', 'unit' => 'celsius'], $tools[0]['arguments']);
        $this->assertEquals('call_456', $tools[1]['id']);
        $this->assertEquals('search_web', $tools[1]['name']);
    }
    
    public function test_extract_tool_calls_with_alternative_name_field(): void
    {
        $response = [
            'output' => [
                [
                    'type' => 'tool_call',
                    'call_id' => 'call_789',
                    'name' => 'alternative_field',
                    'arguments' => ['key' => 'value'],
                ],
            ],
        ];
        
        $tools = OpenAI::extractToolCalls($response);
        
        $this->assertCount(1, $tools);
        $this->assertEquals('alternative_field', $tools[0]['name']);
    }
    
    public function test_extract_tool_calls_returns_empty_array_when_no_tools(): void
    {
        $response = [
            'output' => [
                [
                    'type' => 'output_text',
                    'text' => 'Just text, no tools',
                ],
            ],
        ];
        
        $tools = OpenAI::extractToolCalls($response);
        
        $this->assertEmpty($tools);
    }
    
    public function test_extract_tool_calls_returns_empty_array_for_empty_response(): void
    {
        $tools = OpenAI::extractToolCalls([]);
        
        $this->assertEmpty($tools);
    }
    
    public function test_extract_tool_calls_handles_missing_fields_gracefully(): void
    {
        $response = [
            'output' => [
                [
                    'type' => 'tool_call',
                    // Missing fields should use defaults
                ],
            ],
        ];
        
        $tools = OpenAI::extractToolCalls($response);
        
        $this->assertCount(1, $tools);
        $this->assertEquals('', $tools[0]['id']);
        $this->assertEquals('', $tools[0]['name']);
        $this->assertEquals([], $tools[0]['arguments']);
    }
    
    public function test_method_chaining_immutability(): void
    {
        $openai = new OpenAI('test-api-key');
        
        $configured = $openai
            ->model('gpt-4o')
            ->withOptions(['temperature' => 0.8])
            ->withReasoning('high')
            ->withTools([['type' => 'function']]);
        
        // Each method should return a new instance
        $this->assertNotSame($openai, $configured);
        $this->assertInstanceOf(OpenAI::class, $configured);
    }
    
    public function test_extract_text_prefers_text_field_over_content(): void
    {
        $response = [
            'output' => [
                [
                    'type' => 'output_text',
                    'text' => 'Text field',
                    'content' => 'Content field',
                ],
            ],
        ];
        
        $text = OpenAI::extractText($response);
        
        $this->assertEquals('Text field', $text);
    }
    
    public function test_extract_tool_calls_prefers_tool_name_over_name(): void
    {
        $response = [
            'output' => [
                [
                    'type' => 'tool_call',
                    'call_id' => 'call_123',
                    'tool_name' => 'preferred_name',
                    'name' => 'fallback_name',
                    'arguments' => [],
                ],
            ],
        ];
        
        $tools = OpenAI::extractToolCalls($response);
        
        $this->assertEquals('preferred_name', $tools[0]['name']);
    }
    
    public function test_multiple_output_items_returns_first_text(): void
    {
        $response = [
            'output' => [
                [
                    'type' => 'tool_call',
                    'name' => 'some_tool',
                ],
                [
                    'type' => 'output_text',
                    'text' => 'First text',
                ],
                [
                    'type' => 'output_text',
                    'text' => 'Second text',
                ],
            ],
        ];
        
        $text = OpenAI::extractText($response);
        
        $this->assertEquals('First text', $text);
    }
    
    public function test_complex_json_schema_configuration(): void
    {
        $openai = new OpenAI('test-api-key');
        $schema = [
            'type' => 'object',
            'properties' => [
                'user' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'email' => ['type' => 'string'],
                        'age' => ['type' => 'integer', 'minimum' => 0],
                        'tags' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                    ],
                    'required' => ['name', 'email'],
                ],
                'metadata' => [
                    'type' => 'object',
                    'additionalProperties' => true,
                ],
            ],
            'required' => ['user'],
            'additionalProperties' => false,
        ];
        
        $configured = $openai->withJsonSchema('complex_user', $schema, true);
        
        $this->assertInstanceOf(OpenAI::class, $configured);
    }
    
    public function test_all_reasoning_effort_levels(): void
    {
        $openai = new OpenAI('test-api-key');
        
        foreach (['low', 'medium', 'high'] as $effort) {
            $configured = $openai->withReasoning($effort);
            $this->assertInstanceOf(OpenAI::class, $configured);
        }
    }
    
    public function test_multiple_tools_configuration(): void
    {
        $openai = new OpenAI('test-api-key');
        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'tool_1',
                    'description' => 'First tool',
                    'parameters' => ['type' => 'object'],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'tool_2',
                    'description' => 'Second tool',
                    'parameters' => ['type' => 'object'],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'tool_3',
                    'description' => 'Third tool',
                    'parameters' => ['type' => 'object'],
                ],
            ],
        ];
        
        $configured = $openai->withTools($tools, 'required');
        
        $this->assertInstanceOf(OpenAI::class, $configured);
    }
}

