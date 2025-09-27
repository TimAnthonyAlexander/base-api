
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

class Product extends BaseModel
{
    public string $name = '';
    public ?string $description = null;
    public float $price = 0.0;
    
    // id, created_at, updated_at are inherited from BaseModel
}`;

const controllerCode = `<?php

namespace App\\Controllers;

use BaseApi\\Controllers\\Controller;
use BaseApi\\Http\\JsonResponse;
use App\\Models\\Product;

/**
 * ProductController
 * 
 * Handles CRUD operations for products.
 */
class ProductController extends Controller
{
    // Define public properties to auto-populate from request data
    public string $name = '';
    public ?string $description = null;
    public float $price = 0.0;
    public string $id = '';
    
    public function post(): JsonResponse
    {
        // Validate input (BaseAPI provides automatic validation)
        $this->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0'
        ]);
        
        $product = new Product();
        $product->name = $this->name;
        $product->description = $this->description ?? null;
        $product->price = (float) $this->price;
        $product->save();
        
        return JsonResponse::created($product);
    }
    
    public function get(): JsonResponse
    {
        // If ID is provided as route parameter, return single product
        if (!empty($this->id)) {
            $product = Product::find($this->id);
            
            if (!$product) {
                return JsonResponse::notFound('Product not found');
            }
            
            return JsonResponse::ok($product);
        }
        
        // Otherwise return all products
        $products = Product::all();
        return JsonResponse::ok($products);
    }
}`;

const routesCode = `<?php

use BaseApi\\App;
use App\\Controllers\\ProductController;

$router = App::router();

$router->get('/products', [ProductController::class]);
$router->post('/products', [ProductController::class]);
$router->get('/products/{id}', [ProductController::class]);`;

const testRequests = `# Get all products
curl http://localhost:7879/products

# Create a new product
curl -X POST http://localhost:7879/products \\
  -H "Content-Type: application/json" \\
  -d '{"name": "Laptop", "description": "Gaming laptop", "price": 999.99}'

# Get a specific product (replace {id} with actual ID)
curl http://localhost:7879/products/1`;

const steps = [
    {
        label: 'Create a Model',
        content: 'Start by creating a Product model that defines your data structure.',
        detail: 'Models in BaseAPI extend BaseModel and use public properties to define database fields. The framework automatically handles migrations based on your model definitions.',
    },
    {
        label: 'Generate Migration',
        content: 'Create and apply the database migration for your model.',
        detail: 'BaseAPI generates migrations automatically by analyzing your model definitions.',
    },
    {
        label: 'Create a Controller',
        content: 'Build a controller to handle HTTP requests for your products.',
        detail: 'Controllers handle the business logic for your API endpoints. BaseAPI automatically injects request data based on method names.',
    },
    {
        label: 'Define Routes',
        content: 'Set up routes to connect URLs to your controller methods.',
        detail: 'Routes map HTTP methods and paths to controller actions. BaseAPI uses convention over configuration for method routing.',
    },
    {
        label: 'Test Your API',
        content: 'Make requests to test your new endpoints.',
        detail: 'Your API is now ready to handle CRUD operations for products.',
    },
];

export default function FirstApi() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Your First API
            </Typography>

            <Typography variant="h5" color="text.secondary" paragraph>
                Build a complete CRUD API for products, then enhance it step-by-step with BaseAPI's advanced features.
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
                    We'll create a simple products API with the following endpoints:
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
                            /products
                        </Typography>
                    </Box>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <ApiMethod method="GET" />
                        <Typography variant="body2" fontFamily="monospace">
                            /products/{'{id}'}
                        </Typography>
                    </Box>
                </Box>

                <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
                    Once you have this working, we'll show you how to enhance it with BaseAPI's advanced features!
                </Typography>
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
                            This creates <code>app/Models/Product.php</code> with a comprehensive template.
                            For this tutorial, update it with these simple properties:
                        </Typography>

                        <CodeBlock
                            language="php"
                            code={modelCode}
                            title="app/Models/Product.php"
                        />

                        <Callout type="info">
                            <Typography>
                                The generated model template includes examples of indexes, relations, and column overrides.
                                You can use these features as your application grows, but for now, simple public properties are all you need.
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
                            code="./mason make:controller ProductController"
                            title="Generate ProductController"
                        />

                        <Typography variant="body2" color="text.secondary">
                            The generated <code>app/Controllers/ProductController.php</code> includes the basic structure with JsonResponse imports.
                            Update it with the Product model import and CRUD methods:
                        </Typography>

                        <CodeBlock
                            language="php"
                            code={controllerCode}
                            title="app/Controllers/ProductController.php"
                        />

                        <Callout type="tip">
                            <Typography>
                                <strong>Convention over Configuration:</strong> Method names like <code>get()</code>, <code>post()</code> automatically map to HTTP methods.
                                Public properties like <code>$id</code>, <code>$name</code> are auto-populated from request data (JSON body, query params, or route parameters).
                            </Typography>
                        </Callout>
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

                        <Typography variant="body2" color="text.secondary">
                            Add the following routes to <code>routes/api.php</code>:
                        </Typography>

                        <CodeBlock
                            language="php"
                            code={routesCode}
                            title="routes/api.php"
                        />
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
                            code={testRequests}
                            title="Test your API endpoints"
                        />

                        <Callout type="success">
                            <Typography>
                                <strong>Congratulations!</strong> You've just built your first BaseAPI endpoint. Your products API is now fully functional with create, read operations.
                                The framework handles validation, database operations, JSON responses, and error handling automatically.
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

            </Box>

            {/* Enhancement Sections */}
            <Typography variant="h2" gutterBottom sx={{ mt: 6 }}>
                Enhance Your API
            </Typography>
            
            <Typography>
                Now that you have a working API, let's enhance it step by step with BaseAPI's powerful features. 
                Each section builds on your existing code, so you can see how easy it is to add functionality.
            </Typography>

            {/* Caching Enhancement */}
            <Box sx={{ mt: 4 }}>
                <Typography variant="h3" gutterBottom>
                    🚀 Add Caching for Better Performance
                </Typography>
                
                <Typography>
                    Let's add intelligent caching to your existing API to improve performance:
                </Typography>

                <CodeBlock
                    language="php"
                    code={`<?php
// Update your existing ProductController.php

// Add this import at the top
use BaseApi\\Cache\\Cache;

// Update your get() method:
public function get(): JsonResponse
{
    if (!empty($this->id)) {
        // Try cache first for single products
        $cacheKey = "product:{$this->id}";
        $product = Cache::get($cacheKey);
        
        if (!$product) {
            $product = Product::find($this->id);
            
            if (!$product) {
                return JsonResponse::notFound('Product not found');
            }
            
            // Cache for 1 hour
            Cache::put($cacheKey, $product, 3600);
        }
        
        return JsonResponse::ok($product);
    }
    
    // Cache all products list
    $products = Product::cached()->all();
    return JsonResponse::ok($products);
}

// Update your post() method to clear cache:
public function post(): JsonResponse
{
    // ... existing validation and save code ...
    
    $product->save();
    
    // Clear the products cache when new products are added
    Cache::tags(['products'])->flush();
    
    return JsonResponse::created($product);
}`}
                    title="Enhanced ProductController.php with caching"
                />

                <Callout type="tip">
                    <Typography>
                        <strong>Smart Caching:</strong> BaseAPI's cache system automatically handles cache invalidation when models are updated.
                        Use <code>Cache::tags()</code> to group related cache entries and clear them together.
                    </Typography>
                </Callout>
            </Box>

            {/* File Upload Enhancement */}
            <Box sx={{ mt: 4 }}>
                <Typography variant="h3" gutterBottom>
                    📁 Add File Upload Support
                </Typography>
                
                <Typography>
                    Let's add image upload capability to your products:
                </Typography>

                <CodeBlock
                    language="php"
                    code={`<?php
// Update your Product model (app/Models/Product.php)

use BaseApi\\Storage\\Storage;

class Product extends BaseModel
{
    public string $name = '';
    public ?string $description = null;
    public float $price = 0.0;
    public ?string $image_path = null;  // Add this line
    
    // Add this method for image URLs
    public function getImageUrl(): ?string
    {
        return $this->image_path ? Storage::url($this->image_path) : null;
    }
    
    // Override to include image URL in JSON
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['image_url'] = $this->getImageUrl();
        return $data;
    }
}`}
                    title="Enhanced Product.php with image support"
                />

                <CodeBlock
                    language="php"
                    code={`<?php
// Update your ProductController.php

// Add these imports at the top
use BaseApi\\Http\\UploadedFile;
use BaseApi\\Storage\\Storage;

class ProductController extends Controller
{
    public string $name = '';
    public ?string $description = null;
    public float $price = 0.0;
    public string $id = '';
    public ?UploadedFile $image = null;  // Add this property
    
    public function post(): JsonResponse
    {
        // Add image validation
        $this->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'image' => 'file|mimes:jpg,jpeg,png,gif|max:5120'  // 5MB max
        ]);
        
        $product = new Product();
        $product->name = $this->name;
        $product->description = $this->description ?? null;
        $product->price = (float) $this->price;
        
        // Handle image upload
        if ($this->image instanceof UploadedFile) {
            $imagePath = $this->image->store('products');
            $product->image_path = $imagePath;
        }
        
        $product->save();
        
        return JsonResponse::created($product);
    }
}`}
                    title="Enhanced ProductController.php with file uploads"
                />

                <CodeBlock
                    language="bash"
                    code={`# Test file upload with curl
curl -X POST http://localhost:7879/products \\
  -F "name=Gaming Laptop" \\
  -F "description=High-performance gaming laptop" \\
  -F "price=1299.99" \\
  -F "image=@laptop.jpg"
  
# Don't forget to run migrations for the new image_path field
./mason migrate:generate
./mason migrate:apply`}
                    title="Test file upload"
                />
            </Box>

            {/* Summary */}
            <Box sx={{ 
                mt: 6, 
                mb: 4, 
                p: 3, 
                bgcolor: 'background.paper',
                borderRadius: 2,
                border: 1,
                borderColor: 'divider'
            }}>
                <Typography variant="h6" gutterBottom>
                    🎉 What You Can Do Next
                </Typography>
                
                <Typography paragraph>
                    You now have a solid foundation! Your API supports basic CRUD operations, and you've seen how easy it is 
                    to add advanced features. You can explore more BaseAPI capabilities like:
                </Typography>
                
                <Box component="ul" sx={{ pl: 3, mb: 2 }}>
                    <Box component="li"><Link to="/queue/overview">Background Job Processing</Link> - Handle time-consuming tasks asynchronously</Box>
                    <Box component="li"><Link to="/security/api-token-auth">API Token Authentication</Link> - Secure your endpoints</Box>
                    <Box component="li"><Link to="/i18n/overview">Internationalization</Link> - Support multiple languages</Box>
                    <Box component="li"><Link to="/cli/overview">Advanced CLI Commands</Link> - Automate development tasks</Box>
                    <Box component="li"><Link to="/deployment/production">Production Deployment</Link> - Deploy with confidence</Box>
                </Box>
                
                <Typography variant="body2" color="text.secondary">
                    Each feature integrates seamlessly with your existing code - that's the power of BaseAPI's design!
                </Typography>
            </Box>

            <Callout type="tip">
                <Typography>
                    <strong>Keep Learning:</strong> Check out our <Link to="/guides/crud-api">complete CRUD guide</Link> for more advanced patterns, 
                    or explore <Link to="/architecture/validation">input validation</Link> to make your API even more robust.
                </Typography>
            </Callout>
        </Box>
    );
}
