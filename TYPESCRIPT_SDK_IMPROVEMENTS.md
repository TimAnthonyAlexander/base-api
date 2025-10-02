# TypeScript SDK Critical Improvements

## Summary

All critical issues identified in the code review have been addressed. The type generation system now follows KISS principles while providing robust, production-ready SDK generation.

## ✅ Implemented Fixes

### 1. **Route Capture - Extended & Robust**
- **Issue**: Regex only supported `get|post|delete|put|patch`, brittle parsing
- **Fix**: 
  - Extended to all HTTP verbs: `OPTIONS`, `HEAD`
  - Improved regex to handle FQCNs and whitespace variations
  - Pattern: `/\$router->(get|post|delete|put|patch|options|head)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*\[(.*?)\]\s*,?\s*\);/s`

### 2. **Client Import Paths - Solved with --bundle-dir**
- **Issue**: Hard-coded sibling imports break when files in different locations
- **Fix**: Added `--bundle-dir` option
  ```bash
  ./mason types:generate --bundle-dir=web/src/api
  ```
  - Co-locates all files in one directory
  - Ensures imports work correctly
  - Prints absolute paths in summary table

### 3. **Envelope Pattern - Configurable**
- **Issue**: Envelope forced unconditionally on all 2xx responses
- **Fix**: Created `#[Enveloped]` attribute
  ```php
  #[Enveloped(enabled: true)]  // Default
  #[Enveloped(enabled: false)] // Disable wrapping
  ```
  - Method-level takes precedence over class-level
  - Default: enabled (backward compatible)

### 4. **HTTP Wrapper - Handles All Response Types**
- **Issue**: `fetchApi` always called `response.json()`, failed on 204/HEAD/non-JSON
- **Fix**: Comprehensive response handling
  ```typescript
  // Handle 204 No Content and HEAD
  if (response.status === 204 || method === 'HEAD') {
    return undefined as T;
  }
  
  // Parse by content-type
  const isJson = contentType?.includes('application/json');
  data = isJson ? await response.json() : await response.text();
  
  // Graceful error handling
  try {
    // Parse response
  } catch (err) {
    throw new ApiError('Failed to parse response', response.status);
  }
  ```

### 5. **Query/Body Parameters - Explicit Control**
- **Issue**: All non-GET params went to body, couldn't specify query on DELETE/POST
- **Fix**: Created attributes for explicit placement
  ```php
  #[Query] public string $filter;
  #[Body] public string $data;
  #[Rules('required|string')] public string $name;
  ```
  - Default convention: GET/HEAD/DELETE→query, POST/PUT/PATCH→body
  - Attributes override defaults
  - `#[Rules]` used for requiredness inference

### 6. **Type Mapping - Fixed Edge Cases**
- **Issue**: `array` → `string[]`, `null` → `nullable string`
- **Fix**:
  ```php
  'array' => SchemaIR::array(SchemaIR::unknown()), // array → unknown[]
  case 'null': return SchemaIR::unknown();           // null → unknown
  ```

### 7. **HEAD/OPTIONS - Full Support**
- **Issue**: Not implemented end-to-end
- **Fix**:
  - Router parsing includes them
  - HTTP client exports `head()` and `options()` functions
  - Operations generate correct functions and hooks

### 8. **Hook Dependencies - Auto-Tracked**
- **Issue**: Hooks didn't include path/query in deps, causing stale fetches
- **Fix**:
  ```typescript
  // Query hooks auto-stringify dependencies
  }, [enabled, JSON.stringify(path), JSON.stringify(query), ...(deps || [])]);
  
  // User can still add custom deps
  useGetUser({ id: '123' }, options, [customDep]);
  ```

### 9. **Error Handling - Robust**
- **Issue**: Non-JSON errors crashed, no content-type checking
- **Fix**:
  - Check content-type before parsing
  - Try/catch around JSON parse
  - Handle both object and string error responses
  - Provide fallback error messages

### 10. **Query on Mutations - Supported**
- **Issue**: DELETE/POST/PUT/PATCH couldn't accept query parameters
- **Fix**:
  - All methods support query params if present in IR
  - Client functions build query strings for any method
  - Mutation hooks include optional `query` in variables

## 🎯 New Attributes

### `#[Query]` - Mark property as query parameter
```php
#[Query(required: true)]
public string $filter;
```

### `#[Body]` - Mark property as body parameter
```php
#[Body]
public string $data;
```

### `#[Rules]` - Define validation rules for type inference
```php
#[Rules('required|email|max:255')]
public string $email;
```

### `#[Enveloped]` - Control response wrapping
```php
#[Enveloped(enabled: false)]
public function get(): JsonResponse { }
```

### `#[ApiModel]` - Export model even if unused
```php
#[ApiModel(name: 'User', description: 'User model')]
class User extends BaseModel { }
```

