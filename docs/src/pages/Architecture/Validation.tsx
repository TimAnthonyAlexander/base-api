
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
                Simple, property-based validation for your controllers.
            </Typography>

            <Typography>
                BaseAPI validates your controller properties directly. Call <code>validate()</code> 
                with rules, and if anything fails, you get a clean error response with specific 
                field messages. No manual request parsing needed.
            </Typography>

            <Alert severity="info" sx={{ my: 3 }}>
                Validation happens automatically on your controller's public properties. 
                Failed validation returns HTTP 400 with detailed error messages.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Basic Validation
            </Typography>

            <Typography>
                Define your properties, then call <code>validate()</code> with rules:
            </Typography>

            <CodeBlock language="php" code={`<?php

class CreateUserController extends Controller
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public int $age = 0;
    
    public function post(): JsonResponse
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'age' => 'required|integer|min:18',
        ]);
        
        // Properties are already populated and validated
        $user = new User();
        $user->name = $this->name;
        $user->email = $this->email;
        $user->password = password_hash($this->password, PASSWORD_DEFAULT);
        $user->age = $this->age;
        $user->save();
        
        return JsonResponse::created($user->toArray());
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Validation Rules
            </Typography>

            <Typography>
                Available validation rules:
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Rule</strong></TableCell>
                            <TableCell><strong>What it does</strong></TableCell>
                            <TableCell><strong>Example</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            <TableCell><code>required</code></TableCell>
                            <TableCell>Cannot be empty</TableCell>
                            <TableCell><code>'name' ={'>'} 'required'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>string</code></TableCell>
                            <TableCell>Must be a string (no type checking in PHP code)</TableCell>
                            <TableCell><code>'title' ={'>'} 'string'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>integer</code></TableCell>
                            <TableCell>Must be an integer</TableCell>
                            <TableCell><code>'age' ={'>'} 'integer'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>numeric</code></TableCell>
                            <TableCell>Must be numeric (int or float)</TableCell>
                            <TableCell><code>'price' ={'>'} 'numeric'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>boolean</code></TableCell>
                            <TableCell>Must be true/false</TableCell>
                            <TableCell><code>'active' ={'>'} 'boolean'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>array</code></TableCell>
                            <TableCell>Must be an array</TableCell>
                            <TableCell><code>'tags' ={'>'} 'array'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>email</code></TableCell>
                            <TableCell>Valid email format</TableCell>
                            <TableCell><code>'email' ={'>'} 'email'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>uuid</code></TableCell>
                            <TableCell>Valid UUID format</TableCell>
                            <TableCell><code>'id' ={'>'} 'uuid'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>min:X</code></TableCell>
                            <TableCell>Minimum length/value/count</TableCell>
                            <TableCell><code>'password' ={'>'} 'min:8'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>max:X</code></TableCell>
                            <TableCell>Maximum length/value/count</TableCell>
                            <TableCell><code>'name' ={'>'} 'max:100'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>in:a,b,c</code></TableCell>
                            <TableCell>Must be one of the listed values</TableCell>
                            <TableCell><code>'status' ={'>'} 'in:active,inactive'</code></TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>confirmed</code></TableCell>
                            <TableCell>Must match another field (field_confirmation)</TableCell>
                            <TableCell><code>'password' ={'>'} 'confirmed'</code></TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Advanced Rules
            </Typography>

            <Typography>
                Combine rules with <code>|</code> for more specific validation:
            </Typography>

            <CodeBlock language="php" code={`<?php

class CreateProductController extends Controller
{
    public string $name = '';
    public float $price = 0.0;
    public string $status = '';
    public array $tags = [];
    
    public function post(): JsonResponse
    {
        $this->validate([
            'name' => 'required|string|min:3|max:100',
            'price' => 'required|numeric|min:0.01',
            'status' => 'required|in:draft,published,archived',
            'tags' => 'array|max:5', // Max 5 tags
        ]);
        
        $product = new Product();
        $product->name = $this->name;
        $product->price = $this->price;
        $product->status = $this->status;
        $product->tags = $this->tags;
        $product->save();
        
        return JsonResponse::created($product->toArray());
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                File Upload Validation
            </Typography>

            <Typography>
                File uploads use <code>UploadedFile</code> properties with file-specific rules:
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
            'avatar' => 'required|file|image|size:2', // 2MB max
            'document' => 'file|mimes:pdf,docx|size:10', // 10MB max
        ]);
        
        if ($this->avatar) {
            $path = $this->avatar->store('uploads/avatars/');
        }
        
        return JsonResponse::ok(['uploaded' => true]);
    }
}`} />

            <Typography sx={{ mt: 2 }}>
                <strong>File validation rules:</strong>
            </Typography>
            <ul>
                <li><code>file</code> - Must be an UploadedFile instance</li>
                <li><code>image</code> - Must be jpeg, png, gif, webp, or bmp</li>
                <li><code>mimes:jpg,png,pdf</code> - Allowed file extensions</li>
                <li><code>size:5</code> - Maximum size in MB</li>
                <li><code>max_width:1920</code> - Maximum image width in pixels</li>
                <li><code>max_height:1080</code> - Maximum image height in pixels</li>
            </ul>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Error Response Format
            </Typography>

            <Typography>
                When validation fails, you get an HTTP 400 response:
            </Typography>

            <CodeBlock language="json" code={`{
  "error": "Validation failed.",
  "requestId": "req_abc123",
  "errors": {
    "name": "The name field is required.",
    "email": "The email field must be a valid email address.",
    "password": "The password field must be at least 8 characters.",
    "age": "The age field must be an integer."
  }
}`} />

            <Typography sx={{ mt: 2 }}>
                Each field gets its first validation error. The validation stops at the first 
                failed rule per field, so you won't get multiple error messages per field.
            </Typography>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Custom Validation Messages
            </Typography>

            <Typography>
                Pass custom messages as the second parameter:
            </Typography>

            <CodeBlock language="php" code={`<?php

