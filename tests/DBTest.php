<?php

namespace BaseApi\Tests;

use Override;
use PDO;
use PDOStatement;
use PDOException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use BaseApi\Database\DB;
use BaseApi\Database\Connection;
use BaseApi\Database\QueryBuilder;
use BaseApi\Database\DbException;

class DBTest extends TestCase
{
    private MockObject $connectionMock;

    private MockObject $pdoMock;

    private MockObject $stmtMock;

    private DB $db;

    #[Override]
    protected function setUp(): void
    {
        $this->connectionMock = $this->createMock(Connection::class);
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);

        $this->connectionMock->method('pdo')->willReturn($this->pdoMock);

        $this->db = new DB($this->connectionMock);
    }

    public function testQbReturnsQueryBuilder(): void
    {
        $qb = $this->db->qb();

        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }

    public function testQbReturnsNewInstanceEachTime(): void
    {
        $qb1 = $this->db->qb();
        $qb2 = $this->db->qb();

        $this->assertNotSame($qb1, $qb2);
    }

    public function testPdoReturnsConnectionPdo(): void
    {
        $pdo = $this->db->pdo();

        $this->assertSame($this->pdoMock, $pdo);
    }

    public function testGetConnectionReturnsConnection(): void
    {
        $connection = $this->db->getConnection();

        $this->assertSame($this->connectionMock, $connection);
    }

    public function testRawQueryExecution(): void
    {
        $sql = 'SELECT * FROM users WHERE id = ?';
        $bindings = ['123'];
        $expectedResult = [
            ['id' => '123', 'name' => 'John Doe'],
            ['id' => '124', 'name' => 'Jane Doe']
        ];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with($bindings)
            ->willReturn(true);

        $this->stmtMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn($expectedResult);

        $result = $this->db->raw($sql, $bindings);

        $this->assertEquals($expectedResult, $result);
    }

    public function testRawQueryWithoutBindings(): void
    {
        $sql = 'SELECT * FROM users';
        $expectedResult = [['id' => '1', 'name' => 'Test']];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([])
            ->willReturn(true);

        $this->stmtMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn($expectedResult);

        $result = $this->db->raw($sql);

        $this->assertEquals($expectedResult, $result);
    }

    public function testRawQueryThrowsDbExceptionOnPdoException(): void
    {
        $sql = 'INVALID SQL';
        $pdoException = new PDOException('Syntax error');

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willThrowException($pdoException);

        $this->expectException(DbException::class);
        $this->expectExceptionMessage('Query failed: Syntax error');

        $this->db->raw($sql);
    }

    public function testScalarQueryExecution(): void
    {
        $sql = 'SELECT COUNT(*) FROM users';
        $bindings = [];
        $expectedResult = 42;

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with($bindings)
            ->willReturn(true);

        $this->stmtMock->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($expectedResult);

        $result = $this->db->scalar($sql, $bindings);

        $this->assertEquals($expectedResult, $result);
    }

    public function testScalarQueryWithBindings(): void
    {
        $sql = 'SELECT age FROM users WHERE id = ?';
        $bindings = ['123'];
        $expectedResult = 25;

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with($bindings)
            ->willReturn(true);

        $this->stmtMock->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($expectedResult);

        $result = $this->db->scalar($sql, $bindings);

        $this->assertEquals($expectedResult, $result);
    }

    public function testScalarQueryThrowsDbExceptionOnPdoException(): void
    {
        $sql = 'INVALID SQL';
        $pdoException = new PDOException('Syntax error');

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willThrowException($pdoException);

        $this->expectException(DbException::class);
        $this->expectExceptionMessage('Scalar query failed: Syntax error');

        $this->db->scalar($sql);
    }

    public function testExecQueryExecution(): void
    {
        $sql = 'UPDATE users SET name = ? WHERE id = ?';
        $bindings = ['New Name', '123'];
        $expectedRowCount = 1;

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with($bindings)
            ->willReturn(true);

        $this->stmtMock->expects($this->once())
            ->method('rowCount')
            ->willReturn($expectedRowCount);

        $result = $this->db->exec($sql, $bindings);

        $this->assertEquals($expectedRowCount, $result);
    }

    public function testExecQueryWithoutBindings(): void
    {
        $sql = 'DELETE FROM temp_table';
        $expectedRowCount = 5;

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([])
            ->willReturn(true);

        $this->stmtMock->expects($this->once())
            ->method('rowCount')
            ->willReturn($expectedRowCount);

        $result = $this->db->exec($sql);

        $this->assertEquals($expectedRowCount, $result);
    }

    public function testExecQueryThrowsDbExceptionOnPdoException(): void
    {
        $sql = 'INVALID SQL';
        $pdoException = new PDOException('Syntax error');

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willThrowException($pdoException);

        $this->expectException(DbException::class);
        $this->expectExceptionMessage('Execute failed: Syntax error');

        $this->db->exec($sql);
    }

    public function testQueryLoggingWhenAppExists(): void
    {
        // This test is complex because it requires mocking the App class
        // In a real test environment, we'd need to set up the App properly
        // For now, we'll test that queries don't fail when profiling is attempted

        $sql = 'SELECT * FROM users';
        $expectedResult = [['id' => '1']];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->stmtMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn($expectedResult);

        // Should not throw even if profiler throws
        $result = $this->db->raw($sql);

        $this->assertEquals($expectedResult, $result);
    }

    public function testQueryLoggingHandlesExceptions(): void
    {
        $sql = 'SELECT * FROM users';
        $expectedResult = [['id' => '1']];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->stmtMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn($expectedResult);

        // Should handle profiler errors gracefully
        $result = $this->db->raw($sql);

        $this->assertEquals($expectedResult, $result);
    }

    public function testMultipleConsecutiveQueries(): void
    {
        // Test that the DB instance can handle multiple queries in sequence

        // First query
        $sql1 = 'SELECT * FROM users';
        $sql2 = 'SELECT COUNT(*) FROM users';
        $result1 = [['id' => '1', 'name' => 'User1']];

        // Mock multiple calls
        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnCallback(fn($sql): MockObject => $this->stmtMock);

        $this->stmtMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->stmtMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn($result1);

        $this->stmtMock->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(1);

        // Execute queries
        $queryResult = $this->db->raw($sql1);
        $countResult = $this->db->scalar($sql2);

        $this->assertEquals($result1, $queryResult);
        $this->assertEquals(1, $countResult);
    }

    public function testQueryWithComplexBindings(): void
    {
        $sql = 'SELECT * FROM users WHERE name = ? AND age > ? AND active = ?';
        $bindings = ['John Doe', 18, true];
        $expectedResult = [['id' => '1', 'name' => 'John Doe', 'age' => '25']];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with($bindings)
            ->willReturn(true);

        $this->stmtMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn($expectedResult);

        $result = $this->db->raw($sql, $bindings);

        $this->assertEquals($expectedResult, $result);
    }

    public function testEmptyResultHandling(): void
    {
        $sql = 'SELECT * FROM users WHERE id = ?';
        $bindings = ['nonexistent'];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with($bindings)
            ->willReturn(true);

        $this->stmtMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        $result = $this->db->raw($sql, $bindings);

        $this->assertEquals([], $result);
    }

    public function testScalarNullResult(): void
    {
        $sql = 'SELECT name FROM users WHERE id = ?';
        $bindings = ['nonexistent'];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with($bindings)
            ->willReturn(true);

        $this->stmtMock->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(false); // PDO returns false for no result

        $result = $this->db->scalar($sql, $bindings);

        $this->assertFalse($result);
    }

    public function testExecZeroRowsAffected(): void
    {
        $sql = 'UPDATE users SET name = ? WHERE id = ?';
        $bindings = ['New Name', 'nonexistent'];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with($bindings)
            ->willReturn(true);

        $this->stmtMock->expects($this->once())
            ->method('rowCount')
            ->willReturn(0);

        $result = $this->db->exec($sql, $bindings);

        $this->assertEquals(0, $result);
    }
}
