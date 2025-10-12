
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

            <CodeBlock language="bash" code={`# Generate both OpenAPI and TypeScript (default behavior)
./mason types:generate

# Generate complete SDK including React hooks
./mason types:generate --all

# Generate with custom output paths
./mason types:generate --out-openapi=custom-api.json --out-ts=my-types.ts

# Generate only OpenAPI specification
./mason types:generate --out-openapi=openapi.json`} />

            <Callout type="info" title="TypeScript SDK Generation">
                BaseAPI can generate a complete TypeScript SDK with React hooks.
                See <a href="/reference/typescript-sdk" style={{ color: 'inherit' }}>TypeScript SDK Generation</a> for details.
            </Callout>

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
                        primary="Request Parameters"
                        secondary="Path, query, and body parameters from controller properties"
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
    "title": "BaseApi",
    "version": "1.0.0",
    "description": "Generated API documentation from BaseApi controllers"
  },
  "servers": [
    {
      "url": "http://localhost:7879"
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
                  "$ref": "#/components/schemas/ErrorResponse"
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
      "ErrorResponse": {
        "type": "object",
        "properties": {
          "error": {
            "type": "string"
          },
          "requestId": {
            "type": "string"
          },
          "errors": {
            "type": "object",
            "additionalProperties": {
              "type": "string"
            }
          }
        },
        "required": ["error", "requestId"]
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
                You can customize the generated OpenAPI specification using PHP attributes. The main attributes are:
            </Typography>
            
            <List sx={{ my: 2 }}>
                <ListItem>
                    <ListItemText
                        primary="#[ResponseType]"
                        secondary="Defines the response data shape and status codes for controller methods"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="#[Tag]"
                        secondary="Groups endpoints by category in the generated documentation"
                    />
                </ListItem>
            </List>
            
            <Typography>
                Example usage:
            </Typography>

            <CodeBlock language="php" code={`<?php
use BaseApi\\Http\\Attributes\\ResponseType;
use BaseApi\\Http\\Attributes\\Tag;

#[Tag('Users', 'Authentication')]
class UserController extends Controller
{
    public string $id = '';
    public string $name = '';
    public string $email = '';
    public int $age = 0;
    
    #[ResponseType(User::class)]
    public function get(): JsonResponse
    {
        // GET /users/{id} - returns single user
        $user = User::find($this->id);
        return JsonResponse::ok($user->jsonSerialize());
    }
    
    #[ResponseType(User::class, status: 201, when: 'created')]
    #[ResponseType(['error' => 'string', 'errors' => 'array'], status: 422, when: 'validation_failed')]
    public function post(): JsonResponse
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'age' => 'required|integer|min:18',
        ]);
        
        // Implementation...
        $user = new User();
        // ... create user
        return JsonResponse::created($user->jsonSerialize());
    }
}`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                ResponseType Attribute Options
            </Typography>
            
            <Typography>
                The ResponseType attribute supports several formats:
            </Typography>
            
            <CodeBlock language="php" code={`// Class reference - single object
#[ResponseType(User::class)]

// Array of objects  
#[ResponseType('User[]')]

// Custom status code and condition
#[ResponseType(User::class, status: 201, when: 'created')]

// Inline object shape
#[ResponseType(['message' => 'string', 'count' => 'int'])]

// Multiple response types for different scenarios
#[ResponseType(User::class, status: 200, when: 'success')]
#[ResponseType(['error' => 'string'], status: 404, when: 'not_found')]`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Configuration Options
            </Typography>

            <Typography>
                BaseAPI automatically generates OpenAPI specs with minimal configuration required:
            </Typography>

            <CodeBlock language="bash" code={`# Environment variables used for OpenAPI generation
APP_URL="https://api.example.com"  # Used for server URL in spec

# CLI options for types:generate command
./mason types:generate --help

# Available options:
#   --out-ts=PATH          Output path for TypeScript type definitions
#   --out-openapi=PATH     Output path for OpenAPI specification
#   --out-routes=PATH      Output path for route constants and path builder
#   --out-http=PATH        Output path for HTTP client
#   --out-client=PATH      Output path for API client functions
#   --out-hooks=PATH       Output path for React hooks
#   --all                  Generate all outputs with default names`} />

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
        run: ./mason types:generate
        
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
