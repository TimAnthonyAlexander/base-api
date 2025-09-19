
import { Box, Typography, Alert } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';

export default function Controllers() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Controllers
      </Typography>
      
      <Typography variant="h5" color="text.secondary" paragraph>
        Building controllers to handle HTTP requests in BaseAPI.
      </Typography>

      <Typography paragraph>
        Controllers in BaseAPI are classes that handle HTTP requests and return responses. 
        They automatically receive dependency injection, request parameter binding, and 
        provide convenient methods for validation and response generation.
      </Typography>

      <Alert severity="info" sx={{ my: 3 }}>
        Controllers use method-based routing: <code>get()</code>, <code>post()</code>, <code>put()</code>, 
        <code>patch()</code>, <code>delete()</code>, <code>head()</code> methods correspond to HTTP methods.
      </Alert>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Basic Controller Structure
      </Typography>

      <Typography paragraph>
        All controllers extend the base <code>Controller</code> class and define methods 
        corresponding to HTTP verbs they handle.
      </Typography>

      <CodeBlock language="php" code={`<?php

namespace App\\Controllers;

use BaseApi\\Controllers\\Controller;
use BaseApi\\Http\\JsonResponse;
use App\\Models\\User;

class UserController extends Controller
{
    // Route parameters are automatically injected as properties
    public string $id = '';
    
    // Form fields are automatically injected as properties
    public string $name = '';
    public string $email = '';
    public string $password = '';
    
    // GET /users or GET /users/{id}
    public function get(): JsonResponse
    {
        if (empty($this->id)) {
            // List all users with pagination
            $result = User::apiQuery($this->request, 50);
            return JsonResponse::paginated($result);
        }
        
        // Get specific user
        $user = User::find($this->id);
        if (!$user) {
            return JsonResponse::notFound('User not found');
        }
        
        return JsonResponse::ok($user->jsonSerialize());
    }
    
    // POST /users
    public function post(): JsonResponse
    {
        // Validate request
        $this->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);
        
        // Check if user already exists
        $existingUser = User::firstWhere('email', '=', $this->email);
        if ($existingUser) {
            return JsonResponse::error('User with this email already exists', 409);
        }
        
        $user = new User();
        $user->name = $this->name;
        $user->email = $this->email;
        $user->password = password_hash($this->password, PASSWORD_DEFAULT);
        $user->save();
        
        return JsonResponse::created($user->jsonSerialize());
    }
}
`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        JsonResponse Methods
      </Typography>

      <Typography paragraph>
        BaseAPI provides a comprehensive set of JsonResponse helper methods for common API responses:
      </Typography>

      <CodeBlock language="php" code={`// Success responses
JsonResponse::ok($data)           // 200 with data wrapper
JsonResponse::created($data)      // 201 for created resources  
JsonResponse::success($data)      // 200 with success flag and meta
JsonResponse::accepted($data)     // 202 for async operations
JsonResponse::paginated($result)  // 200 with pagination info

// Error responses  
JsonResponse::badRequest($message, $errors)     // 400
JsonResponse::unauthorized($message)           // 401
JsonResponse::forbidden($message)              // 403  
JsonResponse::notFound($message)               // 404
JsonResponse::error($message, $status)         // Custom error
JsonResponse::validationError($errors)         // 422
JsonResponse::unprocessable($message, $details) // 422

// Other
JsonResponse::noContent()         // 204 empty response`} />

      <Alert severity="success" sx={{ mt: 4 }}>
        <strong>Best Practices:</strong>
        <br />• Keep controllers focused on HTTP concerns
        <br />• Move business logic to service classes
        <br />• Use validation for all input
        <br />• Return appropriate HTTP status codes
        <br />• Handle errors gracefully
        <br />• Use dependency injection for services
        <br />• Use apiQuery() for paginated list endpoints
        <br />• Use paginated() response for API lists
      </Alert>
    </Box>
  );
}
