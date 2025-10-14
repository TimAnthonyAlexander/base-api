import { Box, Typography, Alert, List, ListItem, ListItemText } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

export default function TypeScriptSdk() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                TypeScript SDK Generation
            </Typography>
            <Typography variant="h5" color="text.secondary" paragraph>
                Generate a complete, type-safe TypeScript SDK with React hooks from your BaseAPI controllers
            </Typography>

            <Typography>
                BaseAPI's type generation system creates a comprehensive TypeScript SDK that includes type definitions,
                route constants, HTTP client, API functions, and React hooks. Everything is fully typed end-to-end from
                your PHP controllers to your frontend React components.
            </Typography>

            <Alert severity="success" sx={{ my: 3 }}>
                <strong>Single Command, Complete SDK:</strong> Run <code>./mason types:generate --all</code> to generate
                types, routes, HTTP client, API functions, and React hooks from your PHP code.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Quick Start
            </Typography>

            <CodeBlock language="bash" code={`# Generate complete SDK (recommended)
./mason types:generate --all

# This creates:
# - types.ts         → TypeScript type definitions
# - routes.ts        → Route constants and path builder
# - http.ts          → HTTP client with error handling
# - client.ts        → API functions (e.g., getUserById)
# - hooks.ts         → React hooks (e.g., useGetUserById)
# - openapi.json     → OpenAPI 3.0 specification`} />

            <Callout type="info" title="OpenAPI Specification">
                The type generation also creates OpenAPI specs for Swagger UI, API testing, and general code generation.
                See <a href="/tools/openapi-types" style={{ color: 'inherit' }}>OpenAPI & Types</a> for more details.
            </Callout>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Architecture Overview
            </Typography>

            <Typography>
                The type generation system uses a <strong>single-pass pipeline</strong> that builds a language-neutral
                Intermediate Representation (IR) from your PHP routes and controllers, then emits multiple outputs:
            </Typography>

            <CodeBlock language="text" code={`PHP Routes/Controllers → IRBuilder → ApiIR → Emitters → Outputs
                                                           ↓
                                    ┌──────────────────────┼──────────────────────┐
                                    ↓                      ↓                      ↓
                              OpenAPI 3.0          TypeScript Types        React Hooks
                                                   + Route Constants
                                                   + HTTP Client
                                                   + API Functions`} />

            <Typography sx={{ mt: 2 }}>
                This architecture provides:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Single Source of Truth"
                        secondary="IR is built once from PHP, ensuring consistency across all outputs"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Multiple Outputs"
                        secondary="Generate only what you need: types, client, hooks, or everything"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Extensibility"
                        secondary="New emitters can be added without modifying analysis logic"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Type Safety"
                        secondary="Full TypeScript coverage from PHP to React components"
                    />
                </ListItem>
            </List>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Generated Files
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                types.ts - Type Definitions
            </Typography>

            <Typography>
                Contains all TypeScript interfaces for models and operation request/response types:
            </Typography>

            <CodeBlock language="typescript" code={`// Base types
export type UUID = string;
export type Envelope<T> = { data: T };

export interface ErrorResponse {
  error: string;
  requestId: string;
  errors?: Record<string, string>;
}

// Model interfaces
export interface User {
  id: string;
  name: string;
  email: string;
  created_at: string;
}

// Operation types
export interface GetUserByIdPathParams {
  id: string;
}

export type GetUserByIdResponse = Envelope<User>;

export interface CreateUserRequestBody {
  name: string;
  email: string;
  age?: number;
}

export type CreateUserResponse = Envelope<User>;`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                routes.ts - Route Constants
            </Typography>

            <Typography>
                Provides type-safe route constants and path building utilities:
            </Typography>

            <CodeBlock language="typescript" code={`// Route constants
export const Routes = {
  GetUserById: '/users/{id}',
  CreateUser: '/users',
  UpdateUser: '/users/{id}',
  DeleteUser: '/users/{id}',
} as const;

export type RouteKey = keyof typeof Routes;

// Generic path builder
export function buildPath<K extends RouteKey>(
  key: K,
  params?: Record<string, string | number>
): string;

// Type-safe path builders
export function buildGetUserByIdPath(params: { id: string | number }): string;
export function buildUpdateUserPath(params: { id: string | number }): string;`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                http.ts - HTTP Client
            </Typography>

            <Typography>
                Base HTTP client with error handling and automatic credential inclusion:
            </Typography>

            <CodeBlock language="typescript" code={`export interface HttpOptions {
  headers?: Record<string, string>;
  signal?: AbortSignal;
}

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public requestId?: string,
    public errors?: Record<string, string>
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

export const http = {
  get: <T>(path: string, options?: HttpOptions) => Promise<T>,
  post: <T>(path: string, body: unknown, options?: HttpOptions) => Promise<T>,
  put: <T>(path: string, body: unknown, options?: HttpOptions) => Promise<T>,
  patch: <T>(path: string, body: unknown, options?: HttpOptions) => Promise<T>,
  delete: <T>(path: string, options?: HttpOptions) => Promise<T>,
};`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                client.ts - API Functions
            </Typography>

            <Typography>
                Promise-based functions for each API operation with full type safety:
            </Typography>

            <CodeBlock language="typescript" code={`/**
 * GET /users/{id}
 * @tags Users
 */
export async function getUserById(
  path: Types.GetUserByIdPathParams,
  options?: HttpOptions
): Promise<Types.GetUserByIdResponse> {
  const url = buildPath('GetUserById', path);
  return http.get(url, options);
}

/**
 * POST /users
 * @tags Users
 */
export async function createUser(
  body: Types.CreateUserRequestBody,
  options?: HttpOptions
): Promise<Types.CreateUserResponse> {
  const url = '/users';
  return http.post(url, body, options);
}

/**
 * GET /users
 * @tags Users
 */
export async function getUsers(
  query?: Types.GetUsersQueryParams,
  options?: HttpOptions
): Promise<Types.GetUsersResponse> {
  const url = '/users';
  const searchParams = new URLSearchParams();
  if (query) {
    for (const [key, value] of Object.entries(query)) {
      if (value !== undefined) {
        searchParams.append(key, String(value));
      }
    }
  }
  const fullUrl = searchParams.toString() ? \`\${url}?\${searchParams}\` : url;
  return http.get(fullUrl, options);
}`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                hooks.ts - React Hooks
            </Typography>

            <Typography>
                React hooks for queries (auto-fetch) and mutations (manual trigger):
            </Typography>

            <CodeBlock language="typescript" code={`export interface QueryOptions<T> extends HttpOptions {
  enabled?: boolean;
  onSuccess?: (data: T) => void;
  onError?: (error: Error) => void;
}

export interface QueryResult<T> {
  data: T | null;
  loading: boolean;
  error: Error | null;
  refetch: () => Promise<void>;
}

export interface MutationResult<T, TVariables> {
  data: T | null;
  loading: boolean;
  error: Error | null;
  mutate: (variables: TVariables) => Promise<T>;
  reset: () => void;
}

// Query hook (GET) - auto-fetches on mount
export function useGetUserById(
  path: Types.GetUserByIdPathParams,
  options?: QueryOptions<Types.GetUserByIdResponse>,
  deps?: DependencyList
): QueryResult<Types.GetUserByIdResponse>;

// Mutation hook (POST/PUT/PATCH/DELETE) - manual trigger
export function useCreateUser(
  options?: QueryOptions<Types.CreateUserResponse>
): MutationResult<Types.CreateUserResponse, { body: Types.CreateUserRequestBody }>;`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Usage Examples
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Using API Functions
            </Typography>

            <CodeBlock language="typescript" code={`import * as Api from './api/client';
import * as Types from './api/types';

// GET request with path parameters
async function loadUser(id: string) {
  try {
    const response = await Api.getUserById({ id });
    const user = response.data; // Typed as User
    console.log(user.name);
  } catch (error) {
    if (error instanceof Api.ApiError) {
      console.error(\`Error \${error.status}: \${error.message}\`);
      if (error.errors) {
        // Validation errors
        Object.entries(error.errors).forEach(([field, message]) => {
          console.error(\`\${field}: \${message}\`);
        });
      }
    }
  }
}

// POST request with body
async function createUser(name: string, email: string) {
  const response = await Api.createUser({
    name,
    email,
    age: 25
  });
  return response.data;
}

// GET request with query parameters
async function searchUsers(query: string) {
  const response = await Api.getUsers({
    search: query,
    page: 1,
    limit: 20
  });
  return response.data;
}`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Using React Query Hooks
            </Typography>

            <CodeBlock language="typescript" code={`import { useGetUserById, useUpdateUser, useDeleteUser } from './api/hooks';

function UserProfile({ userId }: { userId: string }) {
  // Auto-fetches on mount and when userId changes
  const { data, loading, error, refetch } = useGetUserById(
    { id: userId },
    {
      enabled: true, // Can disable auto-fetch
      onSuccess: (data) => {
        console.log('User loaded:', data.data.name);
      },
      onError: (error) => {
        console.error('Failed to load user:', error);
      }
    },
    [userId] // Re-fetch when userId changes
  );

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error.message}</div>;
  if (!data) return null;

  const user = data.data;

  return (
    <div>
      <h1>{user.name}</h1>
      <p>{user.email}</p>
      <button onClick={refetch}>Refresh</button>
    </div>
  );
}`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Using React Mutation Hooks
            </Typography>

            <CodeBlock language="typescript" code={`import { useCreateUser, useUpdateUser, useDeleteUser } from './api/hooks';

function UserForm() {
  const createUser = useCreateUser({
    onSuccess: (data) => {
      console.log('User created:', data.data.id);
      // Navigate or update UI
    },
    onError: (error) => {
      console.error('Failed to create user:', error);
    }
  });

  const updateUser = useUpdateUser({
    onSuccess: () => console.log('Updated!')
  });

  const deleteUser = useDeleteUser();

  const handleCreate = async () => {
    try {
      await createUser.mutate({
        body: {
          name: 'John Doe',
          email: 'john@example.com'
        }
      });
    } catch (error) {
      // Error already handled by onError callback
    }
  };

  const handleUpdate = async (userId: string) => {
    await updateUser.mutate({
      path: { id: userId },
      body: { name: 'Updated Name' }
    });
  };

  const handleDelete = async (userId: string) => {
    if (confirm('Delete user?')) {
      await deleteUser.mutate({ path: { id: userId } });
    }
  };

  return (
    <div>
      <button 
        onClick={handleCreate}
        disabled={createUser.loading}
      >
        {createUser.loading ? 'Creating...' : 'Create User'}
      </button>
      
      {createUser.error && (
        <div className="error">{createUser.error.message}</div>
      )}
      
      {createUser.data && (
        <div>Created user: {createUser.data.data.name}</div>
      )}
    </div>
  );
}`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Using Route Constants
            </Typography>

            <CodeBlock language="typescript" code={`import { Routes, buildPath } from './api/routes';
import { Link } from 'react-router-dom';

function UserList({ users }: { users: User[] }) {
  return (
    <ul>
      {users.map(user => (
        <li key={user.id}>
          <Link to={buildPath('GetUserById', { id: user.id })}>
            {user.name}
          </Link>
        </li>
      ))}
    </ul>
  );
}

// Access raw templates
console.log(Routes.GetUserById); // "/users/{id}"

// Build paths programmatically
const userDetailUrl = buildPath('GetUserById', { id: '123' });
// Result: "/users/123"`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                CLI Options
            </Typography>

            <CodeBlock language="bash" code={`# Generate all outputs (recommended for full SDK)
./mason types:generate --all

# Generate specific outputs only
./mason types:generate --out-ts=types.ts --out-routes=routes.ts

# Custom output paths
./mason types:generate \\
  --out-ts=web/src/api/types.ts \\
  --out-routes=web/src/api/routes.ts \\
  --out-http=web/src/api/http.ts \\
  --out-client=web/src/api/client.ts \\
  --out-hooks=web/src/api/hooks.ts \\
  --out-openapi=storage/openapi.json

# Available options:
#   --out-ts=PATH          Output path for TypeScript type definitions
#   --out-openapi=PATH     Output path for OpenAPI specification
#   --out-routes=PATH      Output path for route constants and path builder
#   --out-http=PATH        Output path for HTTP client
#   --out-client=PATH      Output path for API client functions
#   --out-hooks=PATH       Output path for React hooks
#   --all                  Generate all outputs with default names
#   --help, -h             Show help message`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                PHP Controller Setup
            </Typography>

            <Typography>
                For best results, follow these conventions in your PHP controllers:
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Use Typed Properties
            </Typography>

            <CodeBlock language="php" code={`<?php

class UserController extends Controller
{
    // Path parameters
    public string $id;
    
    // Query/body parameters
    public ?string $name = null;
    public ?string $email = null;
    public ?int $age = null;
    public ?bool $active = null;
    
    // Arrays
    public ?array $tags = null;
}`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Add ResponseType Attributes
            </Typography>

            <CodeBlock language="php" code={`<?php
use BaseApi\\Http\\Attributes\\ResponseType;

class UserController extends Controller
{
    #[ResponseType(status: 200, shape: User::class)]
    public function get(): JsonResponse
    {
        $user = User::find($this->id);
        return JsonResponse::ok($user);
    }
    
    #[ResponseType(status: 200, shape: 'User[]')]
    public function getList(): JsonResponse
    {
        $users = User::all();
        return JsonResponse::ok($users);
    }
    
    #[ResponseType(status: 201, shape: User::class)]
    public function post(): JsonResponse
    {
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
        ]);
        return JsonResponse::created($user);
    }
}`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Add Tags for Organization
            </Typography>

            <CodeBlock language="php" code={`<?php
use BaseApi\\Http\\Attributes\\Tag;

#[Tag('Users')]
class UserController extends Controller
{
    // All methods in this controller are tagged with 'Users'
}

// Or on individual methods
class MixedController extends Controller
{
    #[Tag('Users', 'Authentication')]
    public function login(): JsonResponse
    {
        // ...
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Integration with Build Tools
            </Typography>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Vite / React
            </Typography>

            <CodeBlock language="json" code={`{
  "scripts": {
    "generate:api": "cd ../api && ./mason types:generate --all && mv *.ts ../web/src/api/",
    "dev": "npm run generate:api && vite",
    "build": "npm run generate:api && vite build"
  }
}`} />

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                CI/CD Pipeline
            </Typography>

            <CodeBlock language="yaml" code={`# GitHub Actions
name: Build Frontend
on: [push]

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
      
      - name: Generate TypeScript SDK
        run: |
          cd api
          composer install
          ./mason types:generate --all
          mv *.ts ../web/src/api/
      
      - name: Build Frontend
        run: |
          cd web
          npm install
          npm run build`} />

            <Callout type="tip" title="Recommended Workflow">
                Add <code>npm run generate:api</code> to your pre-commit hooks or run it before starting development
                to ensure your frontend always has the latest API types.
            </Callout>

            <Alert severity="info" sx={{ mt: 4 }}>
                <strong>Version Control:</strong>
                <br />• Commit generated files to version control for consistency
                <br />• Or add to .gitignore and generate during build
                <br />• Use the same approach across your team
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Benefits
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Type Safety"
                        secondary="Catch API changes at compile time, not runtime"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Auto-completion"
                        secondary="Full IDE support for all API operations and types"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Reduced Boilerplate"
                        secondary="Generated hooks eliminate repetitive state management code"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Error Prevention"
                        secondary="TypeScript catches mismatched types, missing parameters, and invalid paths"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Self-Documenting"
                        secondary="Types serve as inline documentation for your API"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Single Source of Truth"
                        secondary="PHP code is the authoritative source for all generated artifacts"
                    />
                </ListItem>
            </List>

            <Alert severity="success" sx={{ mt: 4 }}>
                <strong>Best Practices:</strong>
                <br />• Run <code>types:generate</code> after changing controllers or models
                <br />• Use <code>--all</code> flag for complete SDK generation
                <br />• Add type generation to your build pipeline
                <br />• Use ResponseType attributes for accurate response types
                <br />• Commit generated files or regenerate in CI/CD
                <br />• Use mutation hooks for POST/PUT/PATCH/DELETE operations
                <br />• Use query hooks for GET operations that need reactivity
            </Alert>
        </Box>
    );
}

