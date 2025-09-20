import { Box, Typography, Alert, List, ListItem, ListItemText, Accordion, AccordionSummary, AccordionDetails } from '@mui/material';
import { ExpandMore as ExpandIcon } from '@mui/icons-material';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

export default function ProductionDeployment() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Production Deployment
            </Typography>

            <Typography variant="h5" color="text.secondary" paragraph>
                Complete guide for deploying BaseAPI applications to production environments.
            </Typography>

            <Typography>
                This guide covers everything you need to deploy BaseAPI applications to production, including
                web server configuration, PHP optimization, environment management, and performance tuning.
            </Typography>

            <Alert severity="info" sx={{ my: 3 }}>
                BaseAPI is designed for high-performance production deployment with minimal configuration.
                Follow this guide to ensure optimal performance and security.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Server Requirements
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="PHP 8.4 or higher"
                        secondary="With OPcache, FPM, and required extensions (mbstring, json, pdo)"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Web Server"
                        secondary="NGINX (recommended) or Apache with mod_rewrite"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Database"
                        secondary="MySQL 8.0+, PostgreSQL 13+, or SQLite 3.35+"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Cache (Recommended)"
                        secondary="Redis 6.0+ for optimal caching performance"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="SSL Certificate"
                        secondary="HTTPS is required for production APIs"
                    />
                </ListItem>
            </List>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                NGINX Configuration
            </Typography>

            <Typography>
                NGINX is the recommended web server for BaseAPI production deployments due to its excellent
                performance with PHP-FPM and ability to handle high concurrent loads.
            </Typography>

            <Accordion defaultExpanded>
                <AccordionSummary expandIcon={<ExpandIcon />}>
                    <Typography variant="h6" fontWeight={600}>
                        Complete NGINX Virtual Host
                    </Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <CodeBlock
                        language="nginx"
                        title="/etc/nginx/sites-available/baseapi"
                        code={`# BaseAPI Production NGINX Configuration
server {
    listen 80;
    listen [::]:80;
    server_name api.yourdomain.com;
    
    # Redirect all HTTP traffic to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name api.yourdomain.com;
    
    # SSL Configuration
    ssl_certificate /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # Document root
    root /var/www/baseapi/public;
    index index.php;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # Logging
    access_log /var/log/nginx/baseapi.access.log;
    error_log /var/log/nginx/baseapi.error.log;
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json;
    
    # Rate limiting (optional)
    limit_req_zone $binary_remote_addr zone=api:10m rate=100r/m;
    limit_req zone=api burst=20 nodelay;
    
    # Hide sensitive files
    location ~ /\.(env|git) {
        deny all;
        return 404;
    }
    
    location ~* ^/(storage|vendor|bin|config)/ {
        deny all;
        return 404;
    }
    
    # API routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP-FPM configuration
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_param HTTP_PROXY "";
        
        # Production PHP settings
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_connect_timeout 300;
        
        # Hide PHP version
        fastcgi_hide_header X-Powered-By;
    }
    
    # Optional: Serve static files directly
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
}

# Optional: Status endpoint for monitoring
server {
    listen 127.0.0.1:8080;
    location /nginx_status {
        stub_status on;
        access_log off;
        allow 127.0.0.1;
        deny all;
    }
}`}
                    />

                    <Typography variant="body2" color="text.secondary" sx={{ mt: 2 }}>
                        Enable the site and restart NGINX:
                    </Typography>

                    <CodeBlock
                        language="bash"
                        code={`# Enable the site
sudo ln -s /etc/nginx/sites-available/baseapi /etc/nginx/sites-enabled/
sudo nginx -t  # Test configuration
sudo systemctl reload nginx`}
                    />
                </AccordionDetails>
            </Accordion>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                PHP-FPM Configuration
            </Typography>

            <Typography>
                PHP-FPM (FastCGI Process Manager) is essential for production PHP performance.
                Proper configuration can dramatically improve your API's response times and throughput.
            </Typography>

            <Accordion>
                <AccordionSummary expandIcon={<ExpandIcon />}>
                    <Typography variant="h6" fontWeight={600}>
                        PHP-FPM Pool Configuration
                    </Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <CodeBlock
                        language="ini"
                        title="/etc/php/8.4/fpm/pool.d/baseapi.conf"
                        code={`; BaseAPI Production PHP-FPM Pool Configuration
[baseapi]

; Pool user and group
user = www-data
group = www-data

; Socket configuration
listen = /var/run/php/php8.4-fpm-baseapi.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

; Process management
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 15
pm.max_requests = 1000

; Process priority
process.priority = -10

; Security
security.limit_extensions = .php

; Environment variables
clear_env = no
env[HOSTNAME] = $HOSTNAME
env[PATH] = /usr/local/bin:/usr/bin:/bin
env[TMP] = /tmp
env[TMPDIR] = /tmp
env[TEMP] = /tmp

; PHP configuration overrides for production
php_admin_value[error_log] = /var/log/php/baseapi-fpm.log
php_admin_flag[log_errors] = on
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 30
php_admin_value[max_input_time] = 30
php_admin_value[post_max_size] = 50M
php_admin_value[upload_max_filesize] = 50M

; OPcache settings
php_admin_value[opcache.enable] = 1
php_admin_value[opcache.memory_consumption] = 256
php_admin_value[opcache.interned_strings_buffer] = 16
php_admin_value[opcache.max_accelerated_files] = 20000
php_admin_value[opcache.revalidate_freq] = 2
php_admin_value[opcache.fast_shutdown] = 1
php_admin_value[opcache.enable_cli] = 0
php_admin_value[opcache.save_comments] = 0

; Security settings
php_admin_value[expose_php] = Off
php_admin_value[display_errors] = Off
php_admin_value[display_startup_errors] = Off

; Session configuration
php_admin_value[session.save_handler] = redis
php_admin_value[session.save_path] = "tcp://127.0.0.1:6379"`}
                    />
                </AccordionDetails>
            </Accordion>

            <Accordion>
                <AccordionSummary expandIcon={<ExpandIcon />}>
                    <Typography variant="h6" fontWeight={600}>
                        System PHP Configuration
                    </Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <CodeBlock
                        language="ini"
                        title="/etc/php/8.4/fpm/conf.d/99-baseapi-production.ini"
                        code={`; BaseAPI Production PHP Configuration

; Error handling
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
log_errors = On
error_log = /var/log/php/error.log
display_errors = Off
display_startup_errors = Off

; Resource limits
memory_limit = 256M
max_execution_time = 30
max_input_time = 30
post_max_size = 50M
upload_max_filesize = 50M
max_file_uploads = 20

; Security
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off

; OPcache (critical for performance)
opcache.enable = 1
opcache.enable_cli = 0
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.revalidate_freq = 2
opcache.validate_timestamps = 0
opcache.fast_shutdown = 1
opcache.save_comments = 0
opcache.enable_file_override = 1

; Realpath cache (important for performance)
realpath_cache_size = 4096K
realpath_cache_ttl = 600

; Date settings
date.timezone = UTC

; Session configuration for API use
session.use_cookies = 0
session.use_only_cookies = 0
session.use_trans_sid = 0
session.cache_limiter = nocache

; Disable functions that aren't needed for APIs
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,parse_ini_file,show_source`}
                    />

                    <Typography variant="body2" color="text.secondary" sx={{ mt: 2 }}>
                        Restart PHP-FPM after configuration changes:
                    </Typography>

                    <CodeBlock
                        language="bash"
                        code={`sudo systemctl restart php8.4-fpm
sudo systemctl status php8.4-fpm  # Verify it's running`}
                    />
                </AccordionDetails>
            </Accordion>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Environment Management
            </Typography>

            <Typography>
                Production environments require secure handling of configuration and secrets.
                Never commit sensitive data to version control.
            </Typography>

            <Accordion>
                <AccordionSummary expandIcon={<ExpandIcon />}>
                    <Typography variant="h6" fontWeight={600}>
                        Production Environment Variables
                    </Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <CodeBlock
                        language="bash"
                        title="Production .env"
                        code={`########################################
# Production Environment Configuration
########################################

# Application
APP_NAME="Your API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

# Database (use strong credentials)
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=baseapi_production
DB_USER=baseapi_user
DB_PASSWORD=your-very-secure-password

# Cache (Redis recommended for production)
CACHE_DRIVER=redis
CACHE_PREFIX=baseapi_prod
CACHE_DEFAULT_TTL=3600
CACHE_QUERIES=true
CACHE_QUERY_TTL=900
CACHE_RESPONSES=true
CACHE_RESPONSE_TTL=1800

# Redis configuration
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=your-redis-password
REDIS_CACHE_DB=1

# CORS (restrict to your domains)
CORS_ALLOWLIST=https://yourdomain.com,https://app.yourdomain.com

# Security
SECRET_KEY=your-very-long-random-secret-key-here

# Logging
LOG_LEVEL=warning
LOG_CHANNEL=file

# Optional: External services
MAIL_DRIVER=smtp
MAIL_HOST=smtp.yourdomain.com
MAIL_USERNAME=api@yourdomain.com
MAIL_PASSWORD=your-mail-password

# Optional: Monitoring
SENTRY_DSN=your-sentry-dsn-here`}
                    />

                    <Callout type="warning" title="Security Best Practices">
                        <List sx={{ mt: 1 }}>
                            <ListItem disableGutters>
                                <ListItemText primary="• Use a secrets management system (AWS Secrets Manager, HashiCorp Vault)" />
                            </ListItem>
                            <ListItem disableGutters>
                                <ListItemText primary="• Set restrictive file permissions on .env (600 or 640)" />
                            </ListItem>
                            <ListItem disableGutters>
                                <ListItemText primary="• Use environment-specific .env files (.env.production)" />
                            </ListItem>
                            <ListItem disableGutters>
                                <ListItemText primary="• Never commit .env files to version control" />
                            </ListItem>
                            <ListItem disableGutters>
                                <ListItemText primary="• Use systemd environment files for service configuration" />
                            </ListItem>
                        </List>
                    </Callout>
                </AccordionDetails>
            </Accordion>

            <Accordion>
                <AccordionSummary expandIcon={<ExpandIcon />}>
                    <Typography variant="h6" fontWeight={600}>
                        Systemd Service Configuration
                    </Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <Typography>
                        For production services, consider using systemd environment files instead of .env:
                    </Typography>

                    <CodeBlock
                        language="ini"
                        title="/etc/systemd/system/baseapi.service"
                        code={`[Unit]
Description=BaseAPI Application
After=network.target mysql.service redis.service
Requires=mysql.service redis.service

[Service]
Type=oneshot
User=www-data
Group=www-data
WorkingDirectory=/var/www/baseapi
EnvironmentFile=/etc/baseapi/environment
ExecStart=/usr/bin/php bin/console cache:warm
ExecReload=/usr/bin/php bin/console cache:clear
RemainAfterExit=yes
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target`}
                    />

                    <CodeBlock
                        language="bash"
                        title="/etc/baseapi/environment"
                        code={`# BaseAPI Environment File (systemd)
APP_ENV=production
APP_DEBUG=false
DB_HOST=127.0.0.1
DB_NAME=baseapi_production
DB_USER=baseapi_user
DB_PASSWORD=secure-password
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=redis-password`}
                    />

                    <CodeBlock
                        language="bash"
                        code={`# Set secure permissions
sudo chmod 640 /etc/baseapi/environment
sudo chown root:www-data /etc/baseapi/environment

# Enable and start the service
sudo systemctl daemon-reload
sudo systemctl enable baseapi.service
sudo systemctl start baseapi.service`}
                    />
                </AccordionDetails>
            </Accordion>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Redis Configuration
            </Typography>

            <Typography>
                Redis provides high-performance caching and session storage for BaseAPI applications.
                Proper Redis configuration is crucial for production performance.
            </Typography>

            <CodeBlock
                language="bash"
                title="/etc/redis/redis.conf (key settings)"
                code={`# Network
bind 127.0.0.1
port 6379
timeout 300

# Authentication
requirepass your-very-secure-redis-password

# Memory management
maxmemory 1gb
maxmemory-policy allkeys-lru

# Persistence (adjust based on your needs)
save 900 1
save 300 10
save 60 10000

# Security
protected-mode yes
renamed-command FLUSHALL FLUSH_ALL_b83k92
renamed-command FLUSHDB FLUSH_DB_b83k92
renamed-command CONFIG CONFIG_b83k92

# Performance
tcp-keepalive 300
tcp-backlog 511`}
            />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Deployment Checklist
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="✅ Server Setup"
                        secondary="PHP 8.4+, NGINX/Apache, database server installed and configured"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="✅ SSL Certificate"
                        secondary="Valid SSL certificate installed and HTTPS redirect configured"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="✅ Environment Configuration"
                        secondary="Production .env file with secure credentials and APP_DEBUG=false"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="✅ Database Setup"
                        secondary="Production database created, migrations applied, user permissions set"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="✅ Cache Configuration"
                        secondary="Redis installed and configured, cache driver set to redis"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="✅ File Permissions"
                        secondary="storage/ directory writable, .env file secure (600/640 permissions)"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="✅ Web Server Config"
                        secondary="NGINX/Apache virtual host configured with security headers"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="✅ PHP Optimization"
                        secondary="OPcache enabled, production PHP-FPM settings applied"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="✅ Monitoring"
                        secondary="Log files accessible, monitoring/alerting configured"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="✅ Backups"
                        secondary="Database and application backups automated"
                    />
                </ListItem>
            </List>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Performance Tuning
            </Typography>

            <Typography>
                Additional optimizations for high-traffic production deployments:
            </Typography>

            <Accordion>
                <AccordionSummary expandIcon={<ExpandIcon />}>
                    <Typography variant="h6" fontWeight={600}>
                        System-Level Optimizations
                    </Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <CodeBlock
                        language="bash"
                        title="/etc/sysctl.d/99-baseapi.conf"
                        code={`# Network optimizations
net.core.somaxconn = 1024
net.ipv4.tcp_max_syn_backlog = 1024
net.ipv4.tcp_tw_reuse = 1
net.ipv4.tcp_fin_timeout = 30

# File descriptor limits
fs.file-max = 100000`}
                    />

                    <CodeBlock
                        language="bash"
                        title="/etc/security/limits.d/baseapi.conf"
                        code={`# File descriptor limits for web server
www-data soft nofile 65536
www-data hard nofile 65536`}
                    />
                </AccordionDetails>
            </Accordion>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Monitoring and Maintenance
            </Typography>

            <Typography>
                Essential monitoring for production BaseAPI deployments:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="Application Logs"
                        secondary="Monitor storage/logs/ for errors and performance issues"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Web Server Access Logs"
                        secondary="Track API usage patterns and response times"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="PHP-FPM Status"
                        secondary="Monitor process pool usage and performance metrics"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Database Performance"
                        secondary="Monitor query performance and connection pool usage"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="Redis Metrics"
                        secondary="Track cache hit rates and memory usage"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="System Resources"
                        secondary="CPU, memory, disk usage, and network metrics"
                    />
                </ListItem>
            </List>

            <Alert severity="success" sx={{ mt: 4 }}>
                <strong>Production Deployment Complete!</strong> Your BaseAPI application is now configured
                for high-performance production use with security best practices, caching, and monitoring.
            </Alert>
        </Box>
    );
}
