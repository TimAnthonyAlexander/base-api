<?php

namespace BaseApi\Tests;

use ReflectionClass;
use Override;
use PHPUnit\Framework\TestCase;
use BaseApi\Http\Binding\ControllerBinder;
use BaseApi\Http\Request;
use BaseApi\Http\UploadedFile;

class ControllerBinderTest extends TestCase
{
    private ControllerBinder $binder;

    #[Override]
    protected function setUp(): void
    {
        $this->binder = new ControllerBinder();
    }

    public function testBindRequestProperty(): void
    {
        $request = $this->createRequest();
        $controller = new TestControllerWithRequest();
        
        $this->binder->bind($controller, $request, []);
        
        $this->assertSame($request, $controller->request);
    }

    public function testBindWithoutRequestProperty(): void
    {
        $request = $this->createRequest();
        $controller = new TestControllerWithoutRequest();

        // Should not throw any errors
        $this->binder->bind($controller, $request, []);
        $this->assertTrue(true); // If we get here, no errors were thrown
    }

    public function testBindWithRouteParams(): void
    {
        $request = $this->createRequest();
        $controller = new TestControllerForBinding();
        $routeParams = ['id' => '123', 'category_name' => 'electronics'];

        $this->binder->bind($controller, $request, $routeParams);

        $this->assertEquals('123', $controller->id);
        $this->assertEquals('electronics', $controller->categoryName); // camelCase property matches snake_case param
    }

    public function testBindWithQueryParams(): void
    {
        $query = ['name' => 'John', 'user_age' => '25'];
        $request = $this->createRequest(query: $query);
        $controller = new TestControllerForBinding();

        $this->binder->bind($controller, $request, []);

        $this->assertEquals('John', $controller->name);
        $this->assertEquals('25', $controller->userAge); // camelCase property matches snake_case query
    }

    public function testBindWithBodyParams(): void
    {
        $body = ['email' => 'john@example.com', 'is_active' => 'true'];
        $request = $this->createRequest(body: $body);
        $controller = new TestControllerForBinding();

        $this->binder->bind($controller, $request, []);

        $this->assertEquals('john@example.com', $controller->email);
        $this->assertEquals('true', $controller->isActive); // camelCase property matches snake_case body
    }

    public function testBindWithFiles(): void
    {
        $files = [
            'avatar' => [
                'name' => 'avatar.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/phpupload123',
                'error' => UPLOAD_ERR_OK,
                'size' => 1024
            ],
            'profile_docs' => [
                [
                    'name' => 'doc1.pdf',
                    'type' => 'application/pdf',
                    'tmp_name' => '/tmp/phpupload124',
                    'error' => UPLOAD_ERR_OK,
                    'size' => 2048
                ],
                [
                    'name' => 'doc2.pdf',
                    'type' => 'application/pdf',
                    'tmp_name' => '/tmp/phpupload125',
                    'error' => UPLOAD_ERR_OK,
                    'size' => 3072
                ]
            ]
        ];
        
        $request = $this->createRequest(files: $files);
        $controller = new TestControllerForBinding();
        
        $this->binder->bind($controller, $request, []);
        
        $this->assertInstanceOf(UploadedFile::class, $controller->avatar);
        $this->assertEquals('avatar.jpg', $controller->avatar->name);
        
        $this->assertIsArray($controller->profileDocs);
        $this->assertCount(2, $controller->profileDocs);
        $this->assertInstanceOf(UploadedFile::class, $controller->profileDocs[0]);
        $this->assertEquals('doc1.pdf', $controller->profileDocs[0]->name);
    }

    public function testBindPrecedenceRouteParamsOverQuery(): void
    {
        $query = ['id' => 'from_query'];
        $routeParams = ['id' => 'from_route'];
        $request = $this->createRequest(query: $query);
        $controller = new TestControllerForBinding();
        
        $this->binder->bind($controller, $request, $routeParams);
        
        // Route params should take precedence
        $this->assertEquals('from_route', $controller->id);
    }

