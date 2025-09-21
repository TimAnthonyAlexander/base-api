<?php

namespace BaseApi\Tests;

use Override;
use PHPUnit\Framework\TestCase;
use BaseApi\Http\Validation\Validator;
use BaseApi\Http\Validation\ValidationException;
use BaseApi\Http\UploadedFile;

class ValidatorTest extends TestCase
{
    private Validator $validator;

    #[Override]
    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testValidateWithNoErrorsDoesNotThrow(): void
    {
        $controller = new class {
            public string $name = 'John Doe';

            public int $age = 25;
        };

        $rules = [
            'name' => 'required',
            'age' => 'integer|min:18'
        ];

        // Should not throw exception
        $this->validator->validate($controller, $rules);
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testValidateThrowsValidationExceptionOnErrors(): void
    {
        $controller = new class {
            public string $name = '';

            public int $age = 15;
        };

        $rules = [
            'name' => 'required',
            'age' => 'min:18'
        ];

        $this->expectException(ValidationException::class);
        $this->validator->validate($controller, $rules);
    }

    public function testRequiredValidation(): void
    {
        $controller = new class {
            public string $name = '';

            public ?string $email = null;

            public array $items = [];
        };

        $rules = [
            'name' => 'required',
            'email' => 'required',
            'items' => 'required'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayHasKey('name', $errors);
            $this->assertArrayHasKey('email', $errors);
            $this->assertArrayHasKey('items', $errors);
            $this->assertStringContainsString('required', $errors['name']);
        }
    }

    public function testBooleanValidation(): void
    {
        $controller = new class {
            public bool $active = true;

            public string $invalid = 'not a boolean';
        };

        $rules = [
            'active' => 'boolean',
            'invalid' => 'boolean'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayNotHasKey('active', $errors);
            $this->assertArrayHasKey('invalid', $errors);
            $this->assertStringContainsString('boolean', $errors['invalid']);
        }
    }

    public function testIntegerValidation(): void
    {
        $controller = new class {
            public int $age = 25;

            public string $notInteger = '25.5';
        };

        $rules = [
            'age' => 'integer',
            'notInteger' => 'integer'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayNotHasKey('age', $errors);
            $this->assertArrayHasKey('notInteger', $errors);
            $this->assertStringContainsString('integer', $errors['notInteger']);
        }
    }

    public function testNumericValidation(): void
    {
        $controller = new class {
            public float $price = 29.99;

            public string $numericString = '123';

            public string $notNumeric = 'abc';
        };

        $rules = [
            'price' => 'numeric',
            'numericString' => 'numeric',
            'notNumeric' => 'numeric'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayNotHasKey('price', $errors);
            $this->assertArrayNotHasKey('numericString', $errors);
            $this->assertArrayHasKey('notNumeric', $errors);
            $this->assertStringContainsString('numeric', $errors['notNumeric']);
        }
    }

    public function testArrayValidation(): void
    {
        $controller = new class {
            public array $items = ['item1', 'item2'];

            public string $notArray = 'string';
        };

        $rules = [
            'items' => 'array',
            'notArray' => 'array'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayNotHasKey('items', $errors);
            $this->assertArrayHasKey('notArray', $errors);
            $this->assertStringContainsString('array', $errors['notArray']);
        }
    }

    public function testEmailValidation(): void
    {
        $controller = new class {
            public string $validEmail = 'test@example.com';

            public string $invalidEmail = 'not-an-email';
        };

        $rules = [
            'validEmail' => 'email',
            'invalidEmail' => 'email'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayNotHasKey('validEmail', $errors);
            $this->assertArrayHasKey('invalidEmail', $errors);
            $this->assertStringContainsString('email', $errors['invalidEmail']);
        }
    }

    public function testUuidValidation(): void
    {
        $controller = new class {
            public string $validUuid = '550e8400-e29b-41d4-a716-446655440000';

            public string $invalidUuid = 'not-a-uuid';
        };

        $rules = [
            'validUuid' => 'uuid',
            'invalidUuid' => 'uuid'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayNotHasKey('validUuid', $errors);
            $this->assertArrayHasKey('invalidUuid', $errors);
            $this->assertStringContainsString('UUID', $errors['invalidUuid']);
        }
    }

    public function testMinValidationForStrings(): void
    {
        $controller = new class {
            public string $validString = 'hello world';

            public string $shortString = 'hi';
        };

        $rules = [
            'validString' => 'min:5',
            'shortString' => 'min:5'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayNotHasKey('validString', $errors);
            $this->assertArrayHasKey('shortString', $errors);
            $this->assertStringContainsString('at least 5 characters', $errors['shortString']);
        }
    }

    public function testMinValidationForNumbers(): void
    {
        $controller = new class {
            public int $validNumber = 25;

            public int $smallNumber = 10;
        };

        $rules = [
            'validNumber' => 'min:18',
            'smallNumber' => 'min:18'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayNotHasKey('validNumber', $errors);
            $this->assertArrayHasKey('smallNumber', $errors);
            $this->assertStringContainsString('at least 18', $errors['smallNumber']);
        }
    }

    public function testMinValidationForArrays(): void
    {
        $controller = new class {
            public array $validArray = ['a', 'b', 'c'];

            public array $shortArray = ['a'];
        };

        $rules = [
            'validArray' => 'min:2',
            'shortArray' => 'min:2'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayNotHasKey('validArray', $errors);
            $this->assertArrayHasKey('shortArray', $errors);
            $this->assertStringContainsString('at least 2 items', $errors['shortArray']);
        }
    }

    public function testMaxValidationForStrings(): void
    {
        $controller = new class {
            public string $validString = 'hello';

            public string $longString = 'this is a very long string';
        };

        $rules = [
            'validString' => 'max:10',
            'longString' => 'max:10'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayNotHasKey('validString', $errors);
            $this->assertArrayHasKey('longString', $errors);
            $this->assertStringContainsString('not exceed 10 characters', $errors['longString']);
        }
    }

    public function testMaxValidationForNumbers(): void
    {
        $controller = new class {
            public int $validNumber = 25;

            public int $bigNumber = 150;
        };

        $rules = [
            'validNumber' => 'max:100',
            'bigNumber' => 'max:100'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayNotHasKey('validNumber', $errors);
            $this->assertArrayHasKey('bigNumber', $errors);
            $this->assertStringContainsString('not exceed 100', $errors['bigNumber']);
        }
    }

    public function testMaxValidationForArrays(): void
    {
        $controller = new class {
            public array $validArray = ['a', 'b'];

            public array $longArray = ['a', 'b', 'c', 'd', 'e'];
        };

        $rules = [
            'validArray' => 'max:3',
            'longArray' => 'max:3'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayNotHasKey('validArray', $errors);
            $this->assertArrayHasKey('longArray', $errors);
            $this->assertStringContainsString('not have more than 3 items', $errors['longArray']);
        }
    }

    public function testInValidation(): void
    {
        $controller = new class {
            public string $validStatus = 'active';

            public string $invalidStatus = 'unknown';
        };

        $rules = [
            'validStatus' => 'in:active,inactive,pending',
            'invalidStatus' => 'in:active,inactive,pending'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayNotHasKey('validStatus', $errors);
            $this->assertArrayHasKey('invalidStatus', $errors);
            $this->assertStringContainsString('must be one of: active, inactive, pending', $errors['invalidStatus']);
        }
    }

    public function testConfirmedValidation(): void
    {
        $controller = new class {
            public string $password = 'secret123';

            public string $password_confirmation = 'secret123';

            public string $differentPassword = 'secret123';

            public string $differentPassword_confirmation = 'different';
        };

        $rules = [
            'password' => 'confirmed',
            'differentPassword' => 'confirmed'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayNotHasKey('password', $errors);
            $this->assertArrayHasKey('differentPassword', $errors);
            $this->assertStringContainsString('confirmation does not match', $errors['differentPassword']);
        }
    }

    public function testConfirmedValidationWithCustomField(): void
    {
        $controller = new class {
            public string $password = 'secret123';

            public string $confirmPassword = 'secret123';
        };

        $rules = [
            'password' => 'confirmed:confirmPassword'
        ];

        // Should not throw exception since password matches confirmPassword
        $this->validator->validate($controller, $rules);
        $this->assertTrue(true);
    }

    public function testFileValidation(): void
    {
        $validFile = $this->createMock(UploadedFile::class);
        $validFile->method('isValid')->willReturn(true);
        
        $invalidFile = $this->createMock(UploadedFile::class);
        $invalidFile->method('isValid')->willReturn(false);

        $controller = new class {
            public $validFile;

            public $invalidFile;

            public string $notFile = 'string';
        };
        
        $controller->validFile = $validFile;
        $controller->invalidFile = $invalidFile;

        $rules = [
            'validFile' => 'file',
            'invalidFile' => 'file',
            'notFile' => 'file'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayNotHasKey('validFile', $errors);
            $this->assertArrayHasKey('invalidFile', $errors);
            $this->assertArrayHasKey('notFile', $errors);
            $this->assertStringContainsString('upload failed', $errors['invalidFile']);
            $this->assertStringContainsString('must be a file', $errors['notFile']);
        }
    }

    public function testImageValidation(): void
    {
        $validImage = $this->createMock(UploadedFile::class);
        $validImage->method('isValid')->willReturn(true);
        $validImage->method('getMimeType')->willReturn('image/jpeg');
        $validImage->tmpName = '/tmp/valid_image.jpg'; // Set the property directly
        
        $invalidImage = $this->createMock(UploadedFile::class);
        $invalidImage->method('isValid')->willReturn(true);
        $invalidImage->method('getMimeType')->willReturn('application/pdf');
        $invalidImage->tmpName = '/tmp/invalid.pdf'; // Set the property directly

        $controller = new class {
            public $validImage;

            public $invalidImage;
        };
        
        $controller->validImage = $validImage;
        $controller->invalidImage = $invalidImage;

        $rules = [
            'validImage' => 'image',
            'invalidImage' => 'image'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayNotHasKey('validImage', $errors);
            $this->assertArrayHasKey('invalidImage', $errors);
            $this->assertStringContainsString('must be an image file', $errors['invalidImage']);
        }
    }

    public function testMimesValidation(): void
    {
        $validFile = $this->createMock(UploadedFile::class);
        $validFile->method('getExtension')->willReturn('pdf');
        $validFile->method('getMimeType')->willReturn('application/pdf');
        
        $invalidFile = $this->createMock(UploadedFile::class);
        $invalidFile->method('getExtension')->willReturn('txt');
        $invalidFile->method('getMimeType')->willReturn('text/plain');

        $controller = new class {
            public $validFile;

            public $invalidFile;
        };
        
        $controller->validFile = $validFile;
        $controller->invalidFile = $invalidFile;

        $rules = [
            'validFile' => 'mimes:pdf,jpg,png',
            'invalidFile' => 'mimes:pdf,jpg,png'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayNotHasKey('validFile', $errors);
            $this->assertArrayHasKey('invalidFile', $errors);
            $this->assertStringContainsString('must be a file of type: pdf, jpg, png', $errors['invalidFile']);
        }
    }

    public function testSizeValidation(): void
    {
        $validFile = $this->createMock(UploadedFile::class);
        $validFile->method('getSizeInMB')->willReturn(2.0);
        
        $oversizedFile = $this->createMock(UploadedFile::class);
        $oversizedFile->method('getSizeInMB')->willReturn(10.0);

        $controller = new class {
            public $validFile;

            public $oversizedFile;
        };
        
        $controller->validFile = $validFile;
        $controller->oversizedFile = $oversizedFile;

        $rules = [
            'validFile' => 'size:5',
            'oversizedFile' => 'size:5'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayNotHasKey('validFile', $errors);
            $this->assertArrayHasKey('oversizedFile', $errors);
            $this->assertStringContainsString('not exceed 5MB', $errors['oversizedFile']);
        }
    }

    public function testCustomMessages(): void
    {
        $controller = new class {
            public string $name = '';
        };

        $rules = [
            'name' => 'required'
        ];

        $messages = [
            'name.required' => 'Custom name required message'
        ];

        try {
            $this->validator->validate($controller, $rules, $messages);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertEquals('Custom name required message', $errors['name']);
        }
    }

    public function testSkipsNonPublicProperties(): void
    {
        $controller = new class {
            public string $publicField = '';
            protected string $protectedField = '';
        };

        $rules = [
            'publicField' => 'required',
            'privateField' => 'required',
            'protectedField' => 'required'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayHasKey('publicField', $errors); // Only public field should be validated
            $this->assertArrayNotHasKey('privateField', $errors);
            $this->assertArrayNotHasKey('protectedField', $errors);
        }
    }

    public function testSkipsUnknownProperties(): void
    {
        $controller = new class {
            public string $existingField = '';
        };

        $rules = [
            'existingField' => 'required',
            'unknownField' => 'required'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayHasKey('existingField', $errors);
            $this->assertArrayNotHasKey('unknownField', $errors); // Unknown property should be skipped
        }
    }

    public function testSkipsOtherValidationsWhenValueIsEmptyAndNotRequired(): void
    {
        $controller = new class {
            public string $optionalEmail = '';
        };

        $rules = [
            'optionalEmail' => 'email|min:5' // No required rule
        ];

        // Should not throw exception because empty value is allowed for non-required fields
        $this->validator->validate($controller, $rules);
        $this->assertTrue(true);
    }

    public function testMultipleRulesParsing(): void
    {
        $controller = new class {
            public string $field = '';
        };

        $rules = [
            'field' => 'required|email|min:5|max:100'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayHasKey('field', $errors);
            $this->assertStringContainsString('required', $errors['field']);
        }
    }

    public function testCustomRuleExtension(): void
    {
        // Test the extend functionality for custom rules
        Validator::extend('custom_rule', fn($value, $parameter, $controller): bool => $value === 'expected_value');

        $controller = new class {
            public string $field = 'wrong_value';
        };

        $rules = [
            'field' => 'custom_rule'
        ];

        try {
            $this->validator->validate($controller, $rules);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            $this->assertArrayHasKey('field', $errors);
            $this->assertStringContainsString('failed custom_rule validation', $errors['field']);
        }
    }
}
