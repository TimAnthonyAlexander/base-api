
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
        Build a complete CRUD API for products in just a few minutes.
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
        
        <Typography paragraph>
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
            <Typography paragraph color="text.secondary">
              {steps[0].content}
            </Typography>
            <Typography paragraph variant="body2" color="text.secondary">
              {steps[0].detail}
            </Typography>
            
            <CodeBlock
              language="bash"
              code="php bin/console make:model Product"
              title="Generate Product model"
            />
            
            <Typography paragraph variant="body2" color="text.secondary">
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
            <Typography paragraph color="text.secondary">
              {steps[1].content}
            </Typography>
            <Typography paragraph variant="body2" color="text.secondary">
              {steps[1].detail}
            </Typography>
            
            <CodeBlock
              language="bash"
              code={`# Generate migrations from your model changes
php bin/console migrate:generate

# Apply the migrations to your database
php bin/console migrate:apply`}
            />
            
            <Typography paragraph variant="body2" color="text.secondary" sx={{ mt: 2 }}>
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
            <Typography paragraph color="text.secondary">
              {steps[2].content}
            </Typography>
            <Typography paragraph variant="body2" color="text.secondary">
              {steps[2].detail}
            </Typography>
            
            <CodeBlock
              language="bash"
              code="php bin/console make:controller ProductController"
              title="Generate ProductController"
            />
            
            <Typography paragraph variant="body2" color="text.secondary">
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
            <Typography paragraph color="text.secondary">
              {steps[3].content}
            </Typography>
            <Typography paragraph variant="body2" color="text.secondary">
              {steps[3].detail}
            </Typography>
            
            <Typography paragraph variant="body2" color="text.secondary">
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
            <Typography paragraph color="text.secondary">
              {steps[4].content}
            </Typography>
            <Typography paragraph variant="body2" color="text.secondary">
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
        
        <Typography paragraph>
          Now that you have a basic API working, you can:
        </Typography>

        <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap' }}>
          <Button
            component={Link}
            to="/guides/crud-api"
            variant="contained"
            endIcon={<ArrowIcon />}
          >
            Complete CRUD Guide
          </Button>
          
          <Button
            component={Link}
            to="/architecture/validation"
            variant="outlined"
            endIcon={<ArrowIcon />}
          >
            Add Validation
          </Button>
          
          <Button
            component={Link}
            to="/getting-started/project-structure"
            variant="text"
            endIcon={<ArrowIcon />}
          >
            Explore Project Structure
          </Button>
        </Box>
      </Box>

      <Callout type="tip">
        <Typography>
          <strong>Want to learn more?</strong> Check out our <Link to="/guides/crud-api">complete CRUD guide</Link> to add UPDATE and DELETE operations, or explore <Link to="/architecture/validation">input validation</Link> to make your API more robust.
        </Typography>
      </Callout>
    </Box>
  );
}
