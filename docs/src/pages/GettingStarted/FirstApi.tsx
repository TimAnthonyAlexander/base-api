
import {
    Box,
    Typography,
    Button,
    Stepper,
    Step,
    StepLabel,
    StepContent,
} from '@mui/material';
import { ArrowForward as ArrowIcon } from '@mui/icons-material';
import { Link } from 'react-router-dom';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';
import ApiMethod from '../../components/ApiMethod';

const modelCode = `<?php

namespace App\\Models;

use BaseApi\\Models\\BaseModel;
use BaseApi\\Database\\Relations\\BelongsTo;
use BaseApi\\Storage\\Storage;

class Product extends BaseModel
{
    public string $name = '';
    public ?string $description = null;
    public float $price = 0.0;
    public ?string $image_path = null;
    
    // Define indexes for performance and constraints
    public static array $indexes = [
        'name' => 'index',
        'price' => 'index',
    ];
    
    // Define relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    
    // Custom accessor for image URL
    public function getImageUrl(): ?string
    {
        return $this->image_path ? Storage::url($this->image_path) : null;
    }
    
    // Override jsonSerialize to include computed fields
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['image_url'] = $this->getImageUrl();
        return $data;
    }
    
    // id, created_at, updated_at are inherited from BaseModel
}`;

const controllerCode = `<?php

namespace App\\Controllers;

use BaseApi\\Controllers\\Controller;
use BaseApi\\Http\\JsonResponse;
use BaseApi\\Http\\UploadedFile;
use BaseApi\\Http\\Attributes\\Tag;
use BaseApi\\Http\\Validation\\Attributes\\Required;
use BaseApi\\Http\\Validation\\Attributes\\Min;
use BaseApi\\Http\\Validation\\Attributes\\Max;
use BaseApi\\Http\\Validation\\Attributes\\Numeric;
use BaseApi\\Http\\Validation\\Attributes\\File;
use BaseApi\\Http\\Validation\\Attributes\\Mimes;
use BaseApi\\Http\\Validation\\Attributes\\Size;
use BaseApi\\Support\\I18n;
use BaseApi\\Cache\\Cache;
use App\\Models\\Product;
use App\\Jobs\\ProcessProductJob;

#[Tag('Products')]
class ProductController extends Controller
{
    // Define public properties to auto-populate from request data
    #[Required]
    #[Max(255)]
    public string $name = '';
    
    #[Max(1000)]
    public ?string $description = null;
    
    #[Required]
    #[Numeric]
    #[Min(0)]
    public float $price = 0.0;
    
    public string $id = '';
    
    #[File]
    #[Mimes(['jpg', 'jpeg', 'png', 'gif'])]
    #[Size(5)] // 5MB max
    public ?UploadedFile $image = null;
    
    public function post(): JsonResponse
    {
        // Validation is handled automatically via PHP attributes
        
        $product = new Product();
        $product->name = $this->name;
        $product->description = $this->description ?? null;
        $product->price = (float) $this->price;
        
        // Handle image upload if provided
        if ($this->image instanceof UploadedFile) {
            $imagePath = $this->image->store('products');
            $product->image_path = $imagePath;
        }
        
        $product->save();
        
        // Dispatch background job for product processing (notifications, indexing, etc.)
        dispatch(new ProcessProductJob($product->id));
        
        // Clear products cache
        Cache::tags(['products'])->flush();
        
        return JsonResponse::created([
            'product' => $product,
            'message' => I18n::t('product.created_successfully', ['name' => $product->name])
        ]);
    }
    
    public function get(): JsonResponse
    {
        // If ID is provided as route parameter, return single product
        if (!empty($this->id)) {
            // Try to get from cache first
            $cacheKey = "product:{$this->id}";
            $product = Cache::get($cacheKey);
            
            if (!$product) {
                $product = Product::find($this->id);
                
                if (!$product) {
                    return JsonResponse::notFound(I18n::t('product.not_found'));
                }
                
                // Cache for 1 hour
                Cache::put($cacheKey, $product, 3600);
            }
            
            return JsonResponse::ok($product);
        }
        
        // Return all products with caching
        $products = Product::cached()->all();
        return JsonResponse::ok($products);
    }
    
    public function put(): JsonResponse
    {
        $product = Product::find($this->id);
        
        if (!$product) {
            return JsonResponse::notFound(I18n::t('product.not_found'));
        }
        
        // Update product with validation
        if ($this->name) $product->name = $this->name;
        if ($this->description !== null) $product->description = $this->description;
        if ($this->price > 0) $product->price = $this->price;
        
        // Handle image replacement
        if ($this->image instanceof UploadedFile) {
            $imagePath = $this->image->store('products');
            $product->image_path = $imagePath;
        }
        
        $product->save();
        
        // Clear cache
        Cache::forget("product:{$this->id}");
        Cache::tags(['products'])->flush();
        
        return JsonResponse::ok([
            'product' => $product,
            'message' => I18n::t('product.updated_successfully', ['name' => $product->name])
        ]);
    }
    
    public function delete(): JsonResponse
    {
        $product = Product::find($this->id);
        
        if (!$product) {
            return JsonResponse::notFound(I18n::t('product.not_found'));
        }
        
        $product->delete();
        
        // Clear cache
        Cache::forget("product:{$this->id}");
        Cache::tags(['products'])->flush();
        
        return JsonResponse::ok([
            'message' => I18n::t('product.deleted_successfully', ['name' => $product->name])
        ]);
    }
}`;

