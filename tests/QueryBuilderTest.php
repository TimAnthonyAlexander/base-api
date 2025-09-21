<?php

namespace BaseApi\Tests;

use Override;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use BaseApi\Database\QueryBuilder;
use BaseApi\Database\Connection;
use BaseApi\Database\DbException;

class QueryBuilderTest extends TestCase
{
    private MockObject $connectionMock;

    private QueryBuilder $queryBuilder;

    #[Override]
    protected function setUp(): void
    {
        $this->connectionMock = $this->createMock(Connection::class);
        $this->queryBuilder = new QueryBuilder($this->connectionMock);
    }

    public function testTableMethod(): void
    {
        $result = $this->queryBuilder->table('users');

        $this->assertInstanceOf(QueryBuilder::class, $result);
        $this->assertSame($this->queryBuilder, $result); // Should return same instance for fluent interface
    }

    public function testTableMethodResetsQueryState(): void
    {
        // Set up initial query state
        $this->queryBuilder
            ->table('users')
            ->select(['name', 'email'])
            ->where('active', '=', true)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->offset(5);

        // Set new table should reset state
        $this->queryBuilder->table('posts');

        $sqlArray = $this->queryBuilder->toSql();

        // Should not contain previous query parts
        $this->assertStringContainsString('SELECT * FROM `posts`', $sqlArray['sql']);
        $this->assertStringNotContainsString('WHERE', $sqlArray['sql']);
        $this->assertStringNotContainsString('ORDER BY', $sqlArray['sql']);
        $this->assertStringNotContainsString('LIMIT', $sqlArray['sql']);
        $this->assertStringNotContainsString('OFFSET', $sqlArray['sql']);
    }

    public function testSelectWithSingleColumn(): void
    {
        $this->queryBuilder->table('users')->select('name');

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('SELECT `name` FROM `users`', $sqlArray['sql']);
    }

    public function testSelectWithMultipleColumns(): void
    {
        $this->queryBuilder->table('users')->select(['name', 'email', 'created_at']);

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('SELECT `name`, `email`, `created_at` FROM `users`', $sqlArray['sql']);
    }

    public function testSelectWithAsterisk(): void
    {
        $this->queryBuilder->table('users')->select('*');

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('SELECT * FROM `users`', $sqlArray['sql']);
    }

    public function testWhereBasicConditions(): void
    {
        $this->queryBuilder
            ->table('users')
            ->where('id', '=', 1)
            ->where('status', '!=', 'inactive')
            ->where('age', '>', 18)
            ->where('age', '<=', 65)
            ->where('name', 'LIKE', 'John%');

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('WHERE `id` = ? AND `status` != ? AND `age` > ? AND `age` <= ? AND `name` LIKE ?', $sqlArray['sql']);
        $this->assertEquals([1, 'inactive', 18, 65, 'John%'], $sqlArray['bindings']);
    }

    public function testWhereInCondition(): void
    {
        $this->queryBuilder
            ->table('users')
            ->where('id', 'IN', [1, 2, 3, 4]);

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('WHERE `id` IN (?, ?, ?, ?)', $sqlArray['sql']);
        $this->assertEquals([1, 2, 3, 4], $sqlArray['bindings']);
    }

    public function testWhereInWithEmptyArrayThrowsException(): void
    {
        $this->expectException(DbException::class);
        $this->expectExceptionMessage('IN requires a non-empty array');

        $this->queryBuilder
            ->table('users')
            ->where('id', 'IN', []);
    }

    public function testWhereInvalidOperatorThrowsException(): void
    {
        $this->expectException(DbException::class);
        $this->expectExceptionMessage('Invalid operator: INVALID');

        $this->queryBuilder
            ->table('users')
            ->where('id', 'INVALID', 1);
    }

    public function testOrWhereConditions(): void
    {
        $this->queryBuilder
            ->table('users')
            ->where('status', '=', 'active')
            ->orWhere('status', '=', 'pending')
            ->orWhere('role', '=', 'admin');

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('WHERE `status` = ? OR `status` = ? OR `role` = ?', $sqlArray['sql']);
        $this->assertEquals(['active', 'pending', 'admin'], $sqlArray['bindings']);
    }

    public function testWhereNullConditions(): void
    {
        $this->queryBuilder
            ->table('users')
            ->whereNull('deleted_at')
            ->whereNotNull('email');

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('WHERE `deleted_at` IS NULL AND `email` IS NOT NULL', $sqlArray['sql']);
        $this->assertEquals([], $sqlArray['bindings']); // NULL conditions don't need bindings
    }

    public function testOrWhereNullConditions(): void
    {
        $this->queryBuilder
            ->table('users')
            ->where('status', '=', 'active')
            ->orWhereNull('deleted_at')
            ->orWhereNotNull('activated_at');

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('WHERE `status` = ? OR `deleted_at` IS NULL OR `activated_at` IS NOT NULL', $sqlArray['sql']);
        $this->assertEquals(['active'], $sqlArray['bindings']);
    }

