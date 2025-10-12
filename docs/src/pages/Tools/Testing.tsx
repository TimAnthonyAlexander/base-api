import { Box, Typography, Alert, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper, List, ListItem, ListItemText } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';

export default function Testing() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Testing
            </Typography>
            <Typography variant="h5" color="text.secondary" paragraph>
                Beautiful test suite with fluent API testing
            </Typography>

            <Typography>
                BaseAPI includes a complete testing framework with a beautiful TUI, parallel test execution,
                and a fluent API for writing expressive endpoint tests. Write tests that are easy to read,
                maintain, and run blazingly fast.
            </Typography>

            <Alert severity="success" sx={{ my: 3 }}>
                <strong>Quick Start:</strong> Run <code>./mason test</code> to execute your test suite with parallel execution and a beautiful TUI interface.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Running Tests
            </Typography>

            <Typography>
                The <code>test</code> command runs your test suite using paratest for parallel execution:
            </Typography>

            <CodeBlock language="bash" code={`# Run all tests
./mason test

# Run with more parallel processes
./mason test --parallel=8

# Run specific test suite
./mason test --testsuite=Feature

# Filter tests by name
./mason test --filter=UserTest

# Run with code coverage
./mason test --coverage

# Verbose output
./mason test --verbose`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Writing Tests
            </Typography>

            <Typography>
                BaseAPI provides a <code>TestCase</code> base class with fluent methods for testing API endpoints:
            </Typography>

            <CodeBlock language="php" code={`<?php

namespace App\\Tests\\Feature;

use BaseApi\\Testing\\TestCase;

class UserApiTest extends TestCase
{
    public function test_can_create_user(): void
    {
        $this->post('/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'John Doe')
            ->assertJsonPath('data.email', 'john@example.com')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'created_at'
                ]
            ]);
    }
    
    public function test_can_list_users(): void
    {
        $this->get('/users')
            ->assertOk()
            ->assertJsonCount(3);
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                HTTP Methods
            </Typography>

            <Typography>
                The TestCase provides methods for all HTTP verbs:
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }} elevation={0}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Method</strong></TableCell>
                            <TableCell><strong>Description</strong></TableCell>
                            <TableCell><strong>Example</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            <TableCell><code>get($path, $query = [])</code></TableCell>
                            <TableCell>Make a GET request</TableCell>
                            <TableCell><code>$this-&gt;get('/users', ['page' =&gt; 1])</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>post($path, $data = [])</code></TableCell>
                            <TableCell>Make a POST request</TableCell>
                            <TableCell><code>$this-&gt;post('/users', ['name' =&gt; 'John'])</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>put($path, $data = [])</code></TableCell>
                            <TableCell>Make a PUT request</TableCell>
                            <TableCell><code>$this-&gt;put('/users/1', ['name' =&gt; 'Jane'])</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>patch($path, $data = [])</code></TableCell>
                            <TableCell>Make a PATCH request</TableCell>
                            <TableCell><code>$this-&gt;patch('/users/1', ['email' =&gt; 'new@example.com'])</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>delete($path)</code></TableCell>
                            <TableCell>Make a DELETE request</TableCell>
                            <TableCell><code>$this-&gt;delete('/users/1')</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>json($method, $path, $data = [])</code></TableCell>
                            <TableCell>Make a JSON request with any method</TableCell>
                            <TableCell><code>$this-&gt;json('POST', '/users', $data)</code></TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Status Assertions
            </Typography>

            <Typography>
                Assert HTTP status codes with readable methods:
            </Typography>

            <CodeBlock language="php" code={`// Generic status assertion
$response->assertStatus(200);

// Convenient shortcuts
$response->assertOk();              // 200
$response->assertCreated();         // 201
$response->assertNoContent();       // 204
$response->assertBadRequest();      // 400
$response->assertUnauthorized();    // 401
$response->assertForbidden();       // 403
$response->assertNotFound();        // 404
$response->assertUnprocessable();   // 422`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                JSON Assertions
            </Typography>

            <Typography>
                BaseAPI provides comprehensive JSON assertion methods:
            </Typography>

            <CodeBlock language="php" code={`// Assert JSON contains specific data
$response->assertJson([
    'data' => [
        'name' => 'John',
        'email' => 'john@example.com'
    ]
]);

// Assert exact JSON match
$response->assertExactJson([
    'status' => 'success',
    'data' => ['id' => 1]
]);

// Assert JSON structure exists
$response->assertJsonStructure([
    'data' => [
        'id',
        'name',
        'email',
        'created_at'
    ]
]);

// Assert nested structure
$response->assertJsonStructure([
    'data' => [
        'user' => [
            'id',
            'name',
            'profile' => [
                'bio',
                'avatar'
            ]
        ]
    ]
]);

// Assert specific path has value
$response->assertJsonPath('data.user.name', 'John');
$response->assertJsonPath('data.status', 'active');

// Assert key exists
$response->assertJsonHas('data');
$response->assertJsonHas('meta.timestamp');

// Assert key is missing
$response->assertJsonMissing('error');

// Assert array count
$response->assertJsonCount(3);  // Root array has 3 items
$response->assertJsonCount(5, 'data.users');  // Nested array

// Assert contains fragment
$response->assertJsonFragment(['status' => 'success']);`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Header Assertions
            </Typography>

            <Typography>
                Test response headers:
            </Typography>

            <CodeBlock language="php" code={`// Assert header exists with optional value check
$response->assertHeader('Content-Type');
$response->assertHeader('Content-Type', 'application/json; charset=utf-8');
$response->assertHeader('X-Custom-Header', 'value');

// Assert header is missing
$response->assertHeaderMissing('X-Debug-Info');`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Chaining Assertions
            </Typography>

            <Typography>
                All assertion methods return <code>$this</code> for fluent chaining:
            </Typography>

            <CodeBlock language="php" code={`$this->post('/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
])
    ->assertCreated()
    ->assertJsonPath('data.name', 'John Doe')
    ->assertJsonHas('data.id')
    ->assertJsonStructure([
        'data' => ['id', 'name', 'email']
    ])
    ->assertHeader('Content-Type', 'application/json; charset=utf-8');`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Accessing Response Data
            </Typography>

            <Typography>
                Get the raw response data for custom assertions:
            </Typography>

            <CodeBlock language="php" code={`$response = $this->get('/users');

// Get JSON as array
$json = $response->json();
$this->assertIsArray($json['data']);

// Get raw response object
$rawResponse = $response->getResponse();
$this->assertEquals(200, $rawResponse->status);`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Complete Example
            </Typography>

            <Typography>
                A comprehensive test demonstrating various features:
            </Typography>

            <CodeBlock language="php" code={`<?php

namespace App\\Tests\\Feature;

use BaseApi\\Testing\\TestCase;

class ProductApiTest extends TestCase
{
    public function test_product_crud_operations(): void
    {
        // Create a product
        $createResponse = $this->post('/products', [
            'name' => 'Laptop',
            'price' => 999.99,
            'category' => 'electronics'
        ])
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'price',
                    'category',
                    'created_at'
                ]
            ]);
        
        $productId = $createResponse->json()['data']['id'];
        
        // Retrieve the product
        $this->get("/products/{$productId}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Laptop')
            ->assertJsonPath('data.price', 999.99);
        
        // Update the product
        $this->put("/products/{$productId}", [
            'price' => 899.99
        ])
            ->assertOk()
            ->assertJsonPath('data.price', 899.99);
        
        // List products
        $this->get('/products')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'price']
                ]
            ]);
        
        // Delete the product
        $this->delete("/products/{$productId}")
            ->assertNoContent();
        
        // Verify deletion
        $this->get("/products/{$productId}")
            ->assertNotFound();
    }
    
    public function test_product_validation(): void
    {
        $this->post('/products', [
            'name' => '',  // Invalid: empty name
            'price' => -10  // Invalid: negative price
        ])
            ->assertUnprocessable()
            ->assertJsonHas('errors');
    }
    
    public function test_product_search(): void
    {
        $this->get('/products', [
            'search' => 'laptop',
            'category' => 'electronics',
            'min_price' => 500
        ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'price', 'category']
                ],
                'meta' => [
                    'total',
                    'per_page',
                    'current_page'
                ]
            ]);
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Test Organization
            </Typography>

            <Typography>
                Organize tests following PHPUnit conventions:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="tests/Unit/"
                        secondary="Unit tests for individual classes and methods (models, services, helpers)"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="tests/Feature/"
                        secondary="Feature tests for API endpoints and full application flows"
                    />
                </ListItem>
            </List>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Configuration
            </Typography>

            <Typography>
                Configure test environment in <code>phpunit.xml</code>:
            </Typography>

            <CodeBlock language="xml" code={`<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>

    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="QUEUE_DRIVER" value="sync"/>
    </php>
</phpunit>`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Command Options
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }} elevation={0}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Option</strong></TableCell>
                            <TableCell><strong>Description</strong></TableCell>
                            <TableCell><strong>Example</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            <TableCell><code>--parallel=N</code></TableCell>
                            <TableCell>Number of parallel processes (default: 4)</TableCell>
                            <TableCell><code>./mason test --parallel=8</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>--filter=PATTERN</code></TableCell>
                            <TableCell>Run tests matching pattern</TableCell>
                            <TableCell><code>./mason test --filter=UserTest</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>--testsuite=NAME</code></TableCell>
                            <TableCell>Run specific test suite</TableCell>
                            <TableCell><code>./mason test --testsuite=Feature</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>--coverage</code></TableCell>
                            <TableCell>Generate code coverage report</TableCell>
                            <TableCell><code>./mason test --coverage</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>--verbose</code></TableCell>
                            <TableCell>Verbose output</TableCell>
                            <TableCell><code>./mason test --verbose</code></TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>

            <Alert severity="info" sx={{ mt: 4 }}>
                <strong>Best Practices:</strong>
                <br />• Write tests before fixing bugs to prevent regressions
                <br />• Use descriptive test method names that explain what is being tested
                <br />• Keep tests focused - one concept per test method
                <br />• Use the fluent API for readable, maintainable test code
                <br />• Run tests frequently during development with <code>./mason test</code>
            </Alert>

            <Alert severity="success" sx={{ mt: 2 }}>
                <strong>Pro Tip:</strong> The test command uses paratest for parallel execution, making your test suite run significantly faster. On a typical project, tests run 3-4x faster compared to sequential execution.
            </Alert>
        </Box>
    );
}

