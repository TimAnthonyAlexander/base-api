<?php

namespace BaseApi\Tests;

use PHPUnit\Framework\TestCase;
use BaseApi\Database\PaginatedResult;

class PaginatedResultTest extends TestCase
{
    public function testConstructorSetsPropertiesCorrectly(): void
    {
        $data = ['item1', 'item2', 'item3'];
        $page = 2;
        $perPage = 10;
        $total = 25;

        $result = new PaginatedResult($data, $page, $perPage, $total);

        $this->assertEquals($data, $result->data);
        $this->assertEquals($page, $result->page);
        $this->assertEquals($perPage, $result->perPage);
        $this->assertEquals($total, $result->total);
    }

    public function testConstructorCalculatesRemainingCorrectly(): void
    {
        // Test with remaining items
        $result = new PaginatedResult(['item1'], 1, 10, 25);
        $this->assertEquals(15, $result->remaining); // 25 - (1 * 10) = 15

        // Test with no remaining items
        $result = new PaginatedResult(['item1'], 3, 10, 25);
        $this->assertEquals(0, $result->remaining); // 25 - (3 * 10) = -5, but max(0, -5) = 0

        // Test exact match
        $result = new PaginatedResult(['item1'], 2, 10, 20);
        $this->assertEquals(0, $result->remaining); // 20 - (2 * 10) = 0
    }

    public function testConstructorWithNullTotal(): void
    {
        $data = ['item1', 'item2'];
        $page = 1;
        $perPage = 10;

        $result = new PaginatedResult($data, $page, $perPage);

        $this->assertEquals($data, $result->data);
        $this->assertEquals($page, $result->page);
        $this->assertEquals($perPage, $result->perPage);
        $this->assertNull($result->total);
        $this->assertNull($result->remaining);
    }

    public function testConstructorWithNullTotalExplicit(): void
    {
        $data = ['item1', 'item2'];
        $page = 1;
        $perPage = 10;
        $total = null;

        $result = new PaginatedResult($data, $page, $perPage, $total);

        $this->assertEquals($data, $result->data);
        $this->assertEquals($page, $result->page);
        $this->assertEquals($perPage, $result->perPage);
        $this->assertNull($result->total);
        $this->assertNull($result->remaining);
    }

    public function testHeadersWithTotal(): void
    {
        $result = new PaginatedResult(['item1'], 2, 15, 100);

        $headers = $result->headers();

        $expectedHeaders = [
            'X-Page' => '2',
            'X-Per-Page' => '15',
            'X-Total' => '100',
        ];

        $this->assertEquals($expectedHeaders, $headers);
    }

    public function testHeadersWithoutTotal(): void
    {
        $result = new PaginatedResult(['item1'], 3, 20);

        $headers = $result->headers();

        $expectedHeaders = [
            'X-Page' => '3',
            'X-Per-Page' => '20',
        ];

        $this->assertEquals($expectedHeaders, $headers);
    }

    public function testHeadersConvertsToStrings(): void
    {
        $result = new PaginatedResult(['item1'], 1, 10, 50);

        $headers = $result->headers();

        // Verify all header values are strings
        foreach ($headers as $value) {
            $this->assertIsString($value);
        }

        $this->assertEquals('1', $headers['X-Page']);
        $this->assertEquals('10', $headers['X-Per-Page']);
        $this->assertEquals('50', $headers['X-Total']);
    }

    public function testWithEmptyData(): void
    {
        $result = new PaginatedResult([], 1, 10, 0);

        $this->assertEquals([], $result->data);
        $this->assertEquals(1, $result->page);
        $this->assertEquals(10, $result->perPage);
        $this->assertEquals(0, $result->total);
        $this->assertEquals(0, $result->remaining); // max(0, 0 - (1 * 10)) = 0
    }

    public function testWithLargeNumbers(): void
    {
        $result = new PaginatedResult(['item'], 100, 50, 10000);

        $this->assertEquals(['item'], $result->data);
        $this->assertEquals(100, $result->page);
        $this->assertEquals(50, $result->perPage);
        $this->assertEquals(10000, $result->total);
        $this->assertEquals(5000, $result->remaining); // 10000 - (100 * 50) = 5000
    }

    public function testRemainingNeverNegative(): void
    {
        // Test cases where calculated remaining would be negative
        $result = new PaginatedResult(['item'], 10, 20, 100);
        $this->assertEquals(0, $result->remaining); // max(0, 100 - (10 * 20)) = max(0, -100) = 0

        $result = new PaginatedResult(['item'], 5, 10, 30);
        $this->assertEquals(0, $result->remaining); // max(0, 30 - (5 * 10)) = max(0, -20) = 0
    }
}
