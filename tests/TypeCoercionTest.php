<?php

namespace BaseApi\Tests;

use ReflectionClass;
use ReflectionUnionType;
use ReflectionNamedType;
use PHPUnit\Framework\TestCase;
use BaseApi\Http\Binding\TypeCoercion;
use BaseApi\Http\UploadedFile;

class TypeCoercionTest extends TestCase
{
    public function testCoerceWithNullType(): void
    {
        $value = 'test_value';
        $result = TypeCoercion::coerce($value, null);
        
        $this->assertEquals($value, $result);
    }

    public function testCoerceWithUnionType(): void
    {
        $reflectionClass = new ReflectionClass(TestClassWithUnionType::class);
        $property = $reflectionClass->getProperty('stringOrNull');
        $unionType = $property->getType();

        $result = TypeCoercion::coerce(123, $unionType);
        
        // Should coerce to string (first non-null type)
        $this->assertEquals('123', $result);
    }

    public function testCoerceWithUnionTypeAllNull(): void
    {
        // Create a mock union type with only null types
        $mockUnionType = $this->createMockUnionTypeWithOnlyNull();
        
        $value = 'test_value';
        $result = TypeCoercion::coerce($value, $mockUnionType);
        
        $this->assertEquals($value, $result);
    }

    public function testCoerceToString(): void
    {
        $reflectionClass = new ReflectionClass(TestClassForTypeCoercion::class);
        $property = $reflectionClass->getProperty('stringProp');
        $type = $property->getType();

        // Test individual cases to avoid float key issues
        $this->assertEquals('123', TypeCoercion::coerce(123, $type));
        $this->assertEquals('45.67', TypeCoercion::coerce(45.67, $type));
        $this->assertEquals('1', TypeCoercion::coerce(true, $type));
        $this->assertEquals('', TypeCoercion::coerce(false, $type));
        $this->assertEquals('already_string', TypeCoercion::coerce('already_string', $type));
    }

    public function testCoerceToInt(): void
    {
        $reflectionClass = new ReflectionClass(TestClassForTypeCoercion::class);
        $property = $reflectionClass->getProperty('intProp');
        $type = $property->getType();

        // Test individual cases to avoid float key issues
        $this->assertEquals(123, TypeCoercion::coerce(123, $type));
        $this->assertEquals(456, TypeCoercion::coerce('456', $type));
        $this->assertEquals(789, TypeCoercion::coerce('789.5', $type)); // Numeric string gets converted
        $this->assertEquals('not_numeric', TypeCoercion::coerce('not_numeric', $type)); // Non-numeric stays unchanged
        $this->assertEquals(45.67, TypeCoercion::coerce(45.67, $type)); // Non-string stays unchanged
    }

    public function testCoerceToFloat(): void
    {
        $reflectionClass = new ReflectionClass(TestClassForTypeCoercion::class);
        $property = $reflectionClass->getProperty('floatProp');
        $type = $property->getType();

        // Test individual cases to avoid float key issues
        $this->assertEquals(123.0, TypeCoercion::coerce(123, $type));
        $this->assertEquals(456.78, TypeCoercion::coerce('456.78', $type));
        $this->assertEquals(789.0, TypeCoercion::coerce('789', $type));
        $this->assertEquals(45.67, TypeCoercion::coerce(45.67, $type));
        $this->assertEquals('not_numeric', TypeCoercion::coerce('not_numeric', $type)); // Non-numeric stays unchanged
    }

    public function testCoerceToBool(): void
    {
        $reflectionClass = new ReflectionClass(TestClassForTypeCoercion::class);
        $property = $reflectionClass->getProperty('boolProp');
        $type = $property->getType();

        $testCases = [
            true => true,
            false => false,
            'true' => true,
            'false' => false,
            '1' => true,
            '0' => false,
            'yes' => true,
            'no' => false,
            'on' => true,
            'off' => false,
            'invalid_bool' => 'invalid_bool', // Invalid bool strings stay unchanged
            123 => 123 // Non-string values stay unchanged
        ];

        foreach ($testCases as $input => $expected) {
            $result = TypeCoercion::coerce($input, $type);
            $this->assertEquals($expected, $result);
        }
    }

