<?php

/**
 * Example: Custom Method Routing
 *
 * This example demonstrates how to register routes with custom controller method names
 * using the array syntax [ControllerClass::class, 'methodName'].
 */

use BaseApi\Router;
use BaseApi\Controllers\Controller;
use BaseApi\Http\Response;

// Example Controller
class ProductController extends Controller
{
    // Custom method for listing products
    public function listProducts(): Response
    {
        $products = [
            ['id' => 1, 'name' => 'Product 1'],
            ['id' => 2, 'name' => 'Product 2'],
        ];

        return Response::json($products);
    }

    // Custom method for showing a single product
    public function showProduct(): Response
    {
        // Access route parameter via controller property binding
        return Response::json([
            'id' => $this->id,
            'name' => 'Product ' . $this->id
        ]);
    }

    // Custom method for creating a product
    public function createProduct(): Response
    {
        // Access request data via controller property binding
        return Response::json([
            'message' => 'Product created',
            'name' => $this->name
        ], 201);
    }

    // Custom method for updating a product
    public function updateProduct(): Response
    {
        return Response::json([
            'message' => 'Product updated',
            'id' => $this->id
        ]);
    }

    // Custom method for deleting a product
    public function deleteProduct(): Response
    {
        return Response::json([
            'message' => 'Product deleted',
            'id' => $this->id
        ]);
    }
}

// Register routes with custom method names
$router = new Router();

// Standard route registration (uses HTTP method-based routing)
// This would call ProductController::get(), ProductController::post(), etc.
// $router->get('/old-style', [ProductController::class]);

// NEW: Custom method routing - specify exact method to call
// Syntax: [[ControllerClass::class, 'methodName']]

// List all products
$router->get('/products', [[ProductController::class, 'listProducts']]);

// Show a single product
$router->get('/products/{id}', [[ProductController::class, 'showProduct']]);

// Create a product
$router->post('/products', [[ProductController::class, 'createProduct']]);

// Update a product
$router->put('/products/{id}', [[ProductController::class, 'updateProduct']]);

// Delete a product
$router->delete('/products/{id}', [[ProductController::class, 'deleteProduct']]);

// You can also use custom methods with middleware
$router->get('/admin/products', [
    'AuthMiddleware',
    'AdminMiddleware',
    [ProductController::class, 'listProducts']
]);

// Example with optioned middleware
$router->post('/products', [
    ['CorsMiddleware' => ['origins' => ['https://example.com']]],
    [ProductController::class, 'createProduct']
]);

/**
 * Benefits of Custom Method Routing:
 *
 * 1. Semantic Method Names: Use descriptive method names that clearly indicate what the method does
 *    instead of generic names like get(), post(), etc.
 *
 * 2. Multiple Routes per HTTP Method: You can have multiple GET routes pointing to the same
 *    controller with different methods (e.g., listProducts, showProduct).
 *
 * 3. Better IDE Support: Clearer method names improve code navigation and refactoring.
 *
 * 4. Resource Controllers: Easily implement RESTful resource controllers with standard method names
 *    (index, show, store, update, destroy).
 *
 * 5. Backward Compatible: Still supports the standard syntax where methods are named after
 *    HTTP verbs (get, post, put, delete, patch).
 */

/**
 * Standard vs Custom Method Comparison:
 *
 * Standard (HTTP method-based):
 * $router->get('/products', [ProductController::class]);
 * // Calls ProductController::get()
 *
 * Custom (explicit method name):
 * $router->get('/products', [[ProductController::class, 'listProducts']]);
 * // Calls ProductController::listProducts()
 */
