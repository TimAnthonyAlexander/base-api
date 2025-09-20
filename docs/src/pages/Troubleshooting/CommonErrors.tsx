import { 
  Box, 
  Typography, 
  Alert, 
  List, 
  ListItem, 
  ListItemText, 
  Accordion,
  AccordionSummary,
  AccordionDetails,
  TextField,
  InputAdornment,
  Chip
} from '@mui/material';
import { 
  ExpandMore as ExpandIcon,
  Search as SearchIcon,
  Error as ErrorIcon,
  Warning as WarningIcon,
  Info as InfoIcon
} from '@mui/icons-material';
import { useState, useMemo } from 'react';
import CodeBlock from '../../components/CodeBlock';

interface ErrorItem {
  id: string;
  category: string;
  type: 'error' | 'warning' | 'info';
  title: string;
  message: string;
  cause: string;
  solution: string;
  code?: string;
  relatedErrors?: string[];
}

const ERROR_CATALOG: ErrorItem[] = [
  // Installation & Setup Errors
  {
    id: 'php-version',
    category: 'Installation',
    type: 'error',
    title: 'PHP Version Requirement Not Met',
    message: 'BaseAPI requires PHP 8.4.0 or higher, found 8.3.x',
    cause: 'Your system is running an older version of PHP that doesn\'t support the features BaseAPI requires.',
    solution: 'Update PHP to version 8.4 or higher. BaseAPI uses PHP 8.4+ features for performance and type safety.',
    code: `# Check your PHP version
php --version

# Ubuntu/Debian - install PHP 8.4
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install php8.4-fpm php8.4-cli php8.4-common php8.4-mbstring php8.4-xml php8.4-curl

# macOS with Homebrew
brew install php@8.4

# CentOS/RHEL with Remi repository
sudo yum install epel-release yum-utils
sudo yum install http://rpms.remirepo.net/enterprise/remi-release-8.rpm
sudo yum-config-manager --enable remi-php84
sudo yum install php php-fpm`
  },
  {
    id: 'missing-extensions',
    category: 'Installation',
    type: 'error',
    title: 'Required PHP Extensions Missing',
    message: 'Extension \'mbstring\' is required but not installed',
    cause: 'BaseAPI requires specific PHP extensions that aren\'t installed on your system.',
    solution: 'Install the required PHP extensions: mbstring, json, pdo, curl, xml, and zip.',
    code: `# Install required PHP extensions

# Ubuntu/Debian
sudo apt install php8.4-mbstring php8.4-json php8.4-pdo php8.4-curl php8.4-xml php8.4-zip php8.4-sqlite3 php8.4-mysql

# CentOS/RHEL
sudo yum install php-mbstring php-json php-pdo php-curl php-xml php-zip php-sqlite3 php-mysqlnd

# macOS (extensions usually included with Homebrew PHP)
# If missing, reinstall PHP: brew reinstall php@8.4

# Verify extensions are loaded
php -m | grep -E "(mbstring|json|pdo|curl|xml|zip)"`
  },
  {
    id: 'composer-not-found',
    category: 'Installation',
    type: 'error',
    title: 'Composer Command Not Found',
    message: 'composer: command not found',
    cause: 'Composer is not installed or not in your system PATH.',
    solution: 'Install Composer following the official installation instructions.',
    code: `# Install Composer globally
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Verify installation
composer --version

# Alternative: Download directly
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer`
  },

  // Database Errors
  {
    id: 'db-connection-failed',
    category: 'Database',
    type: 'error',
    title: 'Database Connection Failed',
    message: 'SQLSTATE[HY000] [2002] No such file or directory',
    cause: 'Database server is not running, incorrect connection parameters, or firewall blocking the connection.',
    solution: 'Verify database server is running and connection parameters in .env are correct.',
    code: `# Check database configuration in .env
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=your_database
DB_USER=your_username
DB_PASSWORD=your_password

# Test MySQL connection
mysql -h 127.0.0.1 -P 3306 -u your_username -p

# For SQLite, ensure directory is writable
touch database.sqlite
chmod 664 database.sqlite

# Check if database service is running
sudo systemctl status mysql    # MySQL
sudo systemctl status postgresql  # PostgreSQL`,
    relatedErrors: ['db-access-denied', 'db-not-found']
  },
  {
    id: 'db-access-denied',
    category: 'Database',
    type: 'error',
    title: 'Database Access Denied',
    message: 'SQLSTATE[28000] [1045] Access denied for user \'username\'@\'host\'',
    cause: 'Incorrect database credentials or user doesn\'t have required permissions.',
    solution: 'Verify username/password and ensure database user has proper permissions.',
    code: `# Create database user with proper permissions
CREATE USER 'baseapi_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON baseapi_db.* TO 'baseapi_user'@'localhost';
FLUSH PRIVILEGES;

# For remote connections
GRANT ALL PRIVILEGES ON baseapi_db.* TO 'baseapi_user'@'%' IDENTIFIED BY 'secure_password';

# Update .env with correct credentials
DB_USER=baseapi_user
DB_PASSWORD=secure_password`
  },
  {
    id: 'db-not-found',
    category: 'Database',
    type: 'error',
    title: 'Database Does Not Exist',
    message: 'SQLSTATE[42000] [1049] Unknown database \'database_name\'',
    cause: 'The specified database hasn\'t been created yet.',
    solution: 'Create the database before running your application.',
    code: `# Create database in MySQL
CREATE DATABASE baseapi_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create database in PostgreSQL
CREATE DATABASE baseapi_db WITH ENCODING 'UTF8';

# For SQLite, the file will be created automatically
# Just ensure the directory is writable
mkdir -p storage/database
touch storage/database/database.sqlite`
  },

  // Migration Errors
  {
    id: 'migration-file-missing',
    category: 'Migration',
    type: 'error',
    title: 'Migration File Not Found',
    message: 'No migrations found at storage/migrations.json',
    cause: 'Migration system hasn\'t been initialized or no models have been created yet.',
    solution: 'Create models and generate migrations, or ensure storage directory is writable.',
    code: `# Create storage directory structure
mkdir -p storage/
chmod -R 755 storage/
chown -R www-data:www-data storage/

# Create your first model
php bin/console make:model User

# Generate migrations from your models
php bin/console migrate:generate

# Apply migrations to database
php bin/console migrate:apply

# Check migration status
php bin/console migrate:status`
  },
  {
    id: 'migration-syntax-error',
    category: 'Migration',
    type: 'error',
    title: 'Migration SQL Syntax Error',
    message: 'SQLSTATE[42000]: Syntax error or access violation',
    cause: 'Generated migration contains invalid SQL or database-specific syntax issues.',
    solution: 'Review the generated migration and ensure your model definitions are correct.',
    code: `# Check model definitions for issues
// Ensure proper property types and defaults
class User extends BaseModel
{
    public string $name = '';  // Good: has default
    public ?string $email = null;  // Good: nullable with default
    public int $age;  // BAD: no default value
}

# Review generated migrations
cat storage/migrations.json

# Regenerate migrations after fixing models  
php bin/console migrate:generate

# Check individual migration SQL statements
cat storage/migrations.json | jq '.migrations[].sql'`
  },

  // Permission Errors
  {
    id: 'storage-not-writable',
    category: 'Permissions',
    type: 'error',
    title: 'Storage Directory Not Writable',
    message: 'Unable to write to storage/logs/app.log',
    cause: 'Web server doesn\'t have write permissions to storage directories.',
    solution: 'Set proper permissions on storage directory and subdirectories.',
    code: `# Set proper permissions
sudo chown -R www-data:www-data storage/
sudo chmod -R 755 storage/
sudo chmod -R 775 storage/logs storage/cache  # If these directories exist

# For development (less secure)
chmod -R 777 storage/

# SELinux systems (CentOS/RHEL)
sudo setsebool -P httpd_can_network_connect 1
sudo setsebool -P httpd_can_network_connect_db 1
sudo setsebool -P httpd_execmem 1`
  },
  {
    id: 'env-not-readable',
    category: 'Permissions',
    type: 'warning',
    title: 'Environment File Not Readable',
    message: 'Unable to read .env file',
    cause: 'Environment file doesn\'t exist or has incorrect permissions.',
    solution: 'Ensure .env file exists and is readable by the web server.',
    code: `# Copy from example file
cp .env.example .env

# Set proper permissions
chmod 644 .env
chown www-data:www-data .env

# For production, use more restrictive permissions
chmod 600 .env`
  },

  // Runtime Errors
  {
    id: 'class-not-found',
    category: 'Runtime',
    type: 'error',
    title: 'Controller Class Not Found',
    message: 'Class \'App\\\\Controllers\\\\UserController\' not found',
    cause: 'Controller class doesn\'t exist or has incorrect namespace/filename.',
    solution: 'Ensure controller file exists with proper namespace and class name.',
    code: `# Generate controller with correct structure
php bin/console make:controller UserController

# Verify namespace matches directory structure
// app/Controllers/UserController.php
namespace App\\Controllers;

// For subdirectories
// app/Controllers/Admin/UserController.php  
namespace App\\Controllers\\Admin;

# Run composer autoload dump
composer dump-autoload`
  },
  {
    id: 'method-not-allowed',
    category: 'Runtime',
    type: 'error',
    title: 'HTTP Method Not Allowed',
    message: 'HTTP/1.1 405 Method Not Allowed',
    cause: 'Route is defined for different HTTP method or controller method doesn\'t exist.',
    solution: 'Check route definition matches controller methods and HTTP verbs.',
    code: `# Ensure route matches controller method
$router->get('/users', [UserController::class]);     // needs get() method
$router->post('/users', [UserController::class]);    // needs post() method

# Controller must have matching methods
class UserController extends Controller
{
    public function get(): JsonResponse { /* ... */ }
    public function post(): JsonResponse { /* ... */ }
}

# List routes to verify
php bin/console routes:list`
  },
  {
    id: 'validation-failed',
    category: 'Runtime',
    type: 'warning',
    title: 'Validation Rules Failed',
    message: 'The given data was invalid',
    cause: 'Request data doesn\'t match validation rules defined in controller.',
    solution: 'Check validation rules and ensure client sends properly formatted data.',
    code: `# Common validation issues and fixes
public function post(): JsonResponse
{
    $this->validate([
        'email' => 'required|email',  // Must be valid email
        'name' => 'required|string|max:100',  // Required string, max length
        'age' => 'integer|min:18',  // Integer, minimum value
        'active' => 'boolean',  // Must be true/false, 1/0, "true"/"false"
    ]);
}

# Client should send proper JSON
{
    "email": "user@example.com",
    "name": "John Doe", 
    "age": 25,
    "active": true
}`
  },

  // Cache Errors
  {
    id: 'redis-connection-failed',
    category: 'Cache',
    type: 'error',
    title: 'Redis Connection Failed',
    message: 'Redis connection refused on localhost:6379',
    cause: 'Redis server is not running or connection parameters are incorrect.',
    solution: 'Start Redis server and verify connection configuration.',
    code: `# Start Redis service
sudo systemctl start redis-server
sudo systemctl enable redis-server

# Test Redis connection
redis-cli ping  # Should return "PONG"

# Install Redis if not installed
# Ubuntu/Debian
sudo apt install redis-server

# CentOS/RHEL
sudo yum install redis
# or
sudo dnf install redis

# Check Redis configuration in .env
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=  # Leave empty if no password`
  },
  {
    id: 'cache-dir-not-writable',
    category: 'Cache',
    type: 'error',
    title: 'Cache Directory Not Writable',
    message: 'Unable to create cache file in storage/cache/',
    cause: 'File cache driver cannot write to cache directory.',
    solution: 'Ensure cache directory exists and is writable.',
    code: `# Create and set permissions for cache directory
mkdir -p storage/cache
chmod -R 755 storage/cache
chown -R www-data:www-data storage/cache

# Clear any existing cache files
php bin/console cache:clear

# Verify cache configuration
CACHE_DRIVER=file
CACHE_PATH=storage/cache  # Optional, defaults to storage/cache`
  }
];