    public function testCoerceToArray(): void
    {
        $reflectionClass = new ReflectionClass(TestClassForTypeCoercion::class);
        $property = $reflectionClass->getProperty('arrayProp');
        $type = $property->getType();

        // Test array input (should remain unchanged)
        $arrayInput = [1, 2, 3];
        $result = TypeCoercion::coerce($arrayInput, $type);
        $this->assertEquals($arrayInput, $result);

        // Test non-array input (should remain unchanged - KISS approach)
        $nonArrayInput = 'not_array';
        $result = TypeCoercion::coerce($nonArrayInput, $type);
        $this->assertEquals($nonArrayInput, $result);
    }

    public function testCoerceToUploadedFile(): void
    {
        $reflectionClass = new ReflectionClass(TestClassForTypeCoercion::class);
        $property = $reflectionClass->getProperty('uploadedFileProp');
        $type = $property->getType();

        // Test valid file array
        $fileData = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/tmp/phpupload123',
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];

        $result = TypeCoercion::coerce($fileData, $type);
        $this->assertInstanceOf(UploadedFile::class, $result);

        // Test invalid file array (no tmp_name)
        $invalidFileData = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
        ];

        $result = TypeCoercion::coerce($invalidFileData, $type);
        $this->assertEquals($invalidFileData, $result);

        // Test non-array value
        $nonArrayValue = 'not_a_file';
        $result = TypeCoercion::coerce($nonArrayValue, $type);
        $this->assertEquals($nonArrayValue, $result);
    }

    public function testBoolStringToBool(): void
    {
        // Test boolean values (should remain unchanged)
        $this->assertTrue(TypeCoercion::boolStringToBool(true));
        $this->assertFalse(TypeCoercion::boolStringToBool(false));
        
        // Test string true values
        $trueValues = ['true', 'TRUE', 'True', '1', 'yes', 'YES', 'on', 'ON'];
        foreach ($trueValues as $value) {
            $result = TypeCoercion::boolStringToBool($value);
            $this->assertTrue($result, 'Failed for true value: ' . $value);
        }
        
        // Test string false values
        $falseValues = ['false', 'FALSE', 'False', '0', 'no', 'NO', 'off', 'OFF', ''];
        foreach ($falseValues as $value) {
            $result = TypeCoercion::boolStringToBool($value);
            $this->assertFalse($result, 'Failed for false value: ' . $value);
        }
        
        // Test invalid string values (should remain unchanged)
        $invalidValues = ['invalid', 'maybe'];
        foreach ($invalidValues as $value) {
            $result = TypeCoercion::boolStringToBool($value);
            $this->assertEquals($value, $result, 'Failed for invalid value: ' . $value);
        }
        
        // Test non-string values (should remain unchanged)
        $nonStringTestCases = [
            123 => 123,
            null => null
        ];
        
        foreach ($nonStringTestCases as $input => $expected) {
            $result = TypeCoercion::boolStringToBool($input);
            $this->assertEquals($expected, $result, "Failed for input: " . var_export($input, true));
        }
        
        // Test empty array (should remain unchanged)
        $emptyArray = [];
        $result = TypeCoercion::boolStringToBool($emptyArray);
        $this->assertEquals($emptyArray, $result);
    }

    public function testNumericToInt(): void
    {
        // Test integer values (should remain unchanged)
        $this->assertEquals(123, TypeCoercion::numericToInt(123));
        $this->assertEquals(-456, TypeCoercion::numericToInt(-456));
        
        // Test numeric strings (should be converted)
        $numericStringCases = [
            '789' => 789,
            '-123' => -123,
            '0' => 0,
        ];
        
        foreach ($numericStringCases as $input => $expected) {
            $result = TypeCoercion::numericToInt($input);
            $this->assertEquals($expected, $result, 'Failed for numeric string: ' . $input);
        }
        
        // Test non-numeric strings (should remain unchanged)
        $nonNumericStrings = ['abc', '123abc', ''];
        foreach ($nonNumericStrings as $input) {
            $result = TypeCoercion::numericToInt($input);
            $this->assertEquals($input, $result, 'Failed for non-numeric string: ' . $input);
        }
        
        // Test other types (should remain unchanged)
        $this->assertEquals(123.45, TypeCoercion::numericToInt(123.45));
        $this->assertTrue(TypeCoercion::numericToInt(true));
        $this->assertNull(TypeCoercion::numericToInt(null));
        
        // Test empty array (should remain unchanged)
        $emptyArray = [];
        $result = TypeCoercion::numericToInt($emptyArray);
        $this->assertEquals($emptyArray, $result);
    }

    public function testNumericToFloat(): void
    {
        // Test float values (should remain unchanged)
        $this->assertEquals(123.45, TypeCoercion::numericToFloat(123.45));
        $this->assertEquals(-456.78, TypeCoercion::numericToFloat(-456.78));
        
        // Test numeric values (should be converted to float)
        $numericCases = [
            123 => 123.0,
            '789.12' => 789.12,
            '456' => 456.0,
            '-123.45' => -123.45,
        ];
        
        foreach ($numericCases as $input => $expected) {
            $result = TypeCoercion::numericToFloat($input);
            $this->assertEquals($expected, $result, 'Failed for numeric input: ' . $input);
        }
        
        // Test non-numeric strings (should remain unchanged)
        $nonNumericStrings = ['abc', '123abc', ''];
        foreach ($nonNumericStrings as $input) {
            $result = TypeCoercion::numericToFloat($input);
            $this->assertEquals($input, $result, 'Failed for non-numeric string: ' . $input);
        }
        
        // Test other non-numeric types (should remain unchanged)
        $this->assertTrue(TypeCoercion::numericToFloat(true));
        $this->assertNull(TypeCoercion::numericToFloat(null));
        
        // Test empty array (should remain unchanged)
        $emptyArray = [];
        $result = TypeCoercion::numericToFloat($emptyArray);
        $this->assertEquals($emptyArray, $result);
    }

    public function testToString(): void
    {
        // Test string values (should remain unchanged)
        $this->assertEquals('hello', TypeCoercion::toString('hello'));
        $this->assertEquals('', TypeCoercion::toString(''));
        
        // Test scalar values (should be converted to string)
        $this->assertEquals('123', TypeCoercion::toString(123));
        $this->assertEquals('123.45', TypeCoercion::toString(123.45));
        $this->assertEquals('1', TypeCoercion::toString(true));
        $this->assertEquals('', TypeCoercion::toString(false));
        
        // Test non-scalar values (should remain unchanged)
        $this->assertNull(TypeCoercion::toString(null));
        
        // Test empty array (should remain unchanged)
        $emptyArray = [];
        $result = TypeCoercion::toString($emptyArray);
        $this->assertEquals($emptyArray, $result);
        
        // Test object (should remain unchanged)
        $object = (object)['prop' => 'value'];
        $result = TypeCoercion::toString($object);
        $this->assertEquals($object, $result);
    }

    public function testToArray(): void
    {
        // Test array values (should remain unchanged)
        $emptyArray = [];
        $result = TypeCoercion::toArray($emptyArray);
        $this->assertEquals($emptyArray, $result);

        $indexedArray = [1, 2, 3];
        $result = TypeCoercion::toArray($indexedArray);
        $this->assertEquals($indexedArray, $result);

        $associativeArray = ['key' => 'value'];
        $result = TypeCoercion::toArray($associativeArray);
        $this->assertEquals($associativeArray, $result);

        // Test non-array values (should remain unchanged - KISS approach)
        $testCases = [
            'string' => 'string',
            123 => 123,
            true => true,
            null => null
        ];

        foreach ($testCases as $input => $expected) {
            $result = TypeCoercion::toArray($input);
            $this->assertEquals($expected, $result, "Failed for input: " . var_export($input, true));
        }
    }

    public function testToUploadedFile(): void
    {
        // Valid file array
        $validFileData = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/tmp/phpupload123',
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];

        $result = TypeCoercion::toUploadedFile($validFileData);
        $this->assertInstanceOf(UploadedFile::class, $result);

        // Invalid file array (missing tmp_name)
        $invalidFileData = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];

        $result = TypeCoercion::toUploadedFile($invalidFileData);
        $this->assertEquals($invalidFileData, $result);

        // Non-array values
        $nonArrayValues = ['string', 123, true, null];
        foreach ($nonArrayValues as $value) {
            $result = TypeCoercion::toUploadedFile($value);
            $this->assertEquals($value, $result);
        }
    }

    private function createMockUnionTypeWithOnlyNull(): ReflectionUnionType
    {
        // Create a mock union type that only has null types
        $mockType = $this->createMock(ReflectionUnionType::class);
        
        $nullType = $this->createMock(ReflectionNamedType::class);
        $nullType->method('getName')->willReturn('null');
        
        $mockType->method('getTypes')->willReturn([$nullType]);
        
        return $mockType;
    }
}

// Helper classes for testing type coercion
class TestClassForTypeCoercion
{
    public string $stringProp;

    public int $intProp;

    public float $floatProp;

    public bool $boolProp;

    public array $arrayProp;

    public UploadedFile $uploadedFileProp;
}

class TestClassWithUnionType
{
    public string|null $stringOrNull = null;
}
