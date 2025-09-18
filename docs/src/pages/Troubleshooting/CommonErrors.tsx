import { Box, Typography, Alert, List, ListItem, ListItemText } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';

export default function CommonErrors() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Common Errors & Solutions
      </Typography>
      <Typography variant="h5" color="text.secondary" paragraph>
        Solutions to frequently encountered BaseAPI issues
      </Typography>

      <Alert severity="info" sx={{ my: 3 }}>
        Most BaseAPI errors include detailed error messages and suggestions for resolution.
      </Alert>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Database Connection Errors
      </Typography>

      <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
        "Could not connect to database"
      </Typography>
      <List>
        <ListItem>
          <ListItemText primary="Check your .env database credentials" />
        </ListItem>
        <ListItem>
          <ListItemText primary="Verify the database server is running" />
        </ListItem>
        <ListItem>
          <ListItemText primary="Test connection with: php bin/console serve" />
        </ListItem>
      </List>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Migration Issues
      </Typography>

      <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
        "Migration failed to apply"
      </Typography>
      <CodeBlock language="bash" code={`# Check migration status
php bin/console migrate:status

# Force regenerate migrations
php bin/console migrate:generate --force

# Apply with verbose output
php bin/console migrate:apply --verbose`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Validation Errors
      </Typography>

      <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
        "Validation failed"
      </Typography>
      <CodeBlock language="php" code={`// Check your validation rules match the data types
$this->validate([
    'email' => 'required|email',  // Ensure input is valid email
    'age' => 'required|integer',  // Ensure input is integer
    'name' => 'required|string|max:255' // Check string length
]);`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Cache Issues
      </Typography>

      <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
        "Cache driver not configured"
      </Typography>
      <CodeBlock language="bash" code={`# Clear cache and restart
php bin/console cache:clear

# Check cache configuration in .env
CACHE_DRIVER=file
CACHE_PATH=storage/cache

# Test cache functionality
php bin/console cache:stats`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Quick Diagnostics
      </Typography>

      <CodeBlock language="bash" code={`# Health check
curl http://localhost:7879/health?db=1

# Check logs
tail -f storage/logs/app.log

# Clear all cache
php bin/console cache:clear

# Verify environment
php bin/console --version`} />

      <Alert severity="success" sx={{ mt: 4 }}>
        <strong>Debugging Tips:</strong>
        <br />• Check the logs in storage/logs/
        <br />• Use --verbose flag with CLI commands
        <br />• Verify .env configuration
        <br />• Test with simple examples first
        <br />• Clear cache when encountering odd behaviors
      </Alert>
    </Box>
  );
}