const routesCode = `<?php

use BaseApi\\App;
use App\\Controllers\\ProductController;
use BaseApi\\Http\\Middleware\\RateLimitMiddleware;
use App\\Middleware\\CombinedAuthMiddleware;

$router = App::router();

// Public endpoints (no authentication required)
$router->get('/products', [
    RateLimitMiddleware::class => ['limit' => '100/1h'],
    ProductController::class,
]);

$router->get('/products/{id}', [
    RateLimitMiddleware::class => ['limit' => '100/1h'],
    ProductController::class,
]);

// Protected endpoints (authentication required)
$router->post('/products', [
    CombinedAuthMiddleware::class, // Supports both session and API token auth
    RateLimitMiddleware::class => ['limit' => '20/1h'],
    ProductController::class,
]);

$router->put('/products/{id}', [
    CombinedAuthMiddleware::class,
    RateLimitMiddleware::class => ['limit' => '50/1h'],
    ProductController::class,
]);

$router->delete('/products/{id}', [
    CombinedAuthMiddleware::class,
    RateLimitMiddleware::class => ['limit' => '10/1h'],
    ProductController::class,
]);`;

const testRequests = `# Get all products (public endpoint)
curl http://localhost:7879/products

# Create a new product with authentication
curl -X POST http://localhost:7879/products \\
  -H "Content-Type: application/json" \\
  -H "Authorization: Bearer your_api_token_here" \\
  -d '{"name": "Laptop", "description": "Gaming laptop", "price": 999.99}'

# Create a product with image upload (multipart form)
curl -X POST http://localhost:7879/products \\
  -H "Authorization: Bearer your_api_token_here" \\
  -F "name=Gaming Chair" \\
  -F "description=Ergonomic gaming chair" \\
  -F "price=299.99" \\
  -F "image=@chair.jpg"

# Get a specific product (replace {id} with actual ID)
curl http://localhost:7879/products/1

# Update a product
curl -X PUT http://localhost:7879/products/1 \\
  -H "Content-Type: application/json" \\
  -H "Authorization: Bearer your_api_token_here" \\
  -d '{"name": "Updated Laptop", "price": 899.99}'

# Delete a product
curl -X DELETE http://localhost:7879/products/1 \\
  -H "Authorization: Bearer your_api_token_here"

# Test with different language
curl -H "Accept-Language: es" http://localhost:7879/products/999`;

const steps = [
    {
        label: 'Create a Model',
        content: 'Start by creating a Product model that defines your data structure with relationships and indexes.',
        detail: 'Models in BaseAPI extend BaseModel and use public properties to define database fields. The framework automatically handles migrations based on your model definitions.',
    },
    {
        label: 'Generate Migration',
        content: 'Create and apply the database migration for your model.',
        detail: 'BaseAPI generates migrations automatically by analyzing your model definitions.',
    },
    {
        label: 'Create Background Job',
        content: 'Create a job to handle product processing in the background.',
        detail: 'Jobs allow you to handle time-consuming tasks asynchronously, improving API performance.',
    },
    {
        label: 'Set up Internationalization',
        content: 'Add translation files for multi-language support.',
        detail: 'BaseAPI includes built-in I18n support with automatic token scanning and AI-powered translations.',
    },
    {
        label: 'Create a Controller',
        content: 'Build a comprehensive controller with validation, file uploads, caching, and background jobs.',
        detail: 'Controllers use PHP attributes for validation, support file uploads, and can dispatch background jobs.',
    },
    {
        label: 'Define Routes with Security',
        content: 'Set up protected routes with authentication, rate limiting, and middleware.',
        detail: 'Routes support multiple authentication methods and rate limiting for security and performance.',
    },
    {
        label: 'Set up API Authentication',
        content: 'Configure API token authentication for external integrations.',
        detail: 'BaseAPI supports both session-based and token-based authentication, allowing flexible access control.',
    },
    {
        label: 'Generate OpenAPI & TypeScript',
        content: 'Automatically generate API documentation and TypeScript types.',
        detail: 'BaseAPI analyzes your controllers to generate OpenAPI specs and TypeScript definitions automatically.',
    },
    {
        label: 'Test Your Enhanced API',
        content: 'Test all the new features including file uploads, authentication, and caching.',
        detail: 'Your API now includes comprehensive features for production use.',
    },
];

