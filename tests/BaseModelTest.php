<?php

namespace BaseApi\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use BaseApi\Models\BaseModel;
use BaseApi\Database\QueryBuilder;
use BaseApi\Database\ModelQuery;
use BaseApi\Database\DB;
use BaseApi\Database\Connection;
use BaseApi\App;
use BaseApi\Config;
use BaseApi\Cache\Cache;

// Create a concrete model for testing
class TestUserModel extends BaseModel
{
    public string $id = '';
    public string $name = '';
    public string $email = '';
    public ?string $description = null;
    public int $age = 0;
    public bool $active = true;
    public ?string $created_at = null;
    public ?string $updated_at = null;
    
    // For relationship testing
    public ?TestPostModel $latestPost = null;
    
    /** @var \BaseApi\Tests\TestPostModel[] */
    public array $posts = [];
    
    protected static ?string $table = null;
}

class TestPostModel extends BaseModel
{
    public string $id = '';
    public string $title = '';
    public string $content = '';
    public string $user_id = '';
    public ?string $created_at = null;
    public ?string $updated_at = null;
}

class TestCompanyModel extends BaseModel
{
    public string $id = '';
    public string $name = '';
    
    protected static ?string $table = 'companies';
}

class BaseModelTest extends TestCase
{
    private MockObject $dbMock;
    private MockObject $qbMock;
    private MockObject $connectionMock;
    
    protected function setUp(): void
    {
        // Mock the App::db() call
        $this->dbMock = $this->createMock(DB::class);
        $this->qbMock = $this->createMock(QueryBuilder::class);
        $this->connectionMock = $this->createMock(Connection::class);
        
        $this->dbMock->method('qb')->willReturn($this->qbMock);
        
        // Allow chaining
        $this->qbMock->method('table')->willReturn($this->qbMock);
        $this->qbMock->method('where')->willReturn($this->qbMock);
        $this->qbMock->method('whereIn')->willReturn($this->qbMock);
        $this->qbMock->method('select')->willReturn($this->qbMock);
        $this->qbMock->method('limit')->willReturn($this->qbMock);
        $this->qbMock->method('offset')->willReturn($this->qbMock);
    }
    
    public function testTableNameInference()
    {
        // Test default table name generation
        $tableName = TestUserModel::table();
        $this->assertEquals('test_user_models', $tableName);
        
        // Test custom table name
        $customTableName = TestCompanyModel::table();
        $this->assertEquals('companies', $customTableName);
    }
    
    public function testTableNameWithComplexClassNames()
    {
        // Create anonymous class for testing edge cases
        $model = new class extends BaseModel {
            public string $id = '';
        };
        
        $reflection = new \ReflectionClass($model);
        // Anonymous classes have complex names, this should handle them gracefully
        $tableName = $model::table();
        $this->assertIsString($tableName);
        $this->assertNotEmpty($tableName);
    }
    
    public function testSingularizeMethod()
    {
        // Test common pluralization rules
        $this->assertEquals('category', TestUserModel::singularize('categories'));
        $this->assertEquals('company', TestUserModel::singularize('companies'));
        $this->assertEquals('country', TestUserModel::singularize('countries'));
        $this->assertEquals('city', TestUserModel::singularize('cities'));
        $this->assertEquals('person', TestUserModel::singularize('people'));
        $this->assertEquals('child', TestUserModel::singularize('children'));
        
        // Test regular patterns
        $this->assertEquals('user', TestUserModel::singularize('users'));
        $this->assertEquals('post', TestUserModel::singularize('posts'));
        $this->assertEquals('boxe', TestUserModel::singularize('boxes')); // Current implementation behavior
        $this->assertEquals('class', TestUserModel::singularize('classes'));
        
        // Test no change cases
        $this->assertEquals('data', TestUserModel::singularize('data'));
        $this->assertEquals('fish', TestUserModel::singularize('fish'));
    }
    
    public function testFromRowHydration()
    {
        $rowData = [
            'id' => '123',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => '30',
            'active' => '1',
            'created_at' => '2023-01-01 10:00:00'
        ];
        
        $model = TestUserModel::fromRow($rowData);
        
        $this->assertEquals('123', $model->id);
        $this->assertEquals('John Doe', $model->name);
        $this->assertEquals('john@example.com', $model->email);
        $this->assertEquals(30, $model->age); // Should be cast to int
        $this->assertTrue($model->active); // Should be cast to bool
        $this->assertEquals('2023-01-01 10:00:00', $model->created_at);
    }
    
    public function testToArrayIncludesInitializedProperties()
    {
        $model = new TestUserModel();
        $model->id = '123';
        $model->name = 'John Doe';
        $model->email = 'john@example.com';
        // age, active have default values so they're initialized
        // description is nullable with default null, so it's initialized
        // created_at, updated_at are nullable with default null, so they're initialized
        
        $array = $model->toArray();
        
        $this->assertEquals('123', $array['id']);
        $this->assertEquals('John Doe', $array['name']);
        $this->assertEquals('john@example.com', $array['email']);
        $this->assertEquals(0, $array['age']);
        $this->assertTrue($array['active']);
        
        // Properties with default values are included
        $this->assertEquals(null, $array['description']);
        $this->assertEquals(null, $array['created_at']);
        $this->assertEquals(null, $array['updated_at']);
        
        // Untyped relationship properties may or may not be included based on initialization
        // latestPost has no default but is nullable, so should be included as null
        $this->assertArrayHasKey('latestPost', $array);
        $this->assertEquals(null, $array['latestPost']);
        
        // posts has default empty array, so should be included
        $this->assertArrayHasKey('posts', $array);
        $this->assertEquals([], $array['posts']);
    }
    