    public function testBindPrecedenceQueryOverBody(): void
    {
        $query = ['name' => 'from_query'];
        $body = ['name' => 'from_body'];
        $request = $this->createRequest(query: $query, body: $body);
        $controller = new TestControllerForBinding();
        
        $this->binder->bind($controller, $request, []);
        
        // Query should take precedence over body
        $this->assertEquals('from_query', $controller->name);
    }

    public function testBindPrecedenceBodyOverFiles(): void
    {
        $body = ['name' => 'from_body'];
        $files = [
            'name' => [
                'name' => 'name.txt',
                'type' => 'text/plain',
                'tmp_name' => '/tmp/phpupload123',
                'error' => UPLOAD_ERR_OK,
                'size' => 1024
            ]
        ];
        $request = $this->createRequest(body: $body, files: $files);
        $controller = new TestControllerForBinding();
        
        $this->binder->bind($controller, $request, []);
        
        // Body should take precedence over files
        $this->assertEquals('from_body', $controller->name);
    }

    public function testBindWithTypeCoercion(): void
    {
        $query = ['age' => '25', 'is_admin' => 'true', 'score' => '98.5'];
        $request = $this->createRequest(query: $query);
        $controller = new TestControllerWithTypes();

        $this->binder->bind($controller, $request, []);

        $this->assertSame(25, $controller->age); // Coerced to int
        $this->assertTrue($controller->isAdmin); // Coerced to bool
        $this->assertEquals(98.5, $controller->score); // Coerced to float
    }

    public function testBindPreservesDefaultValues(): void
    {
        $request = $this->createRequest();
        $controller = new TestControllerWithDefaults();
        
        $this->binder->bind($controller, $request, []);
        
        // Default values should be preserved when no data is available
        $this->assertEquals('default_name', $controller->name);
        $this->assertEquals(0, $controller->age);
        $this->assertFalse($controller->isActive);
    }

    public function testBindOverridesDefaultValues(): void
    {
        $query = ['name' => 'John', 'age' => '30', 'is_active' => 'true'];
        $request = $this->createRequest(query: $query);
        $controller = new TestControllerWithDefaults();
        
        $this->binder->bind($controller, $request, []);
        
        // Values from request should override defaults
        $this->assertEquals('John', $controller->name);
        $this->assertSame(30, $controller->age);
        $this->assertTrue($controller->isActive);
    }

    public function testBindWithNullValues(): void
    {
        $query = ['nullable_field' => null];
        $request = $this->createRequest(query: $query);
        $controller = new TestControllerForBinding();
        
        $this->binder->bind($controller, $request, []);
        
        // Null values should be set
        $this->assertNull($controller->nullableField);
    }

    public function testBindCamelToSnakeCaseConversion(): void
    {
        $testCases = [
            // Query params in snake_case, properties in camelCase
            'user_name' => 'userName',
            'is_admin' => 'isAdmin',
            'profile_image' => 'profileImage',
            'last_login_date' => 'lastLoginDate',
        ];

        foreach ($testCases as $snakeCase => $camelCase) {
            $query = [$snakeCase => 'test_value'];
            $request = $this->createRequest(query: $query);
            $controller = new TestControllerForCaseConversion();
            
            $this->binder->bind($controller, $request, []);
            
            $this->assertEquals('test_value', $controller->$camelCase);
        }
    }

    public function testBindIgnoresPrivateAndProtectedProperties(): void
    {
        $query = ['privateField' => 'private_value', 'protectedField' => 'protected_value'];
        $request = $this->createRequest(query: $query);
        $controller = new TestControllerWithPrivateProperties();
        
        $this->binder->bind($controller, $request, []);
        
        // Private and protected properties should not be bound
        $this->assertEquals('default_private', $controller->getPrivateField());
        $this->assertEquals('default_protected', $controller->getProtectedField());
    }

    public function testBindSkipsRequestProperty(): void
    {
        $query = ['request' => 'should_not_override'];
        $request = $this->createRequest(query: $query);
        $controller = new TestControllerWithRequest();
        
        $this->binder->bind($controller, $request, []);
        
        // Request property should contain the actual request object, not the query value
        $this->assertSame($request, $controller->request);
    }

