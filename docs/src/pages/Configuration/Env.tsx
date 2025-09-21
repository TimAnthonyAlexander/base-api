
import {
    Box,
    Typography,
    Accordion,
    AccordionSummary,
    AccordionDetails,
} from '@mui/material';
import { ExpandMore as ExpandIcon } from '@mui/icons-material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';
import EnvTable from '../../components/EnvTable';

const appEnvVars = [
    {
        key: 'APP_NAME',
        default: 'BaseApi',
        description: 'The display name of your application',
    },
    {
        key: 'APP_ENV',
        default: 'local',
        description: 'Environment type',
        type: 'enum' as const,
        options: ['local', 'staging', 'production'],
    },
    {
        key: 'APP_DEBUG',
        default: 'true',
        description: 'Enable/disable debug mode (shows detailed error messages)',
        type: 'boolean' as const,
    },
    {
        key: 'APP_URL',
        default: 'http://127.0.0.1:7879',
        description: 'Base URL for your application',
    },
    {
        key: 'APP_HOST',
        default: '127.0.0.1',
        description: 'Server binding host',
    },
    {
        key: 'APP_PORT',
        default: '7879',
        description: 'Server binding port',
        type: 'number' as const,
    },
];

const corsEnvVars = [
    {
        key: 'CORS_ALLOWLIST',
        default: 'http://localhost:5173,http://127.0.0.1:5173',
        description: 'Comma-separated list of allowed origins for API access',
    },
];

const databaseEnvVars = [
    {
        key: 'DB_DRIVER',
        default: 'mysql',
        description: 'Database driver',
        type: 'enum' as const,
        options: ['mysql', 'sqlite', 'postgresql'],
    },
    {
        key: 'DB_NAME',
        default: 'baseapi',
        description: 'Database name (MySQL/PostgreSQL) or file path (SQLite)',
    },
    {
        key: 'DB_HOST',
        description: 'Database host (MySQL/PostgreSQL only)',
    },
    {
        key: 'DB_PORT',
        description: 'Database port (MySQL: 3306, PostgreSQL: 5432)',
        type: 'number' as const,
    },
    {
        key: 'DB_USER',
        description: 'Database username (MySQL/PostgreSQL only)',
    },
    {
        key: 'DB_PASSWORD',
        description: 'Database password (MySQL/PostgreSQL only)',
    },
];

const cacheEnvVars = [
    {
        key: 'CACHE_DRIVER',
        default: 'file',
        description: 'Default cache driver',
        type: 'enum' as const,
        options: ['array', 'file', 'redis'],
    },
    {
        key: 'CACHE_PREFIX',
        default: 'baseapi_cache',
        description: 'Cache key prefix (prevents collisions in shared environments)',
    },
    {
        key: 'CACHE_DEFAULT_TTL',
        default: '3600',
        description: 'Default cache TTL in seconds (3600 = 1 hour)',
        type: 'number' as const,
    },
    {
        key: 'CACHE_PATH',
        description: 'File cache path (defaults to storage/cache)',
    },
    {
        key: 'CACHE_RESPONSES',
        default: 'false',
        description: 'Enable HTTP response caching middleware',
        type: 'boolean' as const,
    },
    {
        key: 'CACHE_RESPONSE_TTL',
        default: '600',
        description: 'Default TTL for response cache in seconds',
        type: 'number' as const,
    },
];

const redisEnvVars = [
    {
        key: 'REDIS_HOST',
        default: '127.0.0.1',
        description: 'Redis server host',
    },
    {
        key: 'REDIS_PORT',
        default: '6379',
        description: 'Redis server port',
        type: 'number' as const,
    },
    {
        key: 'REDIS_PASSWORD',
        description: 'Redis server password (if required)',
    },
    {
        key: 'REDIS_CACHE_DB',
        default: '1',
        description: 'Redis database number for caching',
        type: 'number' as const,
    },
];

const envFileExample = `########################################
# Application Settings
########################################

# The display name of your application
APP_NAME=BaseApi

# Environment type: local, staging, production
APP_ENV=local

# Enable/disable debug mode (shows detailed error messages)
APP_DEBUG=true

# Base URL and server binding
APP_URL=http://127.0.0.1:7879
APP_HOST=127.0.0.1
APP_PORT=7879


########################################
# CORS (Cross-Origin Resource Sharing)
########################################

# Comma-separated list of allowed origins for API access
CORS_ALLOWLIST=http://localhost:5173,http://127.0.0.1:5173


########################################
# Database Configuration
########################################

# Database driver: mysql, sqlite, postgresql
DB_DRIVER=mysql

# Database name (MySQL/PostgreSQL) or file path (SQLite)
DB_NAME=baseapi


########################################
# Cache Configuration
########################################

# Default cache driver: array, file, redis
CACHE_DRIVER=file

# Cache key prefix (prevents collisions in shared environments)
CACHE_PREFIX=baseapi_cache

# Default cache TTL in seconds (3600 = 1 hour)
CACHE_DEFAULT_TTL=3600

# Enable HTTP response caching middleware  
CACHE_RESPONSES=false

# Default TTL for response cache in seconds
CACHE_RESPONSE_TTL=600`;