export default function FirstApi() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Your First API
            </Typography>

            <Typography variant="h5" color="text.secondary" paragraph>
                Build a comprehensive, production-ready API with advanced BaseAPI features including file uploads, 
                background jobs, caching, internationalization, and authentication.
            </Typography>

            <Callout type="info">
                <Typography>
                    This tutorial assumes you've already <Link to="/getting-started/installation">installed BaseAPI</Link> and have the development server running.
                </Typography>
            </Callout>

            {/* What We'll Build */}
            <Box sx={{ mb: 4 }}>
                <Typography variant="h2" gutterBottom>
                    What We'll Build
                </Typography>

                <Typography>
                    We'll create a comprehensive products API that demonstrates BaseAPI's key features:
                </Typography>

                <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', mb: 2 }}>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <ApiMethod method="GET" />
                        <Typography variant="body2" fontFamily="monospace">
                            /products
                        </Typography>
                    </Box>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <ApiMethod method="POST" />
                        <Typography variant="body2" fontFamily="monospace">
                            /products 🔐
                        </Typography>
                    </Box>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <ApiMethod method="GET" />
                        <Typography variant="body2" fontFamily="monospace">
                            /products/{'{id}'}
                        </Typography>
                    </Box>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <ApiMethod method="PUT" />
                        <Typography variant="body2" fontFamily="monospace">
                            /products/{'{id}'} 🔐
                        </Typography>
                    </Box>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <ApiMethod method="DELETE" />
                        <Typography variant="body2" fontFamily="monospace">
                            /products/{'{id}'} 🔐
                        </Typography>
                    </Box>
                </Box>

                <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
                    🔐 = Protected endpoints requiring authentication (session or API token)
                </Typography>

                <Typography variant="h6" gutterBottom sx={{ mt: 3 }}>
                    Features You'll Learn:
                </Typography>
                <Box component="ul" sx={{ pl: 3, mb: 2 }}>
                    <Box component="li">🎯 <strong>PHP Attributes Validation</strong> - Modern, declarative input validation</Box>
                    <Box component="li">📁 <strong>File Uploads</strong> - Image handling with automatic storage</Box>
                    <Box component="li">⚡ <strong>Background Jobs</strong> - Asynchronous task processing</Box>
                    <Box component="li">🌍 <strong>Internationalization</strong> - Multi-language support with AI translation</Box>
                    <Box component="li">🔑 <strong>API Authentication</strong> - Token-based authentication for external integrations</Box>
                    <Box component="li">💾 <strong>Intelligent Caching</strong> - Performance optimization with cache tagging</Box>
                    <Box component="li">🛡️ <strong>Rate Limiting</strong> - Built-in protection against abuse</Box>
                    <Box component="li">📋 <strong>OpenAPI & TypeScript</strong> - Automatic documentation and type generation</Box>
                </Box>
            </Box>

            {/* Step-by-step Tutorial */}
            <Stepper orientation="vertical">
                <Step active expanded>
                    <StepLabel>
                        <Typography variant="h6" fontWeight={600}>
                            {steps[0].label}
                        </Typography>
                    </StepLabel>
                    <StepContent>
                        <Typography color="text.secondary">
                            {steps[0].content}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            {steps[0].detail}
                        </Typography>

                        <CodeBlock
                            language="bash"
                            code="./mason make:model Product"
                            title="Generate Product model"
                        />

                        <Typography variant="body2" color="text.secondary">
                            This creates <code>app/Models/Product.php</code>. Update it to include image support, 
                            relationships, and indexes:
                        </Typography>

                        <CodeBlock
                            language="php"
                            code={modelCode}
                            title="app/Models/Product.php"
                        />

                        <Callout type="info">
                            <Typography>
                                <strong>Advanced Features:</strong> The model includes indexes for performance, relationships for data connections,
                                and custom accessors for computed fields like image URLs. BaseAPI automatically handles the database schema.
                            </Typography>
                        </Callout>
                    </StepContent>
                </Step>

                <Step active expanded>
                    <StepLabel>
                        <Typography variant="h6" fontWeight={600}>
                            {steps[1].label}
                        </Typography>
                    </StepLabel>
                    <StepContent>
                        <Typography color="text.secondary">
                            {steps[1].content}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            {steps[1].detail}
                        </Typography>

                        <CodeBlock
                            language="bash"
                            code={`# Generate migrations from your model changes
./mason migrate:generate

# Apply the migrations to your database
./mason migrate:apply`}
                        />

                        <Typography variant="body2" color="text.secondary" sx={{ mt: 2 }}>
                            The generate command scans your models, compares with your database, and creates
                            individual SQL migration statements in <code>storage/migrations.json</code>.
                        </Typography>
                    </StepContent>
                </Step>

                <Step active expanded>
                    <StepLabel>
                        <Typography variant="h6" fontWeight={600}>
                            {steps[2].label}
                        </Typography>
                    </StepLabel>
                    <StepContent>
                        <Typography color="text.secondary">
                            {steps[2].content}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            {steps[2].detail}
                        </Typography>

                        <CodeBlock
                            language="bash"
                            code="./mason make:job ProcessProductJob"
                            title="Generate background job"
                        />

                        <CodeBlock
                            language="php"
                            code={`<?php

namespace App\\Jobs;

use BaseApi\\Queue\\Job;
use App\\Models\\Product;
use App\\Services\\EmailService;
use BaseApi\\Logger;
use BaseApi\\App;

class ProcessProductJob extends Job
{
    protected int $maxRetries = 3;
    protected int $retryDelay = 30; // seconds
    
    public function __construct(
        private readonly string $productId
    ) {}
    
    public function handle(): void
    {
        $product = Product::find($this->productId);
        
        if (!$product) {
            return;
        }
        
        // Example: Send notification email about new product
        $emailService = new EmailService(new Logger(), App::config());
        $emailService->send(
            to: 'admin@example.com',
            subject: 'New Product Created',
            body: "Product '{$product->name}' was created with price {$product->price}"
        );
        
        // Example: Generate product thumbnails, update search index, etc.
        error_log("Processed product: {$product->name}");
    }
    
    public function failed(\\Throwable $exception): void
    {
        error_log("Failed to process product {$this->productId}: " . $exception->getMessage());
    }
}`}
                            title="app/Jobs/ProcessProductJob.php"
                        />
                    </StepContent>
                </Step>

                <Step active expanded>
                    <StepLabel>
                        <Typography variant="h6" fontWeight={600}>
                            {steps[3].label}
                        </Typography>
                    </StepLabel>
                    <StepContent>
                        <Typography color="text.secondary">
                            {steps[3].content}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            {steps[3].detail}
                        </Typography>

                        <CodeBlock
                            language="bash"
                            code={`# Add Spanish and French translations
./mason i18n:add-lang es --copy-from=en
./mason i18n:add-lang fr --copy-from=en

# Scan your code for translation tokens and update files
./mason i18n:scan --update`}
                        />

                        <CodeBlock
                            language="json"
                            code={`{
    "created_successfully": "Product '{name}' was created successfully!",
    "updated_successfully": "Product '{name}' was updated successfully!",
    "deleted_successfully": "Product '{name}' was deleted successfully!",
    "not_found": "Product not found"
}`}
                            title="translations/en/product.json"
                        />

                        <CodeBlock
                            language="json"
                            code={`{
    "created_successfully": "¡Producto '{name}' creado exitosamente!",
    "updated_successfully": "¡Producto '{name}' actualizado exitosamente!",
    "deleted_successfully": "¡Producto '{name}' eliminado exitosamente!",
    "not_found": "Producto no encontrado"
}`}
                            title="translations/es/product.json"
                        />

                        <Callout type="tip">
                            <Typography>
                                Use <code>./mason i18n:fill --provider=openai --locale=es</code> to automatically
                                translate missing tokens using AI. BaseAPI supports OpenAI and DeepL for translations.
                            </Typography>
                        </Callout>
                    </StepContent>
                </Step>

                <Step active expanded>
                    <StepLabel>
                        <Typography variant="h6" fontWeight={600}>
                            {steps[4].label}
                        </Typography>
                    </StepLabel>
                    <StepContent>
                        <Typography color="text.secondary">
                            {steps[4].content}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            {steps[4].detail}
                        </Typography>

                        <CodeBlock
                            language="bash"
                            code="./mason make:controller ProductController"
                            title="Generate ProductController"
                        />

                        <Typography variant="body2" color="text.secondary">
                            The generated <code>app/Controllers/ProductController.php</code> includes the basic structure. 
                            Update it with comprehensive features including validation attributes, file uploads, caching, and background jobs:
                        </Typography>

                        <CodeBlock
                            language="php"
                            code={controllerCode}
                            title="app/Controllers/ProductController.php"
                        />

                        <Callout type="tip">
                            <Typography>
                                <strong>Advanced Features:</strong> This controller demonstrates PHP 8+ attributes for validation,
                                file upload handling, caching with tags, background job dispatching, and internationalized responses.
                                BaseAPI handles all the complexity behind the scenes.
                            </Typography>
                        </Callout>
                    </StepContent>
                </Step>

                <Step active expanded>
                    <StepLabel>
                        <Typography variant="h6" fontWeight={600}>
                            {steps[5].label}
                        </Typography>
                    </StepLabel>
                    <StepContent>
                        <Typography color="text.secondary">
                            {steps[5].content}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            {steps[5].detail}
                        </Typography>

                        <Typography variant="body2" color="text.secondary">
                            Add comprehensive routes with authentication and rate limiting to <code>routes/api.php</code>:
                        </Typography>

                        <CodeBlock
                            language="php"
                            code={routesCode}
                            title="routes/api.php"
                        />

                        <Callout type="info">
                            <Typography>
                                <strong>Security by Default:</strong> Protected endpoints require authentication (session or API token),
                                while public endpoints are rate-limited. Different operations have different rate limits based on their impact.
                            </Typography>
                        </Callout>
                    </StepContent>
                </Step>

                <Step active expanded>
                    <StepLabel>
                        <Typography variant="h6" fontWeight={600}>
                            {steps[6].label}
                        </Typography>
                    </StepLabel>
                    <StepContent>
                        <Typography color="text.secondary">
                            {steps[6].content}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            {steps[6].detail}
                        </Typography>

                        <CodeBlock
                            language="bash"
                            code={`# First, create a user account (via signup endpoint or manually)
curl -X POST http://localhost:7879/auth/signup \\
  -H "Content-Type: application/json" \\
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "secure123"
  }'

# Login to get a session
curl -X POST http://localhost:7879/auth/login \\
  -H "Content-Type: application/json" \\
  -c cookies.txt \\
  -d '{
    "email": "john@example.com",
    "password": "secure123"
  }'`}
                        />

                        <CodeBlock
                            language="bash"
                            code={`# Create an API token using your session
curl -X POST http://localhost:7879/api-tokens \\
  -H "Content-Type: application/json" \\
  -b cookies.txt \\
  -d '{
    "name": "My API Token",
    "expires_at": "2024-12-31 23:59:59"
  }'`}
                            title="Create API Token"
                        />

                        <Callout type="warning">
                            <Typography>
                                <strong>Save Your Token:</strong> The API token is only shown once during creation.
                                Store it securely - you cannot retrieve it again. Use it in the Authorization header
                                as <code>Bearer your_token_here</code>.
                            </Typography>
                        </Callout>
                    </StepContent>
                </Step>

                <Step active expanded>
                    <StepLabel>
                        <Typography variant="h6" fontWeight={600}>
                            {steps[7].label}
                        </Typography>
                    </StepLabel>
                    <StepContent>
                        <Typography color="text.secondary">
                            {steps[7].content}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            {steps[7].detail}
                        </Typography>

                        <CodeBlock
                            language="bash"
                            code={`# Generate OpenAPI specification and TypeScript types
./mason types:generate

# This creates:
# - openapi.json (OpenAPI 3.0 specification)
# - types.ts (TypeScript type definitions)
# - types.d.ts (TypeScript declaration file)

# View the generated files
cat openapi.json
cat types.ts`}
                        />

                        <CodeBlock
                            language="typescript"
                            code={`// Generated TypeScript types (types.ts)
export interface Product {
  id: string;
  name: string;
  description?: string;
  price: number;
  image_path?: string;
  image_url?: string;
  created_at: string;
  updated_at: string;
}

export interface ProductCreateRequest {
  name: string;
  description?: string;
  price: number;
  image?: File;
}

export interface ApiResponse<T> {
  data?: T;
  message?: string;
  error?: string;
}`}
                            title="Generated TypeScript types"
                        />

                        <Callout type="tip">
                            <Typography>
                                <strong>Frontend Integration:</strong> Use the generated types in your React, Vue, or Angular
                                frontend for full type safety. The OpenAPI spec can be imported into Postman or used to generate
                                client SDKs in any language.
                            </Typography>
                        </Callout>
                    </StepContent>
                </Step>

                <Step active expanded>
                    <StepLabel>
                        <Typography variant="h6" fontWeight={600}>
                            {steps[8].label}
                        </Typography>
                    </StepLabel>
                    <StepContent>
                        <Typography color="text.secondary">
                            {steps[8].content}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            {steps[8].detail}
                        </Typography>

                        <CodeBlock
                            language="bash"
                            code={`# Start the development server
./mason serve

# In another terminal, start the queue worker to process background jobs
./mason queue:work`}
                            title="Start services"
                        />

                        <CodeBlock
                            language="bash"
                            code={testRequests}
                            title="Test your comprehensive API endpoints"
                        />

                        <CodeBlock
                            language="bash"
                            code={`# Optional: Create and run tests
./mason make:test ProductApiTest

# Run your tests
./vendor/bin/phpunit tests/Feature/ProductApiTest.php

# Check queue status
./mason queue:status

# View cache statistics
./mason cache:stats`}
                            title="Additional testing and monitoring"
                        />

                        <Callout type="success">
                            <Typography>
                                <strong>🎉 Congratulations!</strong> You've built a comprehensive, production-ready API with BaseAPI! 
                                Your products API now includes advanced features like file uploads, background jobs, caching, 
                                internationalization, API token authentication, rate limiting, and automatic documentation generation.
                            </Typography>
                        </Callout>
                    </StepContent>
                </Step>
            </Stepper>

            {/* Next Steps */}
            <Box sx={{ mt: 6, mb: 4 }}>
            <Typography variant="h2" gutterBottom>
                Next Steps
            </Typography>

            <Typography>
                Congratulations! You've built a comprehensive API with advanced BaseAPI features. 
                Here's what you can explore next:
            </Typography>

            <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', mb: 3 }}>
                <Button
                    component={Link}
                    to="/queue/overview"
                    variant="contained"
                    endIcon={<ArrowIcon />}
                >
                    Advanced Queue System
                </Button>

                <Button
                    component={Link}
                    to="/security/overview"
                    variant="outlined"
                    endIcon={<ArrowIcon />}
                >
                    Security Best Practices
                </Button>

                <Button
                    component={Link}
                    to="/configuration/caching"
                    variant="outlined"
                    endIcon={<ArrowIcon />}
                >
                    Advanced Caching
                </Button>

                <Button
                    component={Link}
                    to="/i18n/overview"
                    variant="text"
                    endIcon={<ArrowIcon />}
                >
                    Deep Dive I18n
                </Button>

                <Button
                    component={Link}
                    to="/deployment/production"
                    variant="text"
                    endIcon={<ArrowIcon />}
                >
                    Deploy to Production
                </Button>
            </Box>

            <Typography variant="h6" gutterBottom sx={{ mt: 4 }}>
                What You've Accomplished:
            </Typography>
            <Box component="ul" sx={{ pl: 3, mb: 2 }}>
                <Box component="li">✅ Built a full CRUD API with advanced validation</Box>
                <Box component="li">✅ Implemented file upload capabilities</Box>
                <Box component="li">✅ Added background job processing</Box>
                <Box component="li">✅ Set up multi-language support</Box>
                <Box component="li">✅ Configured authentication and rate limiting</Box>
                <Box component="li">✅ Implemented intelligent caching</Box>
                <Box component="li">✅ Generated API documentation and TypeScript types</Box>
            </Box>
            </Box>

            <Callout type="tip">
                <Typography>
                    <strong>Ready for production?</strong> Your API now includes enterprise-ready features! 
                    Check out our <Link to="/deployment/production">production deployment guide</Link> to learn about 
                    scaling, monitoring, and optimization, or explore <Link to="/security/overview">security best practices</Link> 
                    to further harden your application.
                </Typography>
            </Callout>
        </Box>
    );
}
