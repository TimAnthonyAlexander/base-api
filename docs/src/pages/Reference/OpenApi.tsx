
import { Box, Typography, Alert, List, ListItem, ListItemText } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

export default function OpenAPI() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                OpenAPI Specification
            </Typography>
            <Typography variant="h5" color="text.secondary" paragraph>
                Automatic OpenAPI 3.0 specification generation from your BaseAPI code
            </Typography>

            <Typography>
                BaseAPI automatically generates OpenAPI (Swagger) specifications from your controllers, models,
                and routes. This provides comprehensive API documentation, enables frontend code generation,
                and facilitates API testing and integration.
            </Typography>

            <Alert severity="info" sx={{ my: 3 }}>
                BaseAPI generates OpenAPI specs by analyzing your routes, controllers, models, and validation rules.
                No manual documentation required - your code is the source of truth.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Generating OpenAPI Specs
            </Typography>

            <Typography>
                Use the BaseAPI CLI to generate OpenAPI specifications:
            </Typography>

            <CodeBlock language="bash" code={`# Generate OpenAPI specification only
php bin/console types:generate --openapi

# Generate both OpenAPI and TypeScript types
php bin/console types:generate --openapi --typescript

# Generate with custom output paths
php bin/console types:generate --openapi --output-openapi custom-api.json

# Generate for specific version
php bin/console types:generate --openapi --version "1.2.0"`} />

            <Typography>
                This generates an <code>openapi.json</code> file in your project root containing the complete
                API specification.
            </Typography>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                What Gets Generated
            </Typography>

            <Typography>
                BaseAPI analyzes your code and generates comprehensive OpenAPI documentation:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="API Routes"
                        secondary="All defined routes with HTTP methods, paths, and parameters"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Request/Response Schemas"
                        secondary="Data structures based on controller properties and model definitions"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Validation Rules"
                        secondary="Parameter validation requirements from controller validation rules"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Error Responses"
                        secondary="Standard error response formats (400, 401, 404, 422, 500)"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Model Schemas"
                        secondary="Data models with property types and relationships"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Authentication"
                        secondary="Security schemes for protected endpoints"
                    />
                </ListItem>
            </List>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Example Generated Specification
            </Typography>

            <Typography>
                Here's what BaseAPI generates for a typical controller:
            </Typography>

            <CodeBlock language="php" code={`<?php
// Controller
class UserController extends Controller
{
    public string $id = '';
    public string $name = '';
    public string $email = '';
    public int $age = 0;
    
    public function get(): JsonResponse
    {
        // GET /users/{id} or GET /users
    }
    
    public function post(): JsonResponse
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'age' => 'required|integer|min:18',
        ]);
        
        // Create user logic...
    }
}`} />

            <CodeBlock language="json" code={`{
  "openapi": "3.0.3",
  "info": {
    "title": "BaseAPI Application",
    "version": "1.0.0",
    "description": "API documentation generated from BaseAPI"
  },
  "servers": [
    {
      "url": "http://localhost:7879",
      "description": "Development server"
    }
  ],
  "paths": {
    "/users": {
      "get": {
        "summary": "List users",
        "operationId": "getUsers",
        "responses": {
          "200": {
            "description": "Success",
            "content": {
              "application/json": {
                "schema": {
                  "type": "array",
                  "items": {
                    "$ref": "#/components/schemas/User"
                  }
                }
              }
            }
          }
        }
      },
      "post": {
        "summary": "Create user",
        "operationId": "createUser",
        "requestBody": {
          "required": true,
          "content": {
            "application/json": {
              "schema": {
                "$ref": "#/components/schemas/CreateUserRequest"
              }
            }
          }
        },
        "responses": {
          "201": {
            "description": "User created",
            "content": {
              "application/json": {
                "schema": {
                  "$ref": "#/components/schemas/User"
                }
              }
            }
          },
          "422": {
            "description": "Validation error",
            "content": {
              "application/json": {
                "schema": {
                  "$ref": "#/components/schemas/ValidationError"
                }
              }
            }
          }
        }
      }
    },
    "/users/{id}": {
      "get": {
        "summary": "Get user by ID",
        "operationId": "getUserById",
        "parameters": [
          {
            "name": "id",
            "in": "path",
            "required": true,
            "schema": {
              "type": "string",
              "format": "uuid"
            }
          }
        ],
        "responses": {
          "200": {
            "description": "User found",
            "content": {
              "application/json": {
                "schema": {
                  "$ref": "#/components/schemas/User"
                }
              }
            }
          },
          "404": {
            "description": "User not found"
          }
        }
      }
    }
  },
  "components": {
    "schemas": {
      "User": {
        "type": "object",
        "properties": {
          "id": {
            "type": "string",
            "format": "uuid"
          },
          "name": {
            "type": "string"
          },
          "email": {
            "type": "string",
            "format": "email"
          },
          "age": {
            "type": "integer",
            "minimum": 18
          },
          "created_at": {
            "type": "string",
            "format": "date-time"
          }
        }
      },
      "CreateUserRequest": {
        "type": "object",
        "required": ["name", "email", "age"],
        "properties": {
          "name": {
            "type": "string",
            "maxLength": 100
          },
          "email": {
            "type": "string",
            "format": "email"
          },
          "age": {
            "type": "integer",
            "minimum": 18
          }
        }
      },
      "ValidationError": {
        "type": "object",
        "properties": {
          "error": {
            "type": "string"
          },
          "message": {
            "type": "string"
          },
          "errors": {
            "type": "object",
            "additionalProperties": {
              "type": "array",
              "items": {
                "type": "string"
              }
            }
          },
          "status": {
            "type": "integer"
          }
        }
      }
    }
  }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Using Generated Specifications
            </Typography>

            <Typography>
                The generated OpenAPI specification can be used for various purposes:
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                API Documentation
            </Typography>

            <CodeBlock language="bash" code={`# Serve interactive documentation with Swagger UI
npx swagger-ui-serve openapi.json

# Or use online Swagger Editor
# Upload openapi.json to https://editor.swagger.io/

# Generate static documentation
npx redoc-cli openapi.json --output docs.html`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Client Code Generation
            </Typography>

            <CodeBlock language="bash" code={`# Generate TypeScript client
npx @openapitools/openapi-generator-cli generate \\
  -i openapi.json \\
  -g typescript-fetch \\
  -o ./client

# Generate Python client
npx @openapitools/openapi-generator-cli generate \\
  -i openapi.json \\
  -g python \\
  -o ./python-client

# Generate PHP client
npx @openapitools/openapi-generator-cli generate \\
  -i openapi.json \\
  -g php \\
  -o ./php-client`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                API Testing
            </Typography>

            <CodeBlock language="bash" code={`# Test with Postman
# Import openapi.json into Postman to create collection

# Test with Insomnia
# Import openapi.json into Insomnia

# Automated testing with Dredd
npm install -g dredd
dredd openapi.json http://localhost:7879`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Customizing Generated Specs
            </Typography>

            <Typography>
                You can customize the generated OpenAPI specification using PHP attributes:
            </Typography>

            <CodeBlock language="php" code={`<?php
use BaseApi\\Http\\Attributes\\OpenAPI;

#[OpenAPI\\Operation(
    summary: 'Create a new user account',
    description: 'Creates a new user with the provided information. Email must be unique.',
    tags: ['Users', 'Authentication']
)]
class UserController extends Controller
{
    #[OpenAPI\\Parameter(
        description: 'User ID (UUID format)',
        example: '123e4567-e89b-12d3-a456-426614174000'
    )]
    public string $id = '';
    
    #[OpenAPI\\RequestBody(
        description: 'User creation data',
        example: [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 25
        ]
    )]
    public function post(): JsonResponse
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'age' => 'required|integer|min:18',
        ]);
        
        // Implementation...
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Configuration Options
            </Typography>

            <Typography>
                Configure OpenAPI generation in your environment or CLI options:
            </Typography>

            <CodeBlock language="bash" code={`# Environment variables for OpenAPI generation
OPENAPI_TITLE="My API"
OPENAPI_VERSION="2.1.0"
OPENAPI_DESCRIPTION="My awesome API built with BaseAPI"
OPENAPI_CONTACT_NAME="API Support"
OPENAPI_CONTACT_EMAIL="api-support@example.com"
OPENAPI_LICENSE_NAME="MIT"
OPENAPI_LICENSE_URL="https://opensource.org/licenses/MIT"

# Server configuration
OPENAPI_SERVER_URL="https://api.example.com"
OPENAPI_SERVER_DESCRIPTION="Production server"`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Integration with CI/CD
            </Typography>

            <Typography>
                Automate OpenAPI spec generation in your deployment pipeline:
            </Typography>

            <CodeBlock language="yaml" code={`# GitHub Actions example
name: Generate API Docs
on:
  push:
    branches: [main]
    
jobs:
  docs:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          
      - name: Install dependencies
        run: composer install --no-dev --optimize-autoloader
        
      - name: Generate OpenAPI spec
        run: php bin/console types:generate --openapi --typescript
        
      - name: Deploy docs
        run: |
          npx swagger-ui-serve openapi.json --port 3000 &
          # Deploy to your docs hosting service`} />

            <Callout type="tip" title="Keep Specs Updated">
                Run <code>types:generate</code> after making changes to controllers or models.
                Consider adding this to your development workflow or CI/CD pipeline.
            </Callout>

            <Alert severity="success" sx={{ mt: 4 }}>
                <strong>Best Practices:</strong>
                <br />• Generate specs after code changes
                <br />• Use meaningful controller and method names
                <br />• Add validation rules for better parameter documentation
                <br />• Include example values in validation attributes
                <br />• Version your API specifications
                <br />• Integrate generation into CI/CD pipeline
            </Alert>
        </Box>
    );
}
