
import { Box, Typography, Alert, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

export default function Validation() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Validation
            </Typography>
            <Typography variant="h5" color="text.secondary" paragraph>
                Input validation in BaseAPI controllers.
            </Typography>

            <Typography>
                BaseAPI provides built-in input validation that automatically validates request data
                against defined rules. Validation failures return standardized error responses with
                detailed field-level error messages.
            </Typography>

            <Alert severity="info" sx={{ my: 3 }}>
                Validation rules are applied to controller properties automatically. Failed validation
                returns 422 Unprocessable Entity with detailed error information.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Basic Validation
            </Typography>

            <Typography>
                Use the <code>validate()</code> method in controllers to apply validation rules:
            </Typography>

            <CodeBlock language="php" code={`<?php

class UserController extends Controller
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public int $age = 0;
    public bool $active = true;
    
    public function post(): JsonResponse
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'age' => 'required|integer|min:18|max:120',
            'active' => 'boolean',
        ]);
        
        // If validation passes, create user
        $user = new User();
        $user->name = $this->name;
        $user->email = $this->email;
        $user->password = password_hash($this->password, PASSWORD_DEFAULT);
        $user->age = $this->age;
        $user->active = $this->active;
        $user->save();
        
        return JsonResponse::created($user->jsonSerialize());
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Validation Rules
            </Typography>

            <Typography>
                BaseAPI supports comprehensive validation rules for common scenarios:
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Rule</strong></TableCell>
                            <TableCell><strong>Description</strong></TableCell>
                            <TableCell><strong>Example</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            <TableCell><code>required</code></TableCell>
                            <TableCell>Field must be present and not empty</TableCell>
                            <TableCell><code>'name' ={'>'} 'required'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>string</code></TableCell>
                            <TableCell>Must be a string value</TableCell>
                            <TableCell><code>'title' ={'>'} 'string'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>integer</code></TableCell>
                            <TableCell>Must be an integer value</TableCell>
                            <TableCell><code>'age' ={'>'} 'integer'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>numeric</code></TableCell>
                            <TableCell>Must be numeric (int or float)</TableCell>
                            <TableCell><code>'price' ={'>'} 'numeric'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>boolean</code></TableCell>
                            <TableCell>Must be true/false, 1/0, "true"/"false"</TableCell>
                            <TableCell><code>'active' ={'>'} 'boolean'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>email</code></TableCell>
                            <TableCell>Must be valid email address</TableCell>
                            <TableCell><code>'email' ={'>'} 'email'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>uuid</code></TableCell>
                            <TableCell>Must be valid UUID format</TableCell>
                            <TableCell><code>'id' ={'>'} 'uuid'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>url</code></TableCell>
                            <TableCell>Must be valid URL format</TableCell>
                            <TableCell><code>'website' ={'>'} 'url'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>min:value</code></TableCell>
                            <TableCell>Minimum length/value</TableCell>
                            <TableCell><code>'password' ={'>'} 'min:8'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>max:value</code></TableCell>
                            <TableCell>Maximum length/value</TableCell>
                            <TableCell><code>'name' ={'>'} 'max:100'</code></TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Advanced Rules
            </Typography>

            <Typography>
                More complex validation scenarios:
            </Typography>

            <CodeBlock language="php" code={`<?php

class ProductController extends Controller
{
    public string $name = '';
    public string $sku = '';
    public float $price = 0.0;
    public string $categoryId = '';
    public array $tags = [];
    
    public function post(): JsonResponse
    {
        $this->validate([
            // String with length constraints
            'name' => 'required|string|min:3|max:200',
            
            // SKU with length constraint
            'sku' => 'required|string|max:50',
            
            // Numeric with range
            'price' => 'required|numeric|min:0.01|max:999999.99',
            
            // UUID validation
            'categoryId' => 'required|uuid',
            
            // Array validation
            'tags' => 'array',
            'tags.*' => 'string|max:50', // Each tag element
        ]);
        
        // Validation passed, create product
        $product = new Product();
        $product->name = $this->name;
        $product->sku = $this->sku;
        $product->price = $this->price;
        $product->category_id = $this->categoryId;
        $product->save();
        
        return JsonResponse::created($product->jsonSerialize());
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                File Upload Validation
            </Typography>

            <Typography>
                Validate uploaded files with specific rules:
            </Typography>

            <CodeBlock language="php" code={`<?php

use BaseApi\\Http\\UploadedFile;

class UploadController extends Controller
{
    public ?UploadedFile $avatar = null;
    public ?UploadedFile $document = null;
    
    public function post(): JsonResponse
    {
        $this->validate([
            // Image file validation
            'avatar' => 'required|file|image|max:2048', // 2MB max
            
            // Document validation with specific types
            'document' => 'file|mimes:pdf,doc,docx|max:10240', // 10MB max
        ]);
        
        if ($this->avatar) {
            $avatarPath = $this->avatar->store('uploads/avatars/');
        }
        
        if ($this->document) {
            $docPath = $this->document->store('uploads/documents/');
        }
        
        return JsonResponse::ok(['message' => 'Files uploaded successfully']);
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Error Response Format
            </Typography>

            <Typography>
                Validation failures return structured error responses:
            </Typography>

            <CodeBlock language="json" code={`{
  "error": "Validation failed",
  "message": "The given data was invalid.",
  "errors": {
    "name": [
      "The name field is required."
    ],
    "email": [
      "The email field must be a valid email address.",
      "The email has already been taken."
    ],
    "password": [
      "The password field must be at least 8 characters."
    ],
    "age": [
      "The age field must be an integer.",
      "The age field must be at least 18."
    ]
  },
  "status": 422
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Custom Validation Messages
            </Typography>

            <Typography>
                You can customize validation error messages:
            </Typography>

            <CodeBlock language="php" code={`<?php

class UserController extends Controller
{
    public function post(): JsonResponse
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ], [
            // Custom messages
            'name.required' => 'Please provide your full name.',
            'email.required' => 'An email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'password.min' => 'Password must be at least 8 characters long.',
        ]);
        
        // Create user...
    }
}`} />

            <Callout type="tip" title="Automatic Property Mapping">
                Validation rules automatically apply to controller properties with matching names.
                No need to manually extract request data - it's handled automatically.
            </Callout>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Conditional Validation
            </Typography>

            <Typography>
                Apply validation rules conditionally based on other field values:
            </Typography>

            <CodeBlock language="php" code={`<?php

class OrderController extends Controller
{
    public string $paymentMethod = '';
    public ?string $cardNumber = null;
    public ?string $paypalEmail = null;
    
    public function post(): JsonResponse
    {
        $rules = [
            'paymentMethod' => 'required|in:card,paypal,cash',
        ];
        
        // Conditional validation based on payment method
        if ($this->paymentMethod === 'card') {
            $rules['cardNumber'] = 'required|string|min:13|max:19';
        }
        
        if ($this->paymentMethod === 'paypal') {
            $rules['paypalEmail'] = 'required|email';
        }
        
        $this->validate($rules);
        
        // Process order with validated payment info...
    }
}`} />

            <Alert severity="success" sx={{ mt: 4 }}>
                <strong>Best Practices:</strong>
                <br />• Always validate user input
                <br />• Use appropriate validation rules for data types
                <br />• Provide clear, helpful error messages
                <br />• Validate file uploads for security
                <br />• Use conditional validation when needed
                <br />• Test validation rules thoroughly
            </Alert>
        </Box>
    );
}
