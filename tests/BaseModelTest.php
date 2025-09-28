<?php

namespace BaseApi\Tests;

use Override;
use ReflectionClass;
use InvalidArgumentException;
use BaseApi\Database\Relations\BelongsTo;
use BaseApi\Database\Relations\HasMany;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use BaseApi\Models\BaseModel;
use BaseApi\Database\QueryBuilder;
use BaseApi\Database\DB;

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

    #[Override]
    protected function setUp(): void
    {
        // Mock the App::db() call
        $this->dbMock = $this->createMock(DB::class);
        $this->qbMock = $this->createMock(QueryBuilder::class);

        $this->dbMock->method('qb')->willReturn($this->qbMock);

        // Allow chaining
        $this->qbMock->method('table')->willReturn($this->qbMock);
        $this->qbMock->method('where')->willReturn($this->qbMock);
        $this->qbMock->method('whereIn')->willReturn($this->qbMock);
        $this->qbMock->method('select')->willReturn($this->qbMock);
        $this->qbMock->method('limit')->willReturn($this->qbMock);
        $this->qbMock->method('offset')->willReturn($this->qbMock);
    }

    public function testTableNameInference(): void
    {
        // Test default table name generation
        $tableName = TestUserModel::table();
        $this->assertEquals('test_user_model', $tableName);

        // Test custom table name
        $customTableName = TestCompanyModel::table();
        $this->assertEquals('companies', $customTableName);
    }

    public function testTableNameWithComplexClassNames(): void
    {
        // Create anonymous class for testing edge cases
        $model = new class extends BaseModel {
            public string $id = '';
        };

        new ReflectionClass($model);
        // Anonymous classes have complex names, this should handle them gracefully
        $tableName = $model::table();
        $this->assertIsString($tableName);
        $this->assertNotEmpty($tableName);
    }

    public function testSingularizeMethod(): void
    {
        // Since tables are no longer pluralized, singularize should return input as-is
        $this->assertEquals('category', TestUserModel::singularize('category'));
        $this->assertEquals('company', TestUserModel::singularize('company'));
        $this->assertEquals('user', TestUserModel::singularize('user'));
        $this->assertEquals('post', TestUserModel::singularize('post'));
        $this->assertEquals('data', TestUserModel::singularize('data'));
        $this->assertEquals('fish', TestUserModel::singularize('fish'));
    }

    public function testFromRowHydration(): void
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

    public function testToArrayIncludesInitializedProperties(): void
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

        // With new dynamic properties approach, uninitialized relations won't be in output
        // unless explicitly included with includeRelations=true
        // Since latestPost and posts are nullable/empty by default, they won't be included
        // in the basic toArray() call
    }

    public function testToArrayExcludesStaticProperties(): void
    {
        $model = new TestUserModel();
        $model->id = '123';

        $array = $model->toArray();

        // Should not include static properties like $table
        $this->assertArrayNotHasKey('table', $array);
    }

    public function testJsonSerialize(): void
    {
        $model = new TestUserModel();
        $model->id = '123';
        $model->name = 'John Doe';

        $jsonData = $model->jsonSerialize();

        // With dynamic properties approach, only initialized properties and object vars are included
        $this->assertEquals('123', $jsonData['id']);
        $this->assertEquals('John Doe', $jsonData['name']);
        $this->assertEquals('', $jsonData['email']);
        $this->assertEquals(0, $jsonData['age']);
        $this->assertTrue($jsonData['active']);
        $this->assertEquals(null, $jsonData['description']);
        $this->assertEquals(null, $jsonData['created_at']);
        $this->assertEquals(null, $jsonData['updated_at']);

        // Relations are handled differently - they're excluded unless includeRelations=true
    }

    public function testInferForeignKeyFromTypedProperty(): void
    {
        [$fkColumn, $relatedTable, $relatedClass] = TestUserModel::inferForeignKeyFromTypedProperty('latestPost');

        $this->assertEquals('latestPost_id', $fkColumn);
        $this->assertEquals('test_post_model', $relatedTable);
        $this->assertEquals(\BaseApi\Tests\TestPostModel::class, $relatedClass);
    }

    public function testInferForeignKeyFromTypedPropertyThrowsOnInvalidProperty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Property nonexistent not found');

        TestUserModel::inferForeignKeyFromTypedProperty('nonexistent');
    }

    public function testInferForeignKeyFromTypedPropertyThrowsOnNonTypedProperty(): void
    {
        // Create a model with an untyped property
        $model = new class extends BaseModel {
            public $untypedProperty;
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must have a typed hint');

        $model::inferForeignKeyFromTypedProperty('untypedProperty');
    }

    public function testInferHasMany(): void
    {
        [$fkColumn, $relatedClass] = TestUserModel::inferHasMany('posts');

        $this->assertEquals('test_user_model_id', $fkColumn);
        $this->assertEquals('\BaseApi\Tests\TestPostModel', $relatedClass);
    }

    public function testInferHasManyThrowsOnInvalidProperty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Property nonexistent not found');

        TestUserModel::inferHasMany('nonexistent');
    }

    public function testInferHasManyThrowsOnMissingDocblock(): void
    {
        // Create a model with array property but no proper docblock
        $model = new class extends BaseModel {
            public array $items = [];
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must have @var ClassName[] docblock');

        $model::inferHasMany('items');
    }

    public function testGetRelationType(): void
    {
        $this->assertEquals('belongsTo', TestUserModel::getRelationType('latestPost'));
        $this->assertEquals('hasMany', TestUserModel::getRelationType('posts'));
    }

    public function testGetRelationTypeThrowsOnInvalidProperty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Property invalid not found');

        TestUserModel::getRelationType('invalid');
    }

    public function testGetRelationTypeThrowsOnUnknownRelationType(): void
    {
        // Create a model with a property that's neither BaseModel nor has proper docblock
        $model = new class extends BaseModel {
            public string $simpleString = '';
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot determine relation type');

        $model::getRelationType('simpleString');
    }

    public function testGetCacheTags(): void
    {
        $model = new TestUserModel();
        $model->id = '123';

        $tags = $model->getCacheTags();

        $expectedTags = [
            'model:test_user_model',
            'model:' . TestUserModel::class,
            'model:test_user_model:123',
            'model:' . TestUserModel::class . ':123'
        ];

        $this->assertEquals($expectedTags, $tags);
    }

    public function testGetCacheTagsWithoutId(): void
    {
        $model = new TestUserModel();
        // No ID set

        $tags = $model->getCacheTags();

        $expectedTags = [
            'model:test_user_model',
            'model:' . TestUserModel::class
        ];

        $this->assertEquals($expectedTags, $tags);
    }

    // Note: Most query methods (find, where, etc.) and save/delete methods 
    // require actual database interaction and are better tested as integration tests.
    // However, we can test the query building aspect by mocking App::db()

    public function testBelongsToRelationship(): void
    {
        $parentModel = new TestUserModel();
        $parentModel->id = '123';

        $relation = $parentModel->belongsTo(TestPostModel::class);

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(TestPostModel::class, $relation->getRelatedClass());
        $this->assertEquals($parentModel, $relation->getParent());
    }

    public function testBelongsToRelationshipWithCustomKeys(): void
    {
        $parentModel = new TestUserModel();
        $parentModel->id = '123';

        $relation = $parentModel->belongsTo(TestPostModel::class, 'custom_fk', 'custom_local_key');

        $this->assertInstanceOf(BelongsTo::class, $relation);
    }

    public function testHasManyRelationship(): void
    {
        $parentModel = new TestUserModel();
        $parentModel->id = '123';

        $relation = $parentModel->hasMany(TestPostModel::class);

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(TestPostModel::class, $relation->getRelatedClass());
        $this->assertEquals($parentModel, $relation->getParent());
    }

    public function testHasManyRelationshipWithCustomKeys(): void
    {
        $parentModel = new TestUserModel();
        $parentModel->id = '123';

        $relation = $parentModel->hasMany(TestPostModel::class, 'custom_fk', 'custom_local_key');

        $this->assertInstanceOf(HasMany::class, $relation);
    }
}