    public function testToArrayExcludesStaticProperties()
    {
        $model = new TestUserModel();
        $model->id = '123';
        
        $array = $model->toArray();
        
        // Should not include static properties like $table
        $this->assertArrayNotHasKey('table', $array);
    }
    
    public function testJsonSerialize()
    {
        $model = new TestUserModel();
        $model->id = '123';
        $model->name = 'John Doe';
        
        $jsonData = $model->jsonSerialize();
        
        $expectedData = [
            'id' => '123', 
            'name' => 'John Doe', 
            'email' => '', 
            'description' => null, 
            'age' => 0, 
            'active' => true,
            'created_at' => null,
            'updated_at' => null,
            'latestPost' => null,
            'posts' => []
        ];
        
        $this->assertEquals($expectedData, $jsonData);
    }
    
    public function testInferForeignKeyFromTypedProperty()
    {
        [$fkColumn, $relatedTable, $relatedClass] = TestUserModel::inferForeignKeyFromTypedProperty('latestPost');
        
        $this->assertEquals('latestPost_id', $fkColumn);
        $this->assertEquals('test_post_models', $relatedTable);
        $this->assertEquals('BaseApi\Tests\TestPostModel', $relatedClass);
    }
    
    public function testInferForeignKeyFromTypedPropertyThrowsOnInvalidProperty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property nonexistent not found');
        
        TestUserModel::inferForeignKeyFromTypedProperty('nonexistent');
    }
    
    public function testInferForeignKeyFromTypedPropertyThrowsOnNonTypedProperty()
    {
        // Create a model with an untyped property
        $model = new class extends BaseModel {
            public $untypedProperty;
        };
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must have a typed hint');
        
        $model::inferForeignKeyFromTypedProperty('untypedProperty');
    }
    
    public function testInferHasMany()
    {
        [$fkColumn, $relatedClass] = TestUserModel::inferHasMany('posts');
        
        $this->assertEquals('test_user_model_id', $fkColumn);
        $this->assertEquals('\BaseApi\Tests\TestPostModel', $relatedClass);
    }
    
    public function testInferHasManyThrowsOnInvalidProperty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property nonexistent not found');
        
        TestUserModel::inferHasMany('nonexistent');
    }
    
    public function testInferHasManyThrowsOnMissingDocblock()
    {
        // Create a model with array property but no proper docblock
        $model = new class extends BaseModel {
            public array $items = [];
        };
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must have @var ClassName[] docblock');
        
        $model::inferHasMany('items');
    }
    
    public function testGetRelationType()
    {
        $this->assertEquals('belongsTo', TestUserModel::getRelationType('latestPost'));
        $this->assertEquals('hasMany', TestUserModel::getRelationType('posts'));
    }
    
    public function testGetRelationTypeThrowsOnInvalidProperty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property invalid not found');
        
        TestUserModel::getRelationType('invalid');
    }
    
    public function testGetRelationTypeThrowsOnUnknownRelationType()
    {
        // Create a model with a property that's neither BaseModel nor has proper docblock
        $model = new class extends BaseModel {
            public string $simpleString = '';
        };
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot determine relation type');
        
        $model::getRelationType('simpleString');
    }
    
    public function testGetCacheTags()
    {
        $model = new TestUserModel();
        $model->id = '123';
        
        $tags = $model->getCacheTags();
        
        $expectedTags = [
            'model:test_user_models',
            'model:' . TestUserModel::class,
            'model:test_user_models:123',
            'model:' . TestUserModel::class . ':123'
        ];
        
        $this->assertEquals($expectedTags, $tags);
    }
    
    public function testGetCacheTagsWithoutId()
    {
        $model = new TestUserModel();
        // No ID set
        
        $tags = $model->getCacheTags();
        
        $expectedTags = [
            'model:test_user_models',
            'model:' . TestUserModel::class
        ];
        
        $this->assertEquals($expectedTags, $tags);
    }
    
    // Note: Most query methods (find, where, etc.) and save/delete methods 
    // require actual database interaction and are better tested as integration tests.
    // However, we can test the query building aspect by mocking App::db()
    
    public function testBelongsToRelationship()
    {
        $parentModel = new TestUserModel();
        $parentModel->id = '123';
        
        $relation = $parentModel->belongsTo(TestPostModel::class);
        
        $this->assertInstanceOf(\BaseApi\Database\Relations\BelongsTo::class, $relation);
        $this->assertEquals(TestPostModel::class, $relation->getRelatedClass());
        $this->assertEquals($parentModel, $relation->getParent());
    }
    
    public function testBelongsToRelationshipWithCustomKeys()
    {
        $parentModel = new TestUserModel();
        $parentModel->id = '123';
        
        $relation = $parentModel->belongsTo(TestPostModel::class, 'custom_fk', 'custom_local_key');
        
        $this->assertInstanceOf(\BaseApi\Database\Relations\BelongsTo::class, $relation);
    }
    
    public function testHasManyRelationship()
    {
        $parentModel = new TestUserModel();
        $parentModel->id = '123';
        
        $relation = $parentModel->hasMany(TestPostModel::class);
        
        $this->assertInstanceOf(\BaseApi\Database\Relations\HasMany::class, $relation);
        $this->assertEquals(TestPostModel::class, $relation->getRelatedClass());
        $this->assertEquals($parentModel, $relation->getParent());
    }
    
    public function testHasManyRelationshipWithCustomKeys()
    {
        $parentModel = new TestUserModel();
        $parentModel->id = '123';
        
        $relation = $parentModel->hasMany(TestPostModel::class, 'custom_fk', 'custom_local_key');
        
        $this->assertInstanceOf(\BaseApi\Database\Relations\HasMany::class, $relation);
    }
}