## 📦 CLI Improvements

### New --bundle-dir Option (Recommended)
```bash
# Co-locate all files - ensures imports work
./mason types:generate --bundle-dir=web/src/api
```

### Enhanced Help
```bash
./mason types:generate --help
```

### File Summary Table
After generation, prints absolute paths of all generated files:
```
Generated Files:
  Types:     /path/to/web/src/api/types.ts
  Routes:    /path/to/web/src/api/routes.ts
  HTTP:      /path/to/web/src/api/http.ts
  Client:    /path/to/web/src/api/client.ts
  Hooks:     /path/to/web/src/api/hooks.ts
  OpenAPI:   /path/to/web/src/api/openapi.json
```

## 🔧 Usage Examples

### Controller with Explicit Attributes
```php
#[Tag('Users')]
#[Enveloped(enabled: true)]
class UserController extends Controller
{
    // Path parameter
    public string $id;
    
    // Query parameter (explicit)
    #[Query]
    public ?string $filter = null;
    
    // Body with validation rules
    #[Body]
    #[Rules('required|email')]
    public string $email;
    
    #[ResponseType(User::class)]
    public function get(): JsonResponse
    {
        // GET /users/{id}?filter=active
    }
    
    #[ResponseType(User::class, status: 201)]
    public function post(): JsonResponse
    {
        // POST /users with body
    }
    
    #[Enveloped(enabled: false)]
    #[ResponseType(status: 204)]
    public function delete(): JsonResponse
    {
        // DELETE /users/{id} - returns 204 No Content
    }
}
```

### Generated TypeScript Usage
```typescript
// Query hook with proper dependencies
const { data, loading } = useGetUserById(
  { id: '123' },
  { onSuccess: (data) => console.log(data) },
  [customDep] // Optional additional dependencies
);

// Mutation with query parameters
const deleteUser = useDeleteUser();
await deleteUser.mutate({
  path: { id: '123' },
  query: { force: true } // DELETE supports query params
});

// Handle 204 No Content
const result = await Api.deleteUser({ id: '123' });
// result is undefined for 204 responses

// Error handling
try {
  await Api.createUser({ body: { email: 'invalid' } });
} catch (error) {
  if (error instanceof ApiError) {
    console.log(error.status);      // 422
    console.log(error.errors);      // { email: 'Invalid email' }
    console.log(error.requestId);   // For debugging
  }
}
```

## 🎨 What Wasn't Changed (Keeping It Simple)

Following KISS principles, these were intentionally not implemented to avoid complexity:

1. **Operation naming collision detection** - Rare edge case, can be handled manually
2. **Inline schema de-duplication** - OpenAPI viewers handle this fine
3. **Per-operation file splitting** - Keep simple, tree-shaking works at import level
4. **Dynamic router loading** - Regex parsing is simple and sufficient
5. **#[ApiModel] auto-discovery** - Opt-in is clearer than magic discovery

## 📊 Impact

### Before
- ❌ Only 5 HTTP verbs supported
- ❌ Hard-coded import paths broke easily
- ❌ All responses wrapped in envelope
- ❌ 204/HEAD responses failed
- ❌ No query params on DELETE
- ❌ Stale data in hooks
- ❌ Poor error handling

### After
- ✅ All 7 HTTP verbs (GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD)
- ✅ --bundle-dir ensures correct imports
- ✅ Envelope configurable per-operation
- ✅ 204/HEAD/non-JSON handled correctly
- ✅ Query params work on all methods
- ✅ Hooks auto-track dependencies
- ✅ Robust error handling with fallbacks
- ✅ Complete type safety end-to-end

## 🚀 Migration Guide

### For Existing Projects

1. **Add attributes to controllers** (optional, defaults work):
   ```php
   // If you need query params on DELETE
   #[Query] public ?string $reason = null;
   
   // If you don't want envelope wrapping
   #[Enveloped(enabled: false)]
   public function delete(): JsonResponse { }
   ```

2. **Regenerate with --bundle-dir**:
   ```bash
   ./mason types:generate --bundle-dir=web/src/api
   ```

3. **Update imports** (if using old structure):
   ```typescript
   // Old: separate directories
   import * as Types from '../types/api';
   
   // New: co-located
   import * as Types from './api/types';
   ```

4. **Handle 204 responses**:
   ```typescript
   // Old: expected data
   const data = await Api.deleteUser({ id });
   
   // New: undefined for 204
   await Api.deleteUser({ id });
   ```

## ✨ Result

A production-ready TypeScript SDK generation system that:
- Handles all HTTP methods and response types
- Provides explicit control via attributes
- Generates type-safe, robust client code
- Follows KISS principles
- Maintains backward compatibility (mostly)
- Has clear migration path for breaking changes

