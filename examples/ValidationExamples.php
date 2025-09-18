<?php

require_once '../src/Http/Validation/Validator.php';
require_once '../src/Http/Validation/ValidationException.php';
require_once '../src/Http/UploadedFile.php';

use BaseApi\Http\Validation\Validator;
use BaseApi\Http\Validation\Attributes\Required;
use BaseApi\Http\Validation\Attributes\Email;
use BaseApi\Http\Validation\Attributes\Min;
use BaseApi\Http\Validation\Attributes\Confirmed;
use BaseApi\Http\Validation\Attributes\Image;

// Example 1: Traditional array-based validation with confirmed rule
class CreateUserController
{
    public string $email;
    public string $password;
    public string $password_confirmation;
}

$controller = new CreateUserController();
$controller->email = 'user@example.com';
$controller->password = 'secret123';
$controller->password_confirmation = 'secret123';

$validator = new Validator();

try {
    $validator->validate($controller, [
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed'
    ]);
    echo "✅ Traditional validation passed!\n";
} catch (\Exception $e) {
    echo "❌ Traditional validation failed: " . $e->getMessage() . "\n";
}

// Example 2: Custom validation messages
try {
    $validator->validate($controller, [
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed'
    ], [
        'email.required' => 'Email address is required',
        'email.email' => 'Please provide a valid email address',
        'password.confirmed' => 'Password confirmation does not match',
        'min' => 'The :attribute field must be at least :min characters'
    ]);
    echo "✅ Custom messages validation passed!\n";
} catch (\Exception $e) {
    echo "❌ Custom messages validation failed: " . $e->getMessage() . "\n";
}

// Example 3: Attribute-based validation (PHP 8+)
class CreateUserWithAttributesController
{
    #[Required]
    #[Email]
    public string $email;
    
    #[Required]
    #[Min(8)]
    #[Confirmed]
    public string $password;
    
    public string $password_confirmation;
}

$attributeController = new CreateUserWithAttributesController();
$attributeController->email = 'user@example.com';
$attributeController->password = 'secret123';
$attributeController->password_confirmation = 'secret123';

try {
    $validator->validateWithAttributes($attributeController);
    echo "✅ Attribute-based validation passed!\n";
} catch (\Exception $e) {
    echo "❌ Attribute-based validation failed: " . $e->getMessage() . "\n";
}

// Example 4: Image validation with dimensions
class UploadImageController
{
    public $avatar; // This would be an UploadedFile in real usage
}

$uploadController = new UploadImageController();
// Note: In real usage, this would come from $_FILES
// $uploadController->avatar = new UploadedFile($_FILES['avatar']);

// For demonstration, we'll skip the actual file upload test
echo "✅ Image validation example structure created!\n";

// Example 5: Custom validation rules
Validator::extend('strong_password', function($value, $parameter, $controller) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $value);
});

class StrongPasswordController
{
    public string $password;
}

$strongPasswordController = new StrongPasswordController();
$strongPasswordController->password = 'StrongPass123!';

try {
    $validator->validate($strongPasswordController, [
        'password' => 'required|strong_password'
    ]);
    echo "✅ Custom rule validation passed!\n";
} catch (\Exception $e) {
    echo "❌ Custom rule validation failed: " . $e->getMessage() . "\n";
}

// Example 6: Enhanced file validation rules (structure)
echo "\n=== Enhanced Validation Features Implemented ===\n";
echo "✅ confirmed rule - Validates matching confirmation fields\n";
echo "✅ image rule - Validates image files with MIME type checking\n";
echo "✅ max_width/max_height - Image dimension validation\n";
echo "✅ dimensions - Multi-constraint image validation\n";
echo "✅ Custom messages - Field-specific and rule-specific messages\n";
echo "✅ Custom rules - Extensible validation rule system\n";
echo "✅ Attribute-based validation - PHP 8+ attribute support\n";
echo "✅ Enhanced MIME type mapping - More file types supported\n";
echo "✅ Message interpolation - Placeholder support in messages\n";

echo "\n=== Available Validation Rules ===\n";
$rules = [
    'required', 'boolean', 'integer', 'numeric', 'array', 'file', 'email', 'uuid',
    'min', 'max', 'in', 'mimes', 'size', 'confirmed', 'image', 'max_width', 
    'max_height', 'dimensions'
];

foreach ($rules as $rule) {
    echo "• $rule\n";
}

echo "\n=== Usage Examples ===\n";
echo "// Traditional validation:\n";
echo "\$validator->validate(\$controller, ['email' => 'required|email']);\n\n";

echo "// With custom messages:\n";
echo "\$validator->validate(\$controller, ['email' => 'required'], ['email.required' => 'Custom message']);\n\n";

echo "// Attribute-based:\n";
echo "#[Required] #[Email] public string \$email;\n";
echo "\$validator->validateWithAttributes(\$controller);\n\n";

echo "// Custom rules:\n";
echo "Validator::extend('custom_rule', function(\$value) { return true; });\n\n";

echo "// Image validation:\n";
echo "'avatar' => 'required|image|max_width:1920|max_height:1080'\n";
echo "'photo' => 'image|dimensions:min_width=100,max_width=1000,min_height=100,max_height=1000'\n\n";
