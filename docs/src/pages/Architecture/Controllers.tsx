
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
        <code>patch()</code>, <code>delete()</code> methods correspond to HTTP methods.
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
            // List all users
            $users = User::all(50); // Limit for performance
            return JsonResponse::ok($users);
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
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
        ]);
        
        $user = new User();
        $user->name = $this->name;
        $user->email = $this->email;
        $user->password = password_hash($this->password, PASSWORD_DEFAULT);
        $user->save();
        
        return JsonResponse::created($user->jsonSerialize());
    }
}
`} />

      <Alert severity="success" sx={{ mt: 4 }}>
        <strong>Best Practices:</strong>
        <br />• Keep controllers focused on HTTP concerns
        <br />• Move business logic to service classes
        <br />• Use validation for all input
        <br />• Return appropriate HTTP status codes
        <br />• Handle errors gracefully
        <br />• Use dependency injection for services
      </Alert>
    </Box>
  );
}
