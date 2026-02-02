# Custom Method Routing

BaseAPI now supports registering routes with custom controller method names using array syntax.

## Overview

Previously, routes were mapped to controller methods based on HTTP methods (e.g., `GET` → `get()`, `POST` → `post()`). With custom method routing, you can specify exactly which controller method should handle a route.

## Syntax

### Standard Routing (HTTP Method-Based)

```php
$router->get('/products', [ProductController::class]);
// Calls ProductController::get()

$router->post('/products', [ProductController::class]);
// Calls ProductController::post()
```

### Custom Method Routing

```php
$router->get('/products', [[ProductController::class, 'listProducts']]);
// Calls ProductController::listProducts()

$router->post('/products', [[ProductController::class, 'createProduct']]);
// Calls ProductController::createProduct()
```

**Note:** The custom method syntax requires wrapping the `[Class, 'method']` array in an outer array. This is because the second parameter is a pipeline array that can contain middleware.

## Complete Example

```php
use BaseApi\Router;
use BaseApi\Controllers\Controller;
use BaseApi\Http\Response;

class ProductController extends Controller
{
    public function listProducts(): Response
    {
        // List all products
        return Response::json(['products' => []]);
    }

    public function showProduct(): Response
    {
        // Show single product (id available via $this->id)
        return Response::json(['id' => $this->id]);
    }

    public function createProduct(): Response
    {
        // Create a new product
        return Response::json(['message' => 'Product created'], 201);
    }

    public function updateProduct(): Response
    {
        // Update product
        return Response::json(['message' => 'Product updated']);
    }

    public function deleteProduct(): Response
    {
        // Delete product
        return Response::json(['message' => 'Product deleted']);
    }
}

$router = new Router();

// Register routes with custom methods
$router->get('/products', [[ProductController::class, 'listProducts']]);
$router->get('/products/{id}', [[ProductController::class, 'showProduct']]);
$router->post('/products', [[ProductController::class, 'createProduct']]);
$router->put('/products/{id}', [[ProductController::class, 'updateProduct']]);
$router->delete('/products/{id}', [[ProductController::class, 'deleteProduct']]);
```

## Using with Middleware

Custom methods work seamlessly with middleware:

```php
// Single middleware
$router->get('/products', [
    'AuthMiddleware',
    [ProductController::class, 'listProducts']
]);

// Multiple middleware
$router->post('/products', [
    'AuthMiddleware',
    'ValidationMiddleware',
    [ProductController::class, 'createProduct']
]);

// Optioned middleware
$router->get('/products', [
    ['CorsMiddleware' => ['origins' => ['https://example.com']]],
    [ProductController::class, 'listProducts']
]);
```

## Dynamic Route Parameters

Custom methods work with dynamic route parameters. Parameters are automatically bound to controller properties:

```php
class ProductController extends Controller
{
    public string $id;
    public string $category;

    public function showProductInCategory(): Response
    {
        return Response::json([
            'category' => $this->category,
            'product_id' => $this->id
        ]);
    }
}

$router->get('/categories/{category}/products/{id}', [
    [ProductController::class, 'showProductInCategory']
]);

// GET /categories/electronics/products/123
// Results in: $controller->category = 'electronics', $controller->id = '123'
```

## Benefits

1. **Semantic Naming**: Use descriptive method names that clearly indicate their purpose
   - `listProducts()` vs `get()`
   - `createProduct()` vs `post()`

2. **Multiple Routes per HTTP Method**: Have multiple GET routes to the same controller with different methods
   ```php
   $router->get('/products', [[ProductController::class, 'listProducts']]);
   $router->get('/products/featured', [[ProductController::class, 'listFeaturedProducts']]);
   $router->get('/products/{id}', [[ProductController::class, 'showProduct']]);
   ```

3. **RESTful Resource Controllers**: Implement standard resource controller patterns
   ```php
   $router->get('/products', [[ProductController::class, 'index']]);
   $router->get('/products/{id}', [[ProductController::class, 'show']]);
   $router->post('/products', [[ProductController::class, 'store']]);
   $router->put('/products/{id}', [[ProductController::class, 'update']]);
   $router->delete('/products/{id}', [[ProductController::class, 'destroy']]);
   ```

4. **Better IDE Support**: IDEs can better navigate to specific methods and provide autocomplete

5. **Backward Compatible**: Existing routes using standard HTTP method-based routing continue to work

## Error Handling

If a specified custom method doesn't exist on the controller, the framework returns a 500 Internal Server Error with a JSON response:

```json
{
  "error": "Controller method not found",
  "requestId": "..."
}
```

## Migration Guide

To migrate existing controllers to use custom methods:

### Before
```php
class UserController extends Controller
{
    public function get(): Response
    {
        // List users
    }

    public function post(): Response
    {
        // Create user
    }
}

$router->get('/users', [UserController::class]);
$router->post('/users', [UserController::class]);
```

### After
```php
class UserController extends Controller
{
    public function index(): Response
    {
        // List users
    }

    public function store(): Response
    {
        // Create user
    }
}

$router->get('/users', [[UserController::class, 'index']]);
$router->post('/users', [[UserController::class, 'store']]);
```

## Route Compilation

Custom method routes are fully supported by the route compiler and benefit from the same performance optimizations as standard routes:

```php
// Routes with custom methods are compiled and cached
$router->compile(storage_path('cache/routes.php'));
```

The compiled cache stores both the controller class and custom method name, ensuring fast route matching with zero performance overhead.
