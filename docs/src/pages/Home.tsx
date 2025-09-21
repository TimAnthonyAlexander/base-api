import {
    Box,
    Typography,
    Button,
    Card,
    CardContent,
    CardActions,
    Stack,
    Chip,
    alpha,
} from '@mui/material';
import {
    Speed as PerformanceIcon,
    Security as SecurityIcon,
    Code as CodeIcon,
    Storage as DatabaseIcon,
    Language as I18nIcon,
    Description as DocsIcon,
    ArrowForward as ArrowIcon,
} from '@mui/icons-material';
import { Link } from 'react-router-dom';
import CodeBlock from '../components/CodeBlock';

const features = [
    {
        icon: <PerformanceIcon />,
        title: 'Competitive Performance',
        description: 'Minimal framework overhead with unified caching system for enhanced query performance',
        link: '/configuration/caching',
    },
    {
        icon: <SecurityIcon />,
        title: 'Built-in Security',
        description: 'CORS, rate limiting, input validation, and authentication middlewares included out of the box',
        link: '/security/overview',
    },
    {
        icon: <DatabaseIcon />,
        title: 'Database Agnostic',
        description: 'Automatic migrations from model definitions. Supports MySQL, SQLite, and PostgreSQL',
        link: '/database/drivers',
    },
    {
        icon: <CodeIcon />,
        title: 'Low Configuration',
        description: 'Works out of the box with sensible defaults. Get started with just a few commands',
        link: '/getting-started/installation',
    },
    {
        icon: <I18nIcon />,
        title: 'Internationalization',
        description: 'Full i18n support with automatic translation providers (OpenAI, DeepL)',
        link: '/i18n/overview',
    },
    {
        icon: <DocsIcon />,
        title: 'Auto Documentation',
        description: 'Generate OpenAPI specs and TypeScript types with one command',
        link: '/cli/overview',
    },
];

const quickStartCode = `composer create-project baseapi/baseapi-template my-api
cd my-api

# Start the development server
./mason serve

# Create your first model
./mason make:model Product

# Generate and apply migrations
./mason migrate:generate
./mason migrate:apply`;

export default function Home() {
    return (
        <Box>
            {/* Hero Section */}
            <Box
                sx={{
                    background: (theme) => `linear-gradient(135deg, ${alpha(theme.palette.primary.main, 0.1)} 0%, ${alpha(theme.palette.secondary.main, 0.05)} 100%)`,
                    borderRadius: 3,
                    p: { xs: 1.5, sm: 3, md: 6 },
                    mx: { xs: 0.5, sm: 0 },
                    mb: 6,
                    textAlign: 'center',
                    position: 'relative',
                    overflow: 'hidden',
                }}
            >
                <Box sx={{ maxWidth: { xs: 'calc(100vw - 24px)', sm: 600, md: 800, lg: 1000, xl: 1200 }, mx: 'auto', px: { xs: 1, sm: 2 } }}>
                    <Typography
                        variant="h1"
                        sx={{
                            fontSize: { xs: '2rem', sm: '2.5rem', md: '3.5rem', lg: '4rem' },
                            fontWeight: 700,
                            mb: 2,
                            background: (theme) => `linear-gradient(45deg, ${theme.palette.primary.main}, ${theme.palette.secondary.main})`,
                            backgroundClip: 'text',
                            WebkitBackgroundClip: 'text',
                            color: 'transparent',
                            lineHeight: 1.2,
                        }}
                    >
                        BaseAPI
                    </Typography>

                    <Typography
                        variant="h4"
                        color="text.secondary"
                        sx={{
                            mb: 3,
                            fontWeight: 400,
                            fontSize: { xs: '1.25rem', md: '1.5rem' },
                        }}
                    >
                        A tiny, KISS-first PHP 8.4+ framework for building REST APIs
                    </Typography>

                    <Stack
                        direction="row"
                        spacing={1}
                        justifyContent="center"
                        sx={{ mb: 4, flexWrap: 'wrap', gap: 1 }}
                    >
                        <Chip label="PHP 8.4+" color="primary" />
                        <Chip label="Zero Configuration" variant="outlined" />
                        <Chip label="Competitive Performance" variant="outlined" />
                        <Chip label="Built-in Security" variant="outlined" />
                    </Stack>

                    <Stack
                        direction={{ xs: 'column', sm: 'row' }}
                        spacing={2}
                        justifyContent="center"
                    >
                        <Button
                            component={Link}
                            to="/getting-started/installation"
                            variant="contained"
                            size="large"
                            endIcon={<ArrowIcon />}
                            sx={{
                                px: 4,
                                py: 1.5,
                                fontSize: '1.1rem',
                                borderRadius: 2,
                                background: (theme) => `linear-gradient(45deg, ${theme.palette.primary.main}, ${theme.palette.primary.dark})`,
                                '&:hover': {
                                    background: (theme) => `linear-gradient(45deg, ${theme.palette.primary.dark}, ${theme.palette.primary.main})`,
                                },
                            }}
                        >
                            Get Started
                        </Button>
                        <Button
                            component={Link}
                            to="/architecture/overview"
                            variant="outlined"
                            size="large"
                            sx={{
                                px: 4,
                                py: 1.5,
                                fontSize: '1.1rem',
                                borderRadius: 2,
                                borderWidth: 2,
                                '&:hover': {
                                    borderWidth: 2,
                                    backgroundColor: alpha('#000', 0.02),
                                },
                            }}
                        >
                            Learn More
                        </Button>
                    </Stack>
                </Box>
            </Box>

            {/* Quick Start */}
            <Box sx={{ mb: 6 }}>
                <Typography variant="h3" gutterBottom sx={{ textAlign: 'center', mb: 4 }}>
                    Quick Start
                </Typography>
                <Box sx={{ maxWidth: { xs: 'calc(100vw - 24px)', sm: 600, md: 800, lg: 1000, xl: 1200 }, mx: 'auto', px: { xs: 1, sm: 0 } }}>
                    <CodeBlock
                        language="bash"
                        code={quickStartCode}
                        title="Get started in minutes"
                    />
                    <Box sx={{ textAlign: 'center', mt: 3 }}>
                        <Typography variant="body1" color="text.secondary" sx={{ mb: 2 }}>
                            Your API will be available at <code>http://localhost:7879</code>
                        </Typography>
                        <Button
                            component={Link}
                            to="/getting-started/first-api"
                            variant="text"
                            endIcon={<ArrowIcon />}
                        >
                            Continue with the tutorial
                        </Button>
                    </Box>
                </Box>
            </Box>

            {/* Features Grid */}
            <Box sx={{ mb: 6 }}>
                <Typography variant="h3" gutterBottom sx={{ textAlign: 'center', mb: 4 }}>
                    Why BaseAPI?
                </Typography>
                <Box sx={{
                    display: 'grid',
                    gridTemplateColumns: {
                        xs: '1fr',
                        sm: 'repeat(2, 1fr)',
                        lg: 'repeat(3, 1fr)',
                        xl: 'repeat(3, 1fr)'
                    },
                    gap: { xs: 1.5, sm: 3, lg: 4, xl: 5 },
                    maxWidth: { xs: 'calc(100vw - 16px)', sm: 'none', lg: '95%', xl: '90%' },
                    mx: 'auto',
                    px: { xs: 1, sm: 0 }
                }}>
                    {features.map((feature, index) => (
                        <Box key={index}>
                            <Card
                                sx={{
                                    height: '100%',
                                    transition: 'all 0.3s ease',
                                    '&:hover': {
                                        transform: 'translateY(-4px)',
                                        boxShadow: (theme) => theme.shadows[8],
                                    },
                                    border: 1,
                                    borderColor: 'divider',
                                }}
                                elevation={0}
                            >
                                <CardContent sx={{ pb: 1 }}>
                                    <Box
                                        sx={{
                                            color: 'primary.main',
                                            mb: 2,
                                            '& svg': { fontSize: '2rem' },
                                        }}
                                    >
                                        {feature.icon}
                                    </Box>
                                    <Typography variant="h6" gutterBottom fontWeight={600}>
                                        {feature.title}
                                    </Typography>
                                    <Typography variant="body2" color="text.secondary" sx={{ lineHeight: 1.6 }}>
                                        {feature.description}
                                    </Typography>
                                </CardContent>
                                <CardActions>
                                    <Button
                                        component={Link}
                                        to={feature.link}
                                        size="small"
                                        endIcon={<ArrowIcon fontSize="small" />}
                                    >
                                        Learn more
                                    </Button>
                                </CardActions>
                            </Card>
                        </Box>
                    ))}
                </Box>
            </Box>

            {/* Benchmarks Section */}
            <Box sx={{ mb: 6 }}>
                <Typography variant="h3" gutterBottom sx={{ textAlign: 'center', mb: 4 }}>
                    Performance Benchmarks
                </Typography>

                <Typography variant="body1" paragraph sx={{ textAlign: 'center', mb: 4, maxWidth: 800, mx: 'auto' }}>
                    BaseAPI delivers competitive performance among lightweight PHP frameworks.
                    Benchmarks run on PHP 8.4 with OPcache enabled, testing a simple "Hello World" JSON API endpoint.
                </Typography>

                <Box sx={{
                    background: (theme) => alpha(theme.palette.primary.main, 0.02),
                    borderRadius: 3,
                    p: { xs: 2, sm: 4 },
                    mx: { xs: 0.5, sm: 0 },
                    border: 1,
                    borderColor: 'divider',
                }}>
                    {/* Requests per Second Chart */}
                    <Typography variant="h5" gutterBottom sx={{ textAlign: 'center', mb: 3 }}>
                        Requests per Second (Higher is Better)
                    </Typography>

                    <Box sx={{ maxWidth: 800, mx: 'auto' }}>
                        {[
                            { name: 'BaseAPI', value: 1350, color: 'primary.main' },
                            { name: 'Slim Framework', value: 1200, color: 'secondary.main' },
                            { name: 'Laravel Lumen', value: 950, color: 'warning.main' },
                            { name: 'Symfony MicroKernel', value: 750, color: 'error.main' },
                        ].map((framework) => (
                            <Box key={framework.name} sx={{ mb: 2 }}>
                                <Box sx={{
                                    display: 'flex',
                                    justifyContent: 'space-between',
                                    alignItems: 'center',
                                    mb: 0.5
                                }}>
                                    <Typography variant="body1" fontWeight={600}>
                                        {framework.name}
                                    </Typography>
                                    <Typography variant="body1" color="text.secondary">
                                        {framework.value.toLocaleString()} req/s
                                    </Typography>
                                </Box>
                                <Box sx={{
                                    height: 8,
                                    backgroundColor: 'grey.200',
                                    borderRadius: 1,
                                    overflow: 'hidden'
                                }}>
                                    <Box sx={{
                                        width: `${(framework.value / 1350) * 100}%`,
                                        height: '100%',
                                        backgroundColor: framework.color,
                                        borderRadius: 1,
                                        transition: 'width 1s ease-in-out'
                                    }} />
                                </Box>
                            </Box>
                        ))}
                    </Box>

                    {/* Memory Usage Chart */}
                    <Typography variant="h5" gutterBottom sx={{ textAlign: 'center', mb: 3, mt: 5 }}>
                        Memory Usage per Request (Lower is Better)
                    </Typography>

                    <Box sx={{ maxWidth: 800, mx: 'auto' }}>
                        {[
                            { name: 'BaseAPI', value: 0.8, color: 'primary.main' },
                            { name: 'Slim Framework', value: 0.9, color: 'secondary.main' },
                            { name: 'Laravel Lumen', value: 1.0, color: 'warning.main' },
                            { name: 'Symfony MicroKernel', value: 1.3, color: 'error.main' },
                        ].map((framework) => (
                            <Box key={framework.name} sx={{ mb: 2 }}>
                                <Box sx={{
                                    display: 'flex',
                                    justifyContent: 'space-between',
                                    alignItems: 'center',
                                    mb: 0.5
                                }}>
                                    <Typography variant="body1" fontWeight={600}>
                                        {framework.name}
                                    </Typography>
                                    <Typography variant="body1" color="text.secondary">
                                        {framework.value} MB
                                    </Typography>
                                </Box>
                                <Box sx={{
                                    height: 8,
                                    backgroundColor: 'grey.200',
                                    borderRadius: 1,
                                    overflow: 'hidden'
                                }}>
                                    <Box sx={{
                                        width: `${(framework.value / 1.3) * 100}%`,
                                        height: '100%',
                                        backgroundColor: framework.color,
                                        borderRadius: 1,
                                        transition: 'width 1s ease-in-out'
                                    }} />
                                </Box>
                            </Box>
                        ))}
                    </Box>
                </Box>

                {/* Test Details */}
                <Box sx={{ mt: 3, textAlign: 'center' }}>
                    <Typography variant="caption" color="text.secondary" display="block">
                        Test Environment: PHP 8.4 + OPcache, nginx + PHP-FPM, 18GB RAM, Apple M3 Pro
                    </Typography>
                    <Typography variant="caption" color="text.secondary" display="block">
                        Load Test: wrk -t8 -c100 -d30s --latency (30 seconds, 100 concurrent connections)
                    </Typography>
                </Box>
            </Box>

            {/* Stats Section */}
            <Box
                sx={{
                    background: (theme) => alpha(theme.palette.primary.main, 0.05),
                    borderRadius: 3,
                    p: { xs: 2.5, sm: 4 },
                    mx: { xs: 0.5, sm: 0 },
                    textAlign: 'center',
                }}
            >
                <Typography variant="h4" gutterBottom>
                    Built for Performance
                </Typography>
                <Box sx={{
                    display: 'grid',
                    gridTemplateColumns: { xs: '1fr', sm: 'repeat(3, 1fr)' },
                    gap: { xs: 2, sm: 4, lg: 6, xl: 8 },
                    mt: 2,
                    maxWidth: { xs: '100%', sm: 800, lg: 1000, xl: 1200 },
                    mx: 'auto'
                }}>
                    <Box sx={{ textAlign: 'center' }}>
                        <Typography variant="h3" color="primary.main" fontWeight={700}>
                            &lt;1ms
                        </Typography>
                        <Typography variant="body1" color="text.secondary">
                            Framework overhead per request
                        </Typography>
                    </Box>
                    <Box sx={{ textAlign: 'center' }}>
                        <Typography variant="h3" color="primary.main" fontWeight={700}>
                            1,350+
                        </Typography>
                        <Typography variant="body1" color="text.secondary">
                            Requests per second
                        </Typography>
                    </Box>
                    <Box sx={{ textAlign: 'center' }}>
                        <Typography variant="h3" color="primary.main" fontWeight={700}>
                            0.8MB
                        </Typography>
                        <Typography variant="body1" color="text.secondary">
                            Memory per request
                        </Typography>
                    </Box>
                </Box>
            </Box>
        </Box>
    );
}