export default function Env() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Environment Configuration
            </Typography>

            <Typography variant="h5" color="text.secondary" paragraph>
                Configure your BaseAPI application using environment variables.
            </Typography>

            <Typography>
                BaseAPI uses environment variables for configuration. All settings are stored in the <code>.env</code> file in your project root. When you create a new project, the <code>.env</code> file is automatically created from <code>.env.example</code>.
            </Typography>

            <Callout type="tip">
                <Typography>
                    <strong>Environment-specific configuration:</strong> You can create separate <code>.env.local</code>, <code>.env.staging</code>, or <code>.env.production</code> files for different environments.
                </Typography>
            </Callout>

            {/* Example .env file */}
            <Box sx={{ mb: 4 }}>
                <Typography variant="h2" gutterBottom>
                    Example Configuration
                </Typography>

                <Typography>
                    Here's a complete example of a <code>.env</code> file with all available options:
                </Typography>

                <CodeBlock
                    language="bash"
                    code={envFileExample}
                    title=".env"
                />
            </Box>

            {/* Configuration Sections */}
            <Typography variant="h2" gutterBottom>
                Configuration Reference
            </Typography>

            <Accordion defaultExpanded>
                <AccordionSummary expandIcon={<ExpandIcon />}>
                    <Typography variant="h6" fontWeight={600}>
                        Application Settings
                    </Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <Typography color="text.secondary">
                        Basic application configuration including name, environment, and server settings.
                    </Typography>
                    <EnvTable envVars={appEnvVars} />
                </AccordionDetails>
            </Accordion>

            <Accordion>
                <AccordionSummary expandIcon={<ExpandIcon />}>
                    <Typography variant="h6" fontWeight={600}>
                        CORS Configuration
                    </Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <Typography color="text.secondary">
                        Cross-Origin Resource Sharing settings for API access from web applications.
                    </Typography>
                    <EnvTable envVars={corsEnvVars} />
                </AccordionDetails>
            </Accordion>

            <Accordion>
                <AccordionSummary expandIcon={<ExpandIcon />}>
                    <Typography variant="h6" fontWeight={600}>
                        Database Configuration
                    </Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <Typography color="text.secondary">
                        Database connection settings. BaseAPI supports SQLite, MySQL, and PostgreSQL.
                    </Typography>
                    <EnvTable envVars={databaseEnvVars} />

                    <Callout type="info">
                        <Typography>
                            <strong>MySQL/PostgreSQL:</strong> Requires <code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_NAME</code>, <code>DB_USER</code>, and <code>DB_PASSWORD</code>. For SQLite, only <code>DB_DRIVER=sqlite</code> and <code>DB_NAME</code> (file path) are needed.
                        </Typography>
                    </Callout>
                </AccordionDetails>
            </Accordion>

            <Accordion>
                <AccordionSummary expandIcon={<ExpandIcon />}>
                    <Typography variant="h6" fontWeight={600}>
                        Cache Configuration
                    </Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <Typography color="text.secondary">
                        Unified caching system settings. Supports multiple drivers and HTTP response caching.
                    </Typography>
                    <EnvTable envVars={cacheEnvVars} />

                    <Callout type="tip">
                        <Typography>
                            <strong>Performance Boost:</strong> Enable <code>CACHE_RESPONSES=true</code> for HTTP response caching to improve performance for API endpoints.
                        </Typography>
                    </Callout>
                </AccordionDetails>
            </Accordion>

            <Accordion>
                <AccordionSummary expandIcon={<ExpandIcon />}>
                    <Typography variant="h6" fontWeight={600}>
                        Redis Configuration
                    </Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <Typography color="text.secondary">
                        Redis connection settings (required only if using <code>CACHE_DRIVER=redis</code>).
                    </Typography>
                    <EnvTable envVars={redisEnvVars} />

                    <Callout type="info">
                        <Typography>
                            <strong>Production Recommended:</strong> Use Redis for caching in production environments, especially for multi-server deployments.
                        </Typography>
                    </Callout>
                </AccordionDetails>
            </Accordion>

            {/* Production Considerations */}
            <Box sx={{ mt: 4 }}>
                <Typography variant="h2" gutterBottom>
                    Production Considerations
                </Typography>

                <Callout type="warning">
                    <Typography>
                        <strong>Security:</strong> Always set <code>APP_DEBUG=false</code> in production to prevent sensitive information leaks in error messages.
                    </Typography>
                </Callout>

                <Box sx={{ mt: 2 }}>
                    <Typography variant="h6" gutterBottom>
                        Recommended Production Settings
                    </Typography>

                    <CodeBlock
                        language="bash"
                        code={`# Set to production environment
APP_ENV=production

# Disable debug mode
APP_DEBUG=false

# Use your production URL
APP_URL=https://api.yourapp.com

# Use Redis for better performance
CACHE_DRIVER=redis

# Enable response caching for performance
CACHE_RESPONSES=true

# Use a production database
DB_DRIVER=mysql
DB_HOST=your-db-host
DB_NAME=your-production-db`}
                        title="Production .env example"
                    />
                </Box>
            </Box>
        </Box>
    );
}