    public function testBindWithInvalidFileArray(): void
    {
        $files = [
            'invalid_file' => [
                'name' => 'test.jpg',
                'type' => 'image/jpeg',
                // Missing tmp_name
                'error' => UPLOAD_ERR_OK,
                'size' => 1024
            ]
        ];
        
        $request = $this->createRequest(files: $files);
        $controller = new TestControllerForBinding();
        
        $this->binder->bind($controller, $request, []);
        
        // Should remain as the original array since it's invalid
        $this->assertEquals($files['invalid_file'], $controller->invalidFile);
    }

    public function testBindLeavesUninitializedPropertiesWhenNoValueProvided(): void
    {
        // Test that non-nullable properties without defaults are left uninitialized
        // when no value is provided (instead of trying to set them to null which would fail)
        $request = $this->createRequest();
        $controller = new TestControllerWithRequiredFields();
        
        $this->binder->bind($controller, $request, []);
        
        // These properties should remain uninitialized
        $reflection = new ReflectionClass($controller);
        
        $titleProp = $reflection->getProperty('title');
        $this->assertFalse($titleProp->isInitialized($controller));
        
        $priceProp = $reflection->getProperty('price');
        $this->assertFalse($priceProp->isInitialized($controller));
        
        $stockProp = $reflection->getProperty('stock');
        $this->assertFalse($stockProp->isInitialized($controller));
        
        // But nullable property with default should be set
        $this->assertNull($controller->description);
    }

    public function testBindSetsRequiredFieldsWhenProvided(): void
    {
        // Test that required fields are properly set when values are provided
        $body = [
            'title' => 'Test Product',
            'description' => 'A test product',
            'price' => '29.99',
            'stock' => '10'
        ];
        $request = $this->createRequest(body: $body);
        $controller = new TestControllerWithRequiredFields();
        
        $this->binder->bind($controller, $request, []);
        
        // All properties should now be initialized
        $reflection = new ReflectionClass($controller);
        
        $this->assertTrue($reflection->getProperty('title')->isInitialized($controller));
        $this->assertEquals('Test Product', $controller->title);
        
        $this->assertEquals('A test product', $controller->description);
        
        $this->assertTrue($reflection->getProperty('price')->isInitialized($controller));
        $this->assertEquals(29.99, $controller->price);
        
        $this->assertTrue($reflection->getProperty('stock')->isInitialized($controller));
        $this->assertSame(10, $controller->stock);
    }

    private function createRequest(
        array $query = [],
        array $body = [],
        array $files = [],
        array $headers = []
    ): Request {
        return new Request(
            'GET',
            '/test',
            $headers,
            $query,
            $body,
            null,
            $files,
            [],
            [],
            'test-request-id'
        );
    }
}

// Test classes for binding tests
class TestControllerWithRequest
{
    public ?Request $request = null;
}

class TestControllerWithoutRequest
{
    public string $name = 'default';
}

class TestControllerForBinding
{
    public ?string $id = null;

    public ?string $name = null;

    public ?string $email = null;

    public ?string $categoryName = null;

    public ?string $userAge = null;

    public ?string $isActive = null;

    public ?UploadedFile $avatar = null;

    public ?array $profileDocs = null;

    public ?string $nullableField = null;

    public mixed $invalidFile = null;
}

class TestControllerWithTypes
{
    public int $age = 0;

    public bool $isAdmin = false;

    public float $score = 0.0;
}

class TestControllerWithDefaults
{
    public string $name = 'default_name';

    public int $age = 0;

    public bool $isActive = false;
}

class TestControllerForCaseConversion
{
    public ?string $userName = null;

    public ?string $isAdmin = null;

    public ?string $profileImage = null;

    public ?string $lastLoginDate = null;
}

class TestControllerWithPrivateProperties
{
    public string $publicField = 'public';

    private string $privateField = 'default_private';

    protected string $protectedField = 'default_protected';

    public function getPrivateField(): string
    {
        return $this->privateField;
    }

    public function getProtectedField(): string
    {
        return $this->protectedField;
    }
}

class TestControllerWithRequiredFields
{
    public string $title;
    
    public ?string $description = null;
    
    public float $price;
    
    public int $stock;
}
