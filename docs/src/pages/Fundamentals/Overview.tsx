import { Box, Typography, Alert, List, ListItem, ListItemText } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';

export default function Overview() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Architecture Overview
            </Typography>
            <Typography variant="h5" color="text.secondary" paragraph>
                Understanding BaseAPI's architecture and core principles
            </Typography>

            <Typography>
                BaseAPI is a modern, minimal PHP framework built on PHP 8.4+ that prioritizes
                developer productivity and application performance. <br />It provides everything you need to build
                JSON APIs quickly without unnecessary complexity.
            </Typography>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Application Lifecycle
            </Typography>

            <Typography>
                BaseAPI's request processing follows these key steps:
            </Typography>

            <List sx={{ mb: 4 }}>
                <ListItem>
                    <ListItemText
                        primary="1. Application Bootstrap"
                        secondary="App::boot() loads .env, config files, creates DI container, and registers service providers"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="2. Request Handling"
                        secondary="Kernel::handle() builds Request object from PHP globals, matches routes, and creates middleware pipeline"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="3. Pipeline Execution"
                        secondary="Middleware executes in order, ending with controller resolution and method invocation"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="4. Response Delivery"
                        secondary="Controller returns JsonResponse, kernel sets headers and outputs response body"
                    />
                </ListItem>
            </List>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Core Components
            </Typography>

            <Typography>
                BaseAPI's architecture consists of a few key components working together:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="App Class"
                        secondary="Central bootstrap point that loads config, creates DI container, and provides static access to core services"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="HTTP Kernel"
                        secondary="Handles request/response cycle, route matching, and middleware pipeline execution"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Router"
                        secondary="Fast route matching with parameter extraction and middleware support"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Controllers"
                        secondary="HTTP request handlers with automatic data binding, validation, and dependency injection"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Models"
                        secondary="Active Record ORM with relationships, caching, and automatic migrations"
                    />
                </ListItem>
            </List>

            <CodeBlock language="php" code={`<?php

// Simple controller example
class UserController extends Controller
{
    public string $name = '';     // Auto-populated from request
    public string $email = '';    // Validated automatically
    
    public function post(): JsonResponse
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email'
        ]);
        
        $user = new User();
        $user->name = $this->name;
        $user->email = $this->email;
        $user->save();
        
        return JsonResponse::created($user);
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Key Features
            </Typography>

            <Typography>
                BaseAPI provides essential features for modern API development:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Convention over Configuration"
                        secondary="Sensible defaults, automatic migrations, and predictable file organization minimize setup"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Built-in Security"
                        secondary="CORS, rate limiting, input validation, and SQL injection protection enabled by default"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="High Performance"
                        secondary="Fast routing, query caching, and minimal memory footprint for production workloads"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Developer Experience"
                        secondary="Auto-generated OpenAPI specs, TypeScript types, and comprehensive CLI tools"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Dependency Injection"
                        secondary="Automatic constructor injection with container-managed service lifecycles"
                    />
                </ListItem>
            </List>

            <Alert severity="success" sx={{ mt: 4 }}>
                <strong>Why BaseAPI?</strong>
                <br />• Get a working API in minutes, not hours
                <br />• Security and performance built-in from day one
                <br />• Simple, predictable patterns that scale
                <br />• Focus on your business logic, not framework complexity
            </Alert>
        </Box>
    );
}



