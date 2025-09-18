import {
  Box,
  Typography,
  Button,
  Card,
  CardContent,
  CardActions,
  Container,
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
    title: 'High Performance',
    description: 'Minimal overhead (<0.01ms per request) with unified caching system for 10x+ query performance',
    link: '/performance/benchmarks',
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
    link: '/cli/docs-generation',
  },
];

const quickStartCode = `# Create a new BaseAPI project
composer create-project baseapi/baseapi-template my-api
cd my-api

# Start the development server
php bin/console serve

# Create your first model
php bin/console make:model Product

# Generate and apply migrations
php bin/console migrate:generate
php bin/console migrate:apply`;

export default function Home() {
  return (
    <Box>
      {/* Hero Section */}
      <Box
        sx={{
          background: (theme) => `linear-gradient(135deg, ${alpha(theme.palette.primary.main, 0.1)} 0%, ${alpha(theme.palette.secondary.main, 0.05)} 100%)`,
          borderRadius: 3,
          p: { xs: 4, md: 6 },
          mb: 6,
          textAlign: 'center',
          position: 'relative',
          overflow: 'hidden',
        }}
      >
        <Container maxWidth="md">
          <Typography
            variant="h1"
            sx={{
              fontSize: { xs: '2.5rem', md: '3.5rem' },
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
            <Chip label="High Performance" variant="outlined" />
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
        </Container>
      </Box>

      {/* Quick Start */}
      <Box sx={{ mb: 6 }}>
        <Typography variant="h3" gutterBottom sx={{ textAlign: 'center', mb: 4 }}>
          Quick Start
        </Typography>
        <Container maxWidth="md">
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
        </Container>
      </Box>

      {/* Features Grid */}
      <Box sx={{ mb: 6 }}>
        <Typography variant="h3" gutterBottom sx={{ textAlign: 'center', mb: 4 }}>
          Why BaseAPI?
        </Typography>
        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, 1fr)', lg: 'repeat(3, 1fr)' }, gap: 3 }}>
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

      {/* Stats Section */}
      <Box
        sx={{
          background: (theme) => alpha(theme.palette.primary.main, 0.05),
          borderRadius: 3,
          p: 4,
          textAlign: 'center',
        }}
      >
        <Typography variant="h4" gutterBottom>
          Built for Performance
        </Typography>
        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', sm: 'repeat(3, 1fr)' }, gap: 4, mt: 2 }}>
          <Box sx={{ textAlign: 'center' }}>
            <Typography variant="h3" color="primary.main" fontWeight={700}>
              &lt;0.01ms
            </Typography>
            <Typography variant="body1" color="text.secondary">
              Framework overhead per request
            </Typography>
          </Box>
          <Box sx={{ textAlign: 'center' }}>
            <Typography variant="h3" color="primary.main" fontWeight={700}>
              10x+
            </Typography>
            <Typography variant="body1" color="text.secondary">
              Faster with unified caching
            </Typography>
          </Box>
          <Box sx={{ textAlign: 'center' }}>
            <Typography variant="h3" color="primary.main" fontWeight={700}>
              3
            </Typography>
            <Typography variant="body1" color="text.secondary">
              Database drivers supported
            </Typography>
          </Box>
        </Box>
      </Box>
    </Box>
  );
}
