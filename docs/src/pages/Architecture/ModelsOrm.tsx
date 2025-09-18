
import { Box, Typography, Alert, List, ListItem, ListItemText, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

export default function ModelsOrm() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Models & ORM
      </Typography>
      
      <Typography variant="h5" color="text.secondary" paragraph>
        Working with database models and the BaseAPI ORM.
      </Typography>

      <Typography paragraph>
        BaseAPI uses an Active Record pattern for models, where each model class represents 
        a database table and instances represent individual rows. Models handle data persistence, 
        relationships, validation, and provide a fluent query interface.
      </Typography>

      <Alert severity="info" sx={{ my: 3 }}>
        Models automatically generate migrations, handle relationships, and provide caching 
        for optimal performance. They use PHP 8.4+ typed properties for automatic data validation.
      </Alert>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Basic Model Definition
      </Typography>

      <Typography paragraph>
        Models extend <code>BaseModel</code> and use public typed properties to define their schema:
      </Typography>

      <CodeBlock language="php" code={`<?php

namespace App\\Models;

use BaseApi\\Models\\BaseModel;

class User extends BaseModel
{
    // Basic properties with types
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public bool $active = true;
    public ?string $avatar_url = null;
    
    // Timestamps are inherited: $id, $created_at, $updated_at
    
    // Optional: Define indexes (used by migrations)
    public static array $indexes = [
        'email' => 'unique',
        'active' => 'index',
    ];
    
    // Helper method
    public function checkPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }
}`} />

      <Alert severity="success" sx={{ mt: 4 }}>
        <strong>Best Practices:</strong>
        <br />• Use typed properties for automatic validation
        <br />• Define relationships for data integrity
        <br />• Add indexes for frequently queried columns
        <br />• Use eager loading to avoid N+1 queries
        <br />• Leverage caching for expensive queries
        <br />• Keep business logic in models, HTTP logic in controllers
      </Alert>
    </Box>
  );
}
