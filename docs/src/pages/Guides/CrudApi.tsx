
import { Box, Typography, Alert } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';

export default function CRUDAPI() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Building CRUD APIs
      </Typography>
      <Typography variant="h5" color="text.secondary" paragraph>
        Complete guide to building RESTful CRUD APIs with BaseAPI
      </Typography>

      <Typography paragraph>
        This guide walks through creating a complete CRUD (Create, Read, Update, Delete) API 
        using BaseAPI. We'll build a Product API with proper validation, error handling, 
        and best practices.
      </Typography>

      <Alert severity="info" sx={{ my: 3 }}>
        BaseAPI follows REST conventions and automatically handles parameter binding, validation, 
        and response formatting for clean, maintainable APIs.
      </Alert>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Step 1: Create the Model
      </Typography>

      <Typography paragraph>
        Start by defining your data structure with a BaseAPI model:
      </Typography>

      <CodeBlock language="bash" code={`# Generate the Product model
php bin/console make:model Product`} />

      <CodeBlock language="php" code={`<?php
// app/Models/Product.php

namespace App\\Models;

use BaseApi\\Models\\BaseModel;

class Product extends BaseModel
{
    // Basic product properties
    public string $name = '';
    public string $description = '';
    public float $price = 0.0;
    public string $sku = '';
    public bool $active = true;
    public int $stock = 0;
    
    // Define indexes for performance
    public static array $indexes = [
        'sku' => 'unique',
        'name' => 'index',
        'active' => 'index',
    ];
}`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Step 2: Create the Controller
      </Typography>

      <Typography paragraph>
        Generate a controller to handle HTTP requests:
      </Typography>

      <CodeBlock language="bash" code={`# Generate the Product controller
php bin/console make:controller ProductController`} />

      <CodeBlock language="php" code={`<?php
// app/Controllers/ProductController.php

namespace App\\Controllers;

use BaseApi\\Controllers\\Controller;
use BaseApi\\Http\\JsonResponse;
use App\\Models\\Product;

class ProductController extends Controller
{
    // Route parameters
    public string $id = '';
    
    // Request body properties
    public string $name = '';
    public string $description = '';
    public float $price = 0.0;
    public string $sku = '';
    public bool $active = true;
    public int $stock = 0;
    
    // GET /products - List products
    public function get(): JsonResponse
    {
        if ($this->id) {
            // Get specific product
            $product = Product::find($this->id);
            if (!$product) {
                return JsonResponse::notFound('Product not found');
            }
            return JsonResponse::ok($product->jsonSerialize());
        }
        
        // List products with pagination
        $result = Product::apiQuery($this->request, 50);
        
        return JsonResponse::ok([
            'data' => $result->items,
            'pagination' => [
                'page' => $result->page,
                'perPage' => $result->perPage,
                'total' => $result->total,
                'pages' => $result->pages,
            ]
        ]);
    }
    
    // POST /products - Create product
    public function post(): JsonResponse
    {
        $this->validate([
            'name' => 'required|string|max:200',
            'description' => 'string|max:1000',
            'price' => 'required|numeric|min:0',
            'sku' => 'required|string|unique:products,sku|max:50',
            'active' => 'boolean',
            'stock' => 'required|integer|min:0',
        ]);
        
        $product = new Product();
        $product->name = $this->name;
        $product->description = $this->description;
        $product->price = $this->price;
        $product->sku = $this->sku;
        $product->active = $this->active;
        $product->stock = $this->stock;
        
        $product->save();
        
        return JsonResponse::created($product->jsonSerialize());
    }
    
    // PUT /products/{id} - Update product
    public function put(): JsonResponse
    {
        $product = Product::find($this->id);
        if (!$product) {
            return JsonResponse::notFound('Product not found');
        }
        
        $this->validate([
            'name' => 'required|string|max:200',
            'price' => 'required|numeric|min:0',
            'sku' => 'required|string|unique:products,sku,' . $product->id,
            'stock' => 'required|integer|min:0',
        ]);
        
        $product->name = $this->name;
        $product->description = $this->description;
        $product->price = $this->price;
        $product->sku = $this->sku;
        $product->active = $this->active;
        $product->stock = $this->stock;
        
        $product->save();
        
        return JsonResponse::ok($product->jsonSerialize());
    }
    
    // DELETE /products/{id} - Delete product
    public function delete(): JsonResponse
    {
        $product = Product::find($this->id);
        if (!$product) {
            return JsonResponse::notFound('Product not found');
        }
        
        $product->delete();
        
        return JsonResponse::noContent();
    }
}`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Step 3: Define Routes
      </Typography>

      <Typography paragraph>
        Add routes to connect URLs to your controller:
      </Typography>

      <CodeBlock language="php" code={`<?php
// routes/api.php

use BaseApi\\App;
use App\\Controllers\\ProductController;
use BaseApi\\Http\\Middleware\\AuthMiddleware;
use BaseApi\\Http\\Middleware\\RateLimitMiddleware;

$router = App::router();

// Public product listing (with rate limiting)
$router->get('/products', [
    RateLimitMiddleware::class => ['limit' => '100/1h'],
    ProductController::class
]);

// Get specific product
$router->get('/products/{id}', [ProductController::class]);

// Protected endpoints (require authentication)
$router->post('/products', [
    AuthMiddleware::class,
    ProductController::class
]);

$router->put('/products/{id}', [
    AuthMiddleware::class,
    ProductController::class
]);

$router->delete('/products/{id}', [
    AuthMiddleware::class,
    ProductController::class
]);`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Step 4: Generate and Apply Migrations
            </Typography>

            <CodeBlock language="bash" code={`# Generate migrations from your model changes
php bin/console migrate:generate

# Review the generated migrations (optional)
cat storage/migrations.json

# Apply migrations to create the database table
php bin/console migrate:apply`} />

            <Typography paragraph>
                The migration system will automatically detect your Product model and generate the necessary
                SQL statements to create the table with proper indexes and constraints.
            </Typography>

      <Alert severity="success" sx={{ mt: 4 }}>
        <strong>Your CRUD API is now ready!</strong> It includes validation, error handling, 
        authentication, rate limiting, and automatic pagination.
      </Alert>
    </Box>
  );
}