$this->validate([
    'email' => 'required|email',
    'password' => 'required|min:8',
], [
    'email.required' => 'We need your email address.',
    'email.email' => 'That email doesn\'t look right.',
    'password.min' => 'Password needs at least 8 characters.',
]);`} />

            <Typography sx={{ mt: 2 }}>
                Use <code>field.rule</code> format to target specific field-rule combinations.
            </Typography>

            <Callout type="tip" title="Automatic Property Mapping">
                Validation rules automatically apply to controller properties with matching names.
                No need to manually extract request data - it's handled automatically.
            </Callout>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Custom Validation Rules
            </Typography>

            <Typography>
                Register custom validation rules using <code>Validator::extend()</code>:
            </Typography>

            <CodeBlock language="php" code={`<?php

// Register a custom rule (typically in a service provider)
Validator::extend('strong_password', function($value, $parameter, $controller) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[@$!%*?&])[A-Za-z\\d@$!%*?&]/', $value);
});

// Use it in your controller
class RegisterController extends Controller
{
    public string $password = '';
    
    public function post(): JsonResponse
    {
        $this->validate([
            'password' => 'required|min:8|strong_password',
        ]);
        
        // Password is validated with your custom rule
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                PHP Attributes (Alternative Syntax)
            </Typography>

            <Typography>
                You can also use PHP attributes for validation instead of rule strings:
            </Typography>

            <CodeBlock language="php" code={`<?php

use BaseApi\\Http\\Validation\\Attributes\\*;

class UserController extends Controller
{
    #[Required]
    #[Min(3)]
    #[Max(100)]
    public string $name = '';
    
    #[Required]
    #[Email]
    public string $email = '';
    
    public function post(): JsonResponse
    {
        // Validate using attributes instead of rules
        $validator = new Validator();
        $validator->validateWithAttributes($this);
        
        // Validation passed
    }
}`} />

            <Alert severity="info" sx={{ mt: 4 }}>
                <strong>Keep in mind:</strong>
                <br />• Empty fields skip validation unless they're <code>required</code>
                <br />• Validation stops at the first failed rule per field
                <br />• File validation requires <code>UploadedFile</code> properties
                <br />• Custom rules run after all built-in rules
                <br />• Use specific rules like <code>integer</code> vs just <code>numeric</code>
            </Alert>
        </Box>
    );
}
