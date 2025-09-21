<?php

namespace BaseApi\Tests;

use Override;
use InvalidArgumentException;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use BaseApi\Database\ModelQuery;
use BaseApi\Database\QueryBuilder;
use BaseApi\Database\PaginatedResult;
use BaseApi\Models\BaseModel;

// Test models for ModelQuery testing
class TestAuthor extends BaseModel
{
    public string $id = '';

    public string $name = '';

    public string $email = '';

    /** @var TestBook[] */
    public array $books = [];
}

class TestBook extends BaseModel
{
    public string $id = '';

    public string $title = '';

    public string $author_id = '';

    public ?TestAuthor $author = null;
}

class ModelQueryTest extends TestCase
{
    private MockObject $qbMock;

    private ModelQuery $modelQuery;

    #[Override]
    protected function setUp(): void
    {
        $this->qbMock = $this->createMock(QueryBuilder::class);
        $this->modelQuery = new ModelQuery($this->qbMock, TestAuthor::class);

        // Set up common mock expectations for method chaining
        $this->qbMock->method('where')->willReturn($this->qbMock);
        $this->qbMock->method('whereIn')->willReturn($this->qbMock);
        $this->qbMock->method('orWhere')->willReturn($this->qbMock);
        $this->qbMock->method('whereNull')->willReturn($this->qbMock);
        $this->qbMock->method('whereNotNull')->willReturn($this->qbMock);
        $this->qbMock->method('orderBy')->willReturn($this->qbMock);
        $this->qbMock->method('limit')->willReturn($this->qbMock);
        $this->qbMock->method('offset')->willReturn($this->qbMock);
    }

    public function testWhereMethod(): void
    {
        $this->qbMock->expects($this->once())
            ->method('where')
            ->with('name', '=', 'John Doe')
            ->willReturn($this->qbMock);

        $result = $this->modelQuery->where('name', '=', 'John Doe');

        $this->assertSame($this->modelQuery, $result);
    }

    public function testWhereInMethod(): void
    {
        $values = ['1', '2', '3'];

        $this->qbMock->expects($this->once())
            ->method('whereIn')
            ->with('id', $values)
            ->willReturn($this->qbMock);

        $result = $this->modelQuery->whereIn('id', $values);

        $this->assertSame($this->modelQuery, $result);
    }

    public function testOrWhereMethod(): void
    {
        $this->qbMock->expects($this->once())
            ->method('orWhere')
            ->with('email', 'LIKE', '%@example.com')
            ->willReturn($this->qbMock);

        $result = $this->modelQuery->orWhere('email', 'LIKE', '%@example.com');

        $this->assertSame($this->modelQuery, $result);
    }

    public function testWhereNullMethod(): void
    {
        $this->qbMock->expects($this->once())
            ->method('whereNull')
            ->with('deleted_at')
            ->willReturn($this->qbMock);

        $result = $this->modelQuery->whereNull('deleted_at');

        $this->assertSame($this->modelQuery, $result);
    }

    public function testWhereNotNullMethod(): void
    {
        $this->qbMock->expects($this->once())
            ->method('whereNotNull')
            ->with('email')
            ->willReturn($this->qbMock);

        $result = $this->modelQuery->whereNotNull('email');

        $this->assertSame($this->modelQuery, $result);
    }

    public function testOrderByMethod(): void
    {
        $this->qbMock->expects($this->once())
            ->method('orderBy')
            ->with('name', 'asc')
            ->willReturn($this->qbMock);

        $result = $this->modelQuery->orderBy('name', 'asc');

        $this->assertSame($this->modelQuery, $result);
    }

    public function testOrderByMethodDefaultDirection(): void
    {
        $this->qbMock->expects($this->once())
            ->method('orderBy')
            ->with('name', 'asc')
            ->willReturn($this->qbMock);

        $result = $this->modelQuery->orderBy('name');

        $this->assertSame($this->modelQuery, $result);
    }

    public function testLimitMethod(): void
    {
        $this->qbMock->expects($this->once())
            ->method('limit')
            ->with(10)
            ->willReturn($this->qbMock);

        $result = $this->modelQuery->limit(10);

        $this->assertSame($this->modelQuery, $result);
    }

    public function testOffsetMethod(): void
    {
        $this->qbMock->expects($this->once())
            ->method('offset')
            ->with(20)
            ->willReturn($this->qbMock);

        $result = $this->modelQuery->offset(20);

        $this->assertSame($this->modelQuery, $result);
    }

    public function testWithMethod(): void
    {
        $relations = ['books', 'profile'];

        $result = $this->modelQuery->with($relations);

        $this->assertSame($this->modelQuery, $result);
    }

    public function testWithMethodLimitsRelations(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximum 5 relations allowed');

        $relations = ['rel1', 'rel2', 'rel3', 'rel4', 'rel5', 'rel6'];
        $this->modelQuery->with($relations);
    }

    public function testWithMethodMergesRelations(): void
    {
        $this->modelQuery->with(['books']);
        $result = $this->modelQuery->with(['profile']);

        $this->assertSame($this->modelQuery, $result);
        // Relations should be merged (tested via pagination which uses eager loading)
    }

    public function testQbMethod(): void
    {
        $result = $this->modelQuery->qb();

        $this->assertSame($this->qbMock, $result);
    }

    public function testCacheMethod(): void
    {
        $result = $this->modelQuery->cache(300, 'custom-key');

        $this->assertSame($this->modelQuery, $result);
    }

    public function testCacheMethodWithDefaultValues(): void
    {
        $result = $this->modelQuery->cache();

        $this->assertSame($this->modelQuery, $result);
    }

    public function testCacheWithTagsMethod(): void
    {
        $tags = ['users', 'active'];

        $result = $this->modelQuery->cacheWithTags($tags, 600);

        $this->assertSame($this->modelQuery, $result);
    }

    public function testNoCacheMethod(): void
    {
        $result = $this->modelQuery->noCache();

        $this->assertSame($this->modelQuery, $result);
    }

    public function testGetCacheKey(): void
    {
        // Set up mock to return consistent values
        $this->qbMock->method('toSql')->willReturn([
            'sql' => 'SELECT * FROM users WHERE id = ?',
            'bindings' => ['123']
        ]);

        $cacheKey = $this->modelQuery->getCacheKey();

        $this->assertIsString($cacheKey);
        $this->assertStringStartsWith('query:', $cacheKey);
    }

    public function testGetCacheKeyWithCustomKey(): void
    {
        $this->modelQuery->cache(300, 'my-custom-key');

        $cacheKey = $this->modelQuery->getCacheKey();

        $this->assertEquals('my-custom-key', $cacheKey);
    }

    public function testGetCacheKeyIncludesRelations(): void
    {
        $this->modelQuery->with(['books']);

        // Set up mock to return consistent values
        $this->qbMock->method('toSql')->willReturn([
            'sql' => 'SELECT * FROM users',
            'bindings' => []
        ]);

        $cacheKeyWithRelations = $this->modelQuery->getCacheKey();

        // Create another query without relations for comparison
        $modelQuery2 = new ModelQuery($this->qbMock, TestAuthor::class);
        $cacheKeyWithoutRelations = $modelQuery2->getCacheKey();

        $this->assertNotEquals($cacheKeyWithoutRelations, $cacheKeyWithRelations);
    }

    public function testPaginateWithMaxPerPage(): void
    {
        $mockResult = new PaginatedResult([], 1, 10, null);

        $this->qbMock->expects($this->once())
            ->method('paginate')
            ->with(1, 5, false) // maxPerPage should limit perPage
            ->willReturn($mockResult);

        $this->qbMock->method('get')->willReturn([]);

        $result = $this->modelQuery->paginate(1, 10, 5, false);

        $this->assertInstanceOf(PaginatedResult::class, $result);
    }

    public function testPaginateWithoutMaxPerPage(): void
    {
        $mockResult = new PaginatedResult([], 1, 10, null);

        $this->qbMock->expects($this->once())
            ->method('paginate')
            ->with(1, 10, false)
            ->willReturn($mockResult);

        $this->qbMock->method('get')->willReturn([]);

        $result = $this->modelQuery->paginate(1, 10, null, false);

        $this->assertInstanceOf(PaginatedResult::class, $result);
    }

    public function testPaginateWithTotal(): void
    {
        $mockResult = new PaginatedResult([], 1, 10, 100);

        $this->qbMock->expects($this->once())
            ->method('paginate')
            ->with(1, 10, true)
            ->willReturn($mockResult);

        $this->qbMock->method('get')->willReturn([]);

        $result = $this->modelQuery->paginate(1, 10, null, true);

        $this->assertInstanceOf(PaginatedResult::class, $result);
        $this->assertEquals(100, $result->total);
    }

    public function testMethodChaining(): void
    {
        $result = $this->modelQuery
            ->where('name', '=', 'John')
            ->whereIn('status', ['active', 'pending'])
            ->orWhere('role', '=', 'admin')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->offset(5)
            ->with(['books']);

        $this->assertSame($this->modelQuery, $result);
    }

    public function testComplexQueryBuilding(): void
    {
        $this->qbMock->expects($this->once())
            ->method('where')
            ->with('status', '=', 'active');

        $this->qbMock->expects($this->once())
            ->method('whereIn')
            ->with('category_id', [1, 2, 3]);

        $this->qbMock->expects($this->once())
            ->method('orWhere')
            ->with('priority', '=', 'high');

        $this->qbMock->expects($this->once())
            ->method('whereNull')
            ->with('deleted_at');

        $this->qbMock->expects($this->once())
            ->method('orderBy')
            ->with('created_at', 'desc');

        $this->qbMock->expects($this->once())
            ->method('limit')
            ->with(20);

        $this->qbMock->expects($this->once())
            ->method('offset')
            ->with(40);

        $this->modelQuery
            ->where('status', '=', 'active')
            ->whereIn('category_id', [1, 2, 3])
            ->orWhere('priority', '=', 'high')
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->offset(40)
            ->with(['books', 'author']);
    }

    // Note: Tests for get() and first() methods would require mocking
    // the actual database interaction and model hydration, which is
    // complex and better tested as integration tests. The core query
    // building functionality is tested above.

    public function testModelClassIsStored(): void
    {
        $reflection = new ReflectionClass($this->modelQuery);
        $modelClassProperty = $reflection->getProperty('modelClass');
        $modelClassProperty->setAccessible(true);

        $this->assertEquals(TestAuthor::class, $modelClassProperty->getValue($this->modelQuery));
    }

    public function testQueryBuilderIsStored(): void
    {
        $reflection = new ReflectionClass($this->modelQuery);
        $qbProperty = $reflection->getProperty('qb');
        $qbProperty->setAccessible(true);

        $this->assertSame($this->qbMock, $qbProperty->getValue($this->modelQuery));
    }

    public function testEagerRelationsStorageAndMerging(): void
    {
        $this->modelQuery->with(['books']);
        $this->modelQuery->with(['profile', 'settings']);

        $reflection = new ReflectionClass($this->modelQuery);
        $relationsProperty = $reflection->getProperty('eagerRelations');
        $relationsProperty->setAccessible(true);

        $relations = $relationsProperty->getValue($this->modelQuery);

        $this->assertEquals(['books', 'profile', 'settings'], $relations);
    }

    public function testCachePropertiesAreSet(): void
    {
        $this->modelQuery->cacheWithTags(['tag1', 'tag2'], 300);

        $reflection = new ReflectionClass($this->modelQuery);

        $tagsProperty = $reflection->getProperty('cacheTags');
        $tagsProperty->setAccessible(true);
        $this->assertEquals(['tag1', 'tag2'], $tagsProperty->getValue($this->modelQuery));

        $ttlProperty = $reflection->getProperty('cacheTtl');
        $ttlProperty->setAccessible(true);
        $this->assertEquals(300, $ttlProperty->getValue($this->modelQuery));

        $enabledProperty = $reflection->getProperty('cacheEnabled');
        $enabledProperty->setAccessible(true);
        $this->assertTrue($enabledProperty->getValue($this->modelQuery));
    }

    public function testNoCacheDisablesCaching(): void
    {
        $this->modelQuery->cache(300);
        $this->modelQuery->noCache();

        $reflection = new ReflectionClass($this->modelQuery);
        $enabledProperty = $reflection->getProperty('cacheEnabled');
        $enabledProperty->setAccessible(true);

        $this->assertFalse($enabledProperty->getValue($this->modelQuery));
    }
}