    public function testWhereBetweenConditions(): void
    {
        $this->queryBuilder
            ->table('users')
            ->whereBetween('age', 18, 65)
            ->orWhereBetween('score', 80, 100);

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('WHERE `age` BETWEEN ? AND ? OR `score` BETWEEN ? AND ?', $sqlArray['sql']);
        $this->assertEquals([18, 65, 80, 100], $sqlArray['bindings']);
    }

    public function testWhereNotInConditions(): void
    {
        $this->queryBuilder
            ->table('users')
            ->whereNotIn('status', ['banned', 'suspended'])
            ->orWhereNotIn('role', ['guest']);

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('WHERE `status` NOT IN (?, ?) OR `role` NOT IN (?)', $sqlArray['sql']);
        $this->assertEquals(['banned', 'suspended', 'guest'], $sqlArray['bindings']);
    }

    public function testWhereNotInWithEmptyArrayThrowsException(): void
    {
        $this->expectException(DbException::class);
        $this->expectExceptionMessage('NOT IN requires a non-empty array');

        $this->queryBuilder
            ->table('users')
            ->whereNotIn('id', []);
    }

    public function testWhereGroupConditions(): void
    {
        $this->queryBuilder
            ->table('users')
            ->where('status', '=', 'active')
            ->whereGroup(function (QueryBuilder $q): void {
                $q->where('role', '=', 'admin')->orWhere('role', '=', 'moderator');
            });

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('WHERE `status` = ? AND (`role` = ? OR `role` = ?)', $sqlArray['sql']);
        $this->assertEquals(['active', 'admin', 'moderator'], $sqlArray['bindings']);
    }

    public function testOrWhereGroupConditions(): void
    {
        $this->queryBuilder
            ->table('users')
            ->where('status', '=', 'active')
            ->orWhereGroup(function (QueryBuilder $q): void {
                $q->where('role', '=', 'admin')->where('permissions', 'LIKE', '%manage%');
            });

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('WHERE `status` = ? OR (`role` = ? AND `permissions` LIKE ?)', $sqlArray['sql']);
        $this->assertEquals(['active', 'admin', '%manage%'], $sqlArray['bindings']);
    }

    public function testWhereConditionsArray(): void
    {
        $conditions = [
            ['column' => 'name', 'operator' => 'LIKE', 'value' => 'John%'],
            ['column' => 'age', 'operator' => '>', 'value' => 18],
            ['column' => 'status', 'value' => 'active'] // Default operator should be =
        ];

        $this->queryBuilder->table('users')->whereConditions($conditions);

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('WHERE `name` LIKE ? AND `age` > ? AND `status` = ?', $sqlArray['sql']);
        $this->assertEquals(['John%', 18, 'active'], $sqlArray['bindings']);
    }

    public function testWhereConditionsInvalidThrowsException(): void
    {
        $this->expectException(DbException::class);
        $this->expectExceptionMessage('Invalid where condition');

        $conditions = [
            ['column' => 'name'] // Missing value
        ];

        $this->queryBuilder->table('users')->whereConditions($conditions);
    }

    public function testWhereInWithEmptyArrayGeneratesImpossibleCondition(): void
    {
        $this->queryBuilder
            ->table('users')
            ->whereIn('id', []);

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('WHERE 1 = 0', $sqlArray['sql']); // Impossible condition
        $this->assertEquals([], $sqlArray['bindings']);
    }

    public function testOrderByConditions(): void
    {
        $this->queryBuilder
            ->table('users')
            ->orderBy('name', 'asc')
            ->orderBy('created_at', 'desc')
            ->orderBy('email'); // Should default to ASC

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('ORDER BY `name` ASC, `created_at` DESC, `email` ASC', $sqlArray['sql']);
    }

    public function testJoinConditions(): void
    {
        $this->queryBuilder
            ->table('users')
            ->join('posts', 'users.id', '=', 'posts.user_id')
            ->leftJoin('comments', 'posts.id', '=', 'comments.post_id')
            ->rightJoin('categories', 'posts.category_id', '=', 'categories.id');

        $sqlArray = $this->queryBuilder->toSql();

        $expected = 'INNER JOIN `posts` ON `users`.`id` = `posts`.`user_id` LEFT JOIN `comments` ON `posts`.`id` = `comments`.`post_id` RIGHT JOIN `categories` ON `posts`.`category_id` = `categories`.`id`';
        $this->assertStringContainsString($expected, $sqlArray['sql']);
    }

    public function testCrossJoin(): void
    {
        $this->queryBuilder
            ->table('users')
            ->crossJoin('settings');

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('CROSS JOIN `settings`', $sqlArray['sql']);
    }

    public function testJoinWithInvalidTypeThrowsException(): void
    {
        $this->expectException(DbException::class);
        $this->expectExceptionMessage('Invalid join type: INVALID');

        $this->queryBuilder
            ->table('users')
            ->join('posts', 'users.id', '=', 'posts.user_id', 'INVALID');
    }

