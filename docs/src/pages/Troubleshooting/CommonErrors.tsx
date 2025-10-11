import {
    Box,
    Typography,
    Alert,
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
    Info as InfoIcon,
    Check as CheckIcon
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
./mason make:model User

# Generate migrations from your models
./mason migrate:generate

# Apply migrations to database
./mason migrate:apply`
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
./mason migrate:generate

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
./mason make:controller UserController

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
./mason route:list`
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
./mason cache:clear

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
            {/* Header Section */}
            <Box sx={{ mb: 6 }}>
                <Typography variant="h1" gutterBottom sx={{
                    background: 'linear-gradient(135deg, #2196f3 0%, #1976d2 100%)',
                    backgroundClip: 'text',
                    WebkitBackgroundClip: 'text',
                    WebkitTextFillColor: 'transparent',
                    mb: 2
                }}>
                    Error Catalog
                </Typography>
                <Typography variant="h5" color="text.secondary" paragraph sx={{ mb: 3, maxWidth: '800px' }}>
                    Comprehensive reference for BaseAPI errors with causes and solutions
                </Typography>
                <Typography color="text.secondary" sx={{ maxWidth: '700px', lineHeight: 1.7 }}>
                    Find specific error messages you might encounter, understand their root causes,
                    and get step-by-step solutions. Use the search to quickly locate your issue.
                </Typography>
            </Box>

            {/* Search and Filter */}
            <Box sx={{
                mb: 6,
                p: 3,
                borderRadius: 3,
                border: theme => `1px solid ${theme.palette.divider}`,
                background: theme => theme.palette.mode === 'dark'
                    ? 'linear-gradient(135deg, rgba(255, 255, 255, 0.02) 0%, rgba(255, 255, 255, 0.04) 100%)'
                    : 'linear-gradient(135deg, rgba(0, 0, 0, 0.01) 0%, rgba(0, 0, 0, 0.02) 100%)'
            }}>
                <TextField
                    fullWidth
                    placeholder="Search errors by message, cause, or solution..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    InputProps={{
                        startAdornment: (
                            <InputAdornment position="start">
                                <SearchIcon sx={{ color: 'primary.main' }} />
                            </InputAdornment>
                        ),
                    }}
                    sx={{ mb: 3 }}
                />

                <Box sx={{ display: 'flex', gap: 1.5, flexWrap: 'wrap', alignItems: 'center' }}>
                    <Typography variant="body2" color="text.secondary" sx={{ mr: 1, fontWeight: 500 }}>
                        Categories:
                    </Typography>
                    <Chip
                        label="All"
                        onClick={() => setSelectedCategory(null)}
                        color={selectedCategory === null ? 'primary' : 'default'}
                        variant={selectedCategory === null ? 'filled' : 'outlined'}
                        sx={{ fontWeight: 500 }}
                    />
                    {categories.map(category => (
                        <Chip
                            key={category}
                            label={category}
                            onClick={() => setSelectedCategory(category)}
                            color={selectedCategory === category ? 'primary' : 'default'}
                            variant={selectedCategory === category ? 'filled' : 'outlined'}
                            sx={{ fontWeight: 500 }}
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
                        <Accordion key={error.id}>
                            <AccordionSummary
                                expandIcon={<ExpandIcon />}
                                sx={{
                                    '&:hover': {
                                        '& .error-title': {
                                            color: 'primary.main'
                                        }
                                    }
                                }}
                            >
                                <Box sx={{ display: 'flex', alignItems: 'flex-start', gap: 3, width: '100%' }}>
                                    <Box sx={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        minWidth: 40,
                                        height: 40,
                                        borderRadius: 2,
                                        background: error.type === 'error' ? 'rgba(244, 67, 54, 0.1)'
                                            : error.type === 'warning' ? 'rgba(255, 152, 0, 0.1)'
                                                : 'rgba(33, 150, 243, 0.1)',
                                        mt: 0.5
                                    }}>
                                        {getErrorIcon(error.type)}
                                    </Box>
                                    <Box sx={{ flexGrow: 1, minWidth: 0 }}>
                                        <Typography
                                            variant="h6"
                                            component="div"
                                            className="error-title"
                                            sx={{
                                                fontWeight: 600,
                                                mb: 1,
                                                transition: 'color 0.2s ease',
                                                lineHeight: 1.3
                                            }}
                                        >
                                            {error.title}
                                        </Typography>
                                        <Typography
                                            variant="body2"
                                            color="text.secondary"
                                            sx={{
                                                fontFamily: 'SFMono-Regular, Menlo, Monaco, Consolas, monospace',
                                                fontSize: '0.85rem',
                                                lineHeight: 1.4,
                                                background: theme => theme.palette.mode === 'dark'
                                                    ? 'rgba(255, 255, 255, 0.04)'
                                                    : 'rgba(0, 0, 0, 0.04)',
                                                px: 2,
                                                py: 1,
                                                borderRadius: 1,
                                                wordBreak: 'break-word'
                                            }}
                                        >
                                            {error.message}
                                        </Typography>
                                    </Box>
                                    <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: 1 }}>
                                        <Chip
                                            label={error.category}
                                            size="small"
                                            color="primary"
                                            variant="outlined"
                                            sx={{ fontWeight: 500 }}
                                        />
                                        <Chip
                                            label={error.type.toUpperCase()}
                                            size="small"
                                            color={error.type === 'error' ? 'error' : error.type === 'warning' ? 'warning' : 'info'}
                                            variant="filled"
                                            sx={{ fontSize: '0.7rem', height: 20 }}
                                        />
                                    </Box>
                                </Box>
                            </AccordionSummary>

                            <AccordionDetails>
                                <Box sx={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
                                    {/* Cause Section */}
                                    <Box sx={{
                                        p: 3,
                                        borderRadius: 2,
                                        border: '1px solid rgba(244, 67, 54, 0.2)',
                                        background: theme => theme.palette.mode === 'dark'
                                            ? 'rgba(244, 67, 54, 0.05)'
                                            : 'rgba(244, 67, 54, 0.02)'
                                    }}>
                                        <Typography variant="h6" gutterBottom sx={{
                                            color: 'error.main',
                                            fontWeight: 600,
                                            display: 'flex',
                                            alignItems: 'center',
                                            gap: 1,
                                            mb: 2
                                        }}>
                                            <ErrorIcon sx={{ fontSize: '1.2rem' }} />
                                            Root Cause
                                        </Typography>
                                        <Typography sx={{ lineHeight: 1.7, color: 'text.secondary' }}>
                                            {error.cause}
                                        </Typography>
                                    </Box>

                                    {/* Solution Section */}
                                    <Box sx={{
                                        p: 3,
                                        borderRadius: 2,
                                        border: '1px solid rgba(76, 175, 80, 0.2)',
                                        background: theme => theme.palette.mode === 'dark'
                                            ? 'rgba(76, 175, 80, 0.05)'
                                            : 'rgba(76, 175, 80, 0.02)'
                                    }}>
                                        <Typography variant="h6" gutterBottom sx={{
                                            color: 'success.main',
                                            fontWeight: 600,
                                            display: 'flex',
                                            alignItems: 'center',
                                            gap: 1,
                                            mb: 2
                                        }}>
                                            <CheckIcon sx={{ fontSize: '1.2rem' }} />
                                            Solution
                                        </Typography>
                                        <Typography sx={{ lineHeight: 1.7, color: 'text.secondary' }}>
                                            {error.solution}
                                        </Typography>
                                    </Box>

                                    {/* Code Example */}
                                    {error.code && (
                                        <Box>
                                            <Typography variant="h6" gutterBottom sx={{
                                                fontWeight: 600,
                                                mb: 2,
                                                display: 'flex',
                                                alignItems: 'center',
                                                gap: 1
                                            }}>
                                                <Box sx={{
                                                    width: 6,
                                                    height: 6,
                                                    borderRadius: '50%',
                                                    background: 'linear-gradient(135deg, #2196f3 0%, #1976d2 100%)'
                                                }} />
                                                Code Example
                                            </Typography>
                                            <Box sx={{
                                                border: theme => `1px solid ${theme.palette.divider}`,
                                                borderRadius: 2,
                                                overflow: 'hidden'
                                            }}>
                                                <CodeBlock
                                                    language="bash"
                                                    code={error.code}
                                                />
                                            </Box>
                                        </Box>
                                    )}

                                    {/* Related Errors */}
                                    {error.relatedErrors && (
                                        <Box>
                                            <Typography variant="h6" gutterBottom sx={{
                                                fontWeight: 600,
                                                mb: 2,
                                                display: 'flex',
                                                alignItems: 'center',
                                                gap: 1
                                            }}>
                                                <InfoIcon sx={{ fontSize: '1.2rem', color: 'info.main' }} />
                                                Related Errors
                                            </Typography>
                                            <Box sx={{ display: 'flex', gap: 1.5, flexWrap: 'wrap' }}>
                                                {error.relatedErrors.map(relatedId => {
                                                    const relatedError = ERROR_CATALOG.find(e => e.id === relatedId);
                                                    return relatedError ? (
                                                        <Chip
                                                            key={relatedId}
                                                            label={relatedError.title}
                                                            size="small"
                                                            variant="outlined"
                                                            onClick={() => {
                                                                document.getElementById(relatedId)?.scrollIntoView({ behavior: 'smooth' });
                                                            }}
                                                            sx={{
                                                                cursor: 'pointer',
                                                                fontWeight: 500,
                                                                '&:hover': {
                                                                    background: 'primary.main',
                                                                    color: 'white',
                                                                    borderColor: 'primary.main'
                                                                }
                                                            }}
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
            <Box sx={{
                mt: 8,
                p: 4,
                borderRadius: 3,
                background: theme => theme.palette.mode === 'dark'
                    ? 'linear-gradient(135deg, rgba(33, 150, 243, 0.05) 0%, rgba(156, 39, 176, 0.05) 100%)'
                    : 'linear-gradient(135deg, rgba(33, 150, 243, 0.02) 0%, rgba(156, 39, 176, 0.02) 100%)',
                border: theme => `1px solid ${theme.palette.divider}`
            }}>
                <Typography variant="h2" gutterBottom sx={{
                    mb: 3,
                    background: 'linear-gradient(135deg, #2196f3 0%, #9c27b0 100%)',
                    backgroundClip: 'text',
                    WebkitBackgroundClip: 'text',
                    WebkitTextFillColor: 'transparent'
                }}>
                    Quick Debugging Steps
                </Typography>

                <Box sx={{ display: 'grid', gap: 2, gridTemplateColumns: { xs: '1fr', md: 'repeat(2, 1fr)' } }}>
                    {[
                        { title: '1. Enable Debug Mode', desc: 'Set APP_DEBUG=true in .env for detailed error messages and stack traces' },
                        { title: '2. Check Application Logs', desc: 'Review logs in storage/logs/app.log for detailed error information' },
                        { title: '3. Verify File Permissions', desc: 'Ensure storage/ directory is writable by web server (755 or 775 permissions)' },
                        { title: '4. Clear Cache', desc: 'Run \'./mason cache:clear\' to clear any cached configuration or data' },
                        { title: '5. Check System Requirements', desc: 'Verify PHP version (8.4+), required extensions, and database connectivity' },
                        { title: '6. Review Configuration', desc: 'Double-check .env file settings, especially database and cache configuration' }
                    ].map((step, index) => (
                        <Box key={index} sx={{
                            p: 3,
                            borderRadius: 2,
                            background: theme => theme.palette.mode === 'dark'
                                ? 'rgba(255, 255, 255, 0.02)'
                                : 'rgba(255, 255, 255, 0.7)',
                            border: theme => `1px solid ${theme.palette.divider}`,
                            transition: 'all 0.3s ease',
                            '&:hover': {
                                transform: 'translateY(-2px)',
                                boxShadow: theme => theme.palette.mode === 'dark'
                                    ? '0 4px 20px rgba(0, 0, 0, 0.3)'
                                    : '0 4px 20px rgba(0, 0, 0, 0.1)'
                            }
                        }}>
                            <Typography variant="h6" sx={{ fontWeight: 600, mb: 1, color: 'primary.main' }}>
                                {step.title}
                            </Typography>
                            <Typography variant="body2" color="text.secondary" sx={{ lineHeight: 1.6 }}>
                                {step.desc}
                            </Typography>
                        </Box>
                    ))}
                </Box>

                <Alert
                    severity="info"
                    sx={{
                        mt: 4,
                        borderRadius: 2,
                        border: '1px solid rgba(33, 150, 243, 0.2)',
                        background: theme => theme.palette.mode === 'dark'
                            ? 'rgba(33, 150, 243, 0.05)'
                            : 'rgba(33, 150, 243, 0.02)'
                    }}
                >
                    <Typography sx={{ fontWeight: 500 }}>
                        <strong>Still having issues?</strong> Check the FAQ section for
                        additional help, or search for your specific error message in this catalog using Ctrl+F.
                    </Typography>
                </Alert>
            </Box>
        </Box>
    );
}