export default function CommonErrors() {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState<string | null>(null);

  const categories = useMemo(() => {
    const cats = [...new Set(ERROR_CATALOG.map(error => error.category))];
    return cats.sort();
  }, []);

  const filteredErrors = useMemo(() => {
    return ERROR_CATALOG.filter(error => {
      const matchesSearch = !searchTerm || 
        error.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
        error.message.toLowerCase().includes(searchTerm.toLowerCase()) ||
        error.cause.toLowerCase().includes(searchTerm.toLowerCase()) ||
        error.solution.toLowerCase().includes(searchTerm.toLowerCase());
      
      const matchesCategory = !selectedCategory || error.category === selectedCategory;
      
      return matchesSearch && matchesCategory;
    });
  }, [searchTerm, selectedCategory]);

  const getErrorIcon = (type: string) => {
    switch (type) {
      case 'error': return <ErrorIcon sx={{ color: 'error.main' }} />;
      case 'warning': return <WarningIcon sx={{ color: 'warning.main' }} />;
      case 'info': return <InfoIcon sx={{ color: 'info.main' }} />;
      default: return <ErrorIcon />;
    }
  };

  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Error Catalog
      </Typography>
      <Typography variant="h5" color="text.secondary" paragraph>
        Comprehensive reference for BaseAPI errors with causes and solutions
      </Typography>

      <Typography paragraph>
        This catalog contains specific error messages you might encounter with BaseAPI, 
        their root causes, and step-by-step solutions. Use the search to find your specific error.
      </Typography>

      {/* Search and Filter */}
      <Box sx={{ mb: 4 }}>
        <TextField
          fullWidth
          placeholder="Search errors by message, cause, or solution..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          InputProps={{
            startAdornment: (
              <InputAdornment position="start">
                <SearchIcon />
              </InputAdornment>
            ),
          }}
          sx={{ mb: 2 }}
        />
        
        <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap' }}>
          <Chip 
            label="All Categories"
            onClick={() => setSelectedCategory(null)}
            color={selectedCategory === null ? 'primary' : 'default'}
            variant={selectedCategory === null ? 'filled' : 'outlined'}
          />
          {categories.map(category => (
            <Chip
              key={category}
              label={category}
              onClick={() => setSelectedCategory(category)}
              color={selectedCategory === category ? 'primary' : 'default'}
              variant={selectedCategory === category ? 'filled' : 'outlined'}
            />
          ))}
        </Box>
      </Box>

      {/* Error List */}
      {filteredErrors.length === 0 ? (
        <Alert severity="info">
          No errors found matching your search criteria. Try adjusting your search terms or clearing the category filter.
        </Alert>
      ) : (
        <Box>
          <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
            Found {filteredErrors.length} error(s)
          </Typography>
          
          {filteredErrors.map((error) => (
            <Accordion key={error.id} sx={{ mb: 1 }}>
              <AccordionSummary expandIcon={<ExpandIcon />}>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 2, width: '100%' }}>
                  {getErrorIcon(error.type)}
                  <Box sx={{ flexGrow: 1 }}>
                    <Typography variant="h6" component="div">
                      {error.title}
                    </Typography>
                    <Typography variant="body2" color="text.secondary" sx={{ fontFamily: 'monospace' }}>
                      {error.message}
                    </Typography>
                  </Box>
                  <Chip 
                    label={error.category} 
                    size="small" 
                    color="primary" 
                    variant="outlined" 
                  />
                </Box>
              </AccordionSummary>
              
              <AccordionDetails>
                <Box sx={{ '& > *:not(:last-child)': { mb: 3 } }}>
                  {/* Cause */}
                  <Box>
                    <Typography variant="h6" gutterBottom color="error.main">
                      Root Cause
                    </Typography>
                    <Typography paragraph>
                      {error.cause}
                    </Typography>
                  </Box>

                  {/* Solution */}
                  <Box>
                    <Typography variant="h6" gutterBottom color="success.main">
                      Solution
                    </Typography>
                    <Typography paragraph>
                      {error.solution}
                    </Typography>
                  </Box>

                  {/* Code Example */}
                  {error.code && (
                    <Box>
                      <Typography variant="h6" gutterBottom>
                        Code Example
                      </Typography>
                      <CodeBlock
                        language="bash"
                        code={error.code}
                      />
                    </Box>
                  )}

                  {/* Related Errors */}
                  {error.relatedErrors && (
                    <Box>
                      <Typography variant="h6" gutterBottom>
                        Related Errors
                      </Typography>
                      <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap' }}>
                        {error.relatedErrors.map(relatedId => {
                          const relatedError = ERROR_CATALOG.find(e => e.id === relatedId);
                          return relatedError ? (
                            <Chip
                              key={relatedId}
                              label={relatedError.title}
                              size="small"
                              variant="outlined"
                              onClick={() => {
                                // Scroll to related error
                                document.getElementById(relatedId)?.scrollIntoView({ behavior: 'smooth' });
                              }}
                              sx={{ cursor: 'pointer' }}
                            />
                          ) : null;
                        })}
                      </Box>
                    </Box>
                  )}
                </Box>
              </AccordionDetails>
            </Accordion>
          ))}
        </Box>
      )}

      {/* Quick Help Section */}
      <Box sx={{ mt: 6 }}>
        <Typography variant="h2" gutterBottom>
          Quick Debugging Steps
        </Typography>

        <List>
          <ListItem>
            <ListItemText
              primary="1. Enable Debug Mode"
              secondary="Set APP_DEBUG=true in .env for detailed error messages and stack traces"
            />
          </ListItem>
          <ListItem>
            <ListItemText
              primary="2. Check Application Logs"
              secondary="Review logs in storage/logs/app.log for detailed error information"
            />
          </ListItem>
          <ListItem>
            <ListItemText
              primary="3. Verify File Permissions"
              secondary="Ensure storage/ directory is writable by web server (755 or 775 permissions)"
            />
          </ListItem>
          <ListItem>
            <ListItemText
              primary="4. Clear Cache"
              secondary="Run 'php bin/console cache:clear' to clear any cached configuration or data"
            />
          </ListItem>
          <ListItem>
            <ListItemText
              primary="5. Check System Requirements"
              secondary="Verify PHP version (8.4+), required extensions, and database connectivity"
            />
          </ListItem>
          <ListItem>
            <ListItemText
              primary="6. Review Configuration"
              secondary="Double-check .env file settings, especially database and cache configuration"
            />
          </ListItem>
        </List>

        <Alert severity="info" sx={{ mt: 3 }}>
          <Typography>
            <strong>Still having issues?</strong> Check the FAQ section for 
            additional help, or search for your specific error message in this catalog using Ctrl+F.
          </Typography>
        </Alert>
      </Box>
    </Box>
  );
}