    public function testJoinWithInvalidOperatorThrowsException(): void
    {
        $this->expectException(DbException::class);
        $this->expectExceptionMessage('Invalid join operator: INVALID');

        $this->queryBuilder
            ->table('users')
            ->join('posts', 'users.id', 'INVALID', 'posts.user_id');
    }

    public function testLimitAndOffset(): void
    {
        $this->queryBuilder
            ->table('users')
            ->limit(10)
            ->offset(20);

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('LIMIT 10 OFFSET 20', $sqlArray['sql']);
    }

    public function testLimitWithNegativeValueBecomesZero(): void
    {
        $this->queryBuilder
            ->table('users')
            ->limit(-5);

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('LIMIT 0', $sqlArray['sql']);
    }

    public function testOffsetWithNegativeValueBecomesZero(): void
    {
        $this->queryBuilder
            ->table('users')
            ->offset(-10);

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('OFFSET 0', $sqlArray['sql']);
    }

    public function testComplexSelectQuery(): void
    {
        $this->queryBuilder
            ->table('users')
            ->select(['users.name', 'users.email', 'posts.title'])
            ->join('posts', 'users.id', '=', 'posts.user_id')
            ->where('users.status', '=', 'active')
            ->whereGroup(function (QueryBuilder $q): void {
                $q->where('posts.published', '=', true)
                  ->orWhere('posts.scheduled_at', '<=', date('Y-m-d H:i:s'));
            })
            ->orderBy('users.created_at', 'desc')
            ->limit(50)
            ->offset(100);

        $sqlArray = $this->queryBuilder->toSql();

        $expectedContains = [
            'SELECT `users`.`name`, `users`.`email`, `posts`.`title`',
            'FROM `users`',
            'INNER JOIN `posts` ON `users`.`id` = `posts`.`user_id`',
            'WHERE `users`.`status` = ? AND (`posts`.`published` = ? OR `posts`.`scheduled_at` <= ?)',
            'ORDER BY `users`.`created_at` DESC',
            'LIMIT 50 OFFSET 100'
        ];

        foreach ($expectedContains as $expected) {
            $this->assertStringContainsString($expected, $sqlArray['sql']);
        }

        $this->assertCount(3, $sqlArray['bindings']);
    }

    public function testInsertThrowsExceptionWithEmptyData(): void
    {
        $this->expectException(DbException::class);
        $this->expectExceptionMessage('Insert data cannot be empty');

        $this->queryBuilder->table('users')->insert([]);
    }

    public function testInsertThrowsExceptionWithoutTable(): void
    {
        $this->expectException(DbException::class);
        $this->expectExceptionMessage('Table not specified');

        $this->queryBuilder->insert(['name' => 'John']);
    }

    public function testUpdateReturnsZeroWithEmptyData(): void
    {
        $result = $this->queryBuilder->table('users')->update([]);

        $this->assertEquals(0, $result);
    }

    public function testUpdateThrowsExceptionWithoutTable(): void
    {
        $this->expectException(DbException::class);
        $this->expectExceptionMessage('Table not specified');

        $this->queryBuilder->update(['name' => 'John']);
    }

    public function testDeleteThrowsExceptionWithoutTable(): void
    {
        $this->expectException(DbException::class);
        $this->expectExceptionMessage('Table not specified');

        $this->queryBuilder->delete();
    }

    // Count method tests removed - these require actual database execution
    // and are better tested as integration tests

    public function testApplySortStringValidInput(): void
    {
        $this->queryBuilder
            ->table('users')
            ->applySortString('name,-createdAt,email');

        $sqlArray = $this->queryBuilder->toSql();

        // Note: camelCase gets converted to snake_case
        $this->assertStringContainsString('ORDER BY `name` ASC, `created_at` DESC, `email` ASC', $sqlArray['sql']);
    }

    public function testApplySortStringIgnoresEmptyParts(): void
    {
        $this->queryBuilder
            ->table('users')
            ->applySortString('name,,email');

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('ORDER BY `name` ASC, `email` ASC', $sqlArray['sql']);
    }

    public function testApplyFiltersWithValidArray(): void
    {
        $filters = [
            'name' => 'John',
            'status' => 'active',
            'age' => '25'
        ];

        $this->queryBuilder
            ->table('users')
            ->applyFilters($filters);

        $sqlArray = $this->queryBuilder->toSql();

        $this->assertStringContainsString('WHERE `name` = ? AND `status` = ? AND `age` = ?', $sqlArray['sql']);
        $this->assertEquals(['John', 'active', '25'], $sqlArray['bindings']);
    }

    // Paginate method tests removed - these require actual database execution
    // and are better tested as integration tests

    public function testColumnNameSanitization(): void
    {
        // This tests the sanitization logic - identifiers are wrapped in backticks
        $this->queryBuilder
            ->table('users')
            ->select('firstName')
            ->where('lastName', '=', 'Doe');

        $sqlArray = $this->queryBuilder->toSql();

        // Column names should be wrapped in backticks
        $this->assertStringContainsString('SELECT `firstName` FROM `users`', $sqlArray['sql']);
        $this->assertStringContainsString('WHERE `lastName` = ?', $sqlArray['sql']);
    }
}
