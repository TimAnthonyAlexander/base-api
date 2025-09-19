
import { Box, Typography, Alert } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';

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
        They support eager loading, query caching, and API-ready pagination out of the box.
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

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Relationships
      </Typography>

      <Typography paragraph>
        Models support typed relationships using <code>belongsTo</code> and <code>hasMany</code> methods:
      </Typography>

      <CodeBlock language="php" code={`class Room extends BaseModel
{
    public string $title = '';
    public string $type = '';
    public int $capacity = 1;
    
    // Define typed relationship property
    public Hotel $hotel;
    public string $hotel_id = '';
    
    /** @var Offer[] */
    public array $offers = [];
    
    // Define relationship methods
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
    
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}

// Usage
$room = Room::find($id);
$hotel = $room->hotel()->get();    // Load related hotel
$offers = $room->offers()->get();  // Load related offers`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Eager Loading
      </Typography>

      <Typography paragraph>
        Use the <code>with()</code> method to eager load relationships and avoid N+1 query problems:
      </Typography>

      <CodeBlock language="php" code={`// Load rooms with their hotels
$rooms = Room::with(['hotel'])->get();

// Load hotels with their rooms
$hotels = Hotel::with(['rooms'])->get();

// Load offers with their rooms
$offers = Offer::with(['room'])->get();

// All relationships are automatically loaded
foreach ($hotels as $hotel) {
    foreach ($hotel->rooms as $room) {
        // Room data is already loaded, no additional queries needed
    }
}`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        API Queries & Pagination
      </Typography>

      <Typography paragraph>
        The <code>apiQuery()</code> method provides automatic pagination, sorting, filtering, and eager loading for API endpoints:
      </Typography>

      <CodeBlock language="php" code={`// In a controller
public function get(): JsonResponse
{
    // Auto-handles ?page=1&perPage=20&sort=name&with=hotel
    $result = Room::apiQuery($this->request, 50); // max 50 per page
    
    return JsonResponse::paginated($result);
}

// Manual pagination for custom queries
$result = Room::where('capacity', '>=', 2)
    ->with(['hotel'])
    ->paginate($page, $perPage, $maxPerPage, true);
    
return JsonResponse::paginated($result);`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Query Caching
      </Typography>

      <Typography paragraph>
        Use the <code>cached()</code> method to cache expensive queries with automatic cache invalidation:
      </Typography>

      <CodeBlock language="php" code={`// Cache for 5 minutes (300 seconds)
$hotels = Hotel::cached(300)
    ->where('rating', '>=', 4.0)
    ->with(['rooms'])
    ->get();

// Cache is automatically invalidated when models are saved/deleted
$hotel = new Hotel();
$hotel->title = 'New Hotel';
$hotel->save(); // Automatically clears related cache`} />

      <Alert severity="success" sx={{ mt: 4 }}>
        <strong>Best Practices:</strong>
        <br />• Use typed properties for automatic validation
        <br />• Define relationships for data integrity
        <br />• Add indexes for frequently queried columns
        <br />• Use eager loading to avoid N+1 queries
        <br />• Leverage caching for expensive queries
        <br />• Keep business logic in models, HTTP logic in controllers
        <br />• Use apiQuery() for API endpoints with pagination
        <br />• Cache expensive queries with cached() method
      </Alert>
    </Box>
  );
}
