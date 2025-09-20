
import {
    Box,
    Typography,
    Button,
    List,
    ListItem,
    ListItemIcon,
    ListItemText,
    Stepper,
    Step,
    StepLabel,
    StepContent,
} from '@mui/material';
import {
    Check as CheckIcon,
    ArrowForward as ArrowIcon,
} from '@mui/icons-material';
import { Link } from 'react-router-dom';
import CodeBlock from '../../components/CodeBlock';
import Callout from '../../components/Callout';

const requirements = [
    'PHP 8.4 or higher',
    'Composer (for dependency management)',
    'One of: SQLite (default), MySQL, or PostgreSQL',
    'Optional: Redis (for production caching)',
];

const steps = [
    {
        label: 'Check Requirements',
        content: 'Ensure you have PHP 8.4+ and Composer installed on your system.',
        code: `# Check PHP version
php --version

# Check Composer installation
composer --version`,
    },
    {
        label: 'Create New Project',
        content: 'Use Composer to create a new BaseAPI project from the template.',
        code: `composer create-project baseapi/baseapi-template my-api
cd my-api`,
    },
    {
        label: 'Configure Environment',
        content: 'The .env file is automatically created from .env.example during installation.',
        code: `# View your configuration
cat .env

# The default configuration uses SQLite and is ready to go!`,
    },
    {
        label: 'Start Development Server',
        content: 'Launch the built-in development server to test your installation.',
        code: `php bin/console serve`,
    },
    {
        label: 'Test Installation',
        content: 'Visit the health endpoint to verify everything is working.',
        code: `curl http://localhost:7879/health`,
    },
];

export default function Installation() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Installation
            </Typography>

            <Typography variant="h5" color="text.secondary" paragraph>
                Get BaseAPI up and running in minutes with our simple installation process.
            </Typography>

            {/* Requirements */}
            <Box sx={{ mb: 4 }}>
                <Typography variant="h2" gutterBottom>
                    Requirements
                </Typography>
                <List>
                    {requirements.map((requirement, index) => (
                        <ListItem key={index} disableGutters>
                            <ListItemIcon>
                                <CheckIcon color="success" />
                            </ListItemIcon>
                            <ListItemText primary={requirement} />
                        </ListItem>
                    ))}
                </List>
            </Box>

            <Callout type="tip">
                <Typography>
                    <strong>First time with PHP?</strong> Check out our{' '}
                    <Link to="/troubleshooting/common-errors">troubleshooting guide</Link>{' '}
                    for help setting up your PHP development environment.
                </Typography>
            </Callout>

            {/* Installation Steps */}
            <Box sx={{ mb: 4 }}>
                <Typography variant="h2" gutterBottom>
                    Installation Steps
                </Typography>

                <Stepper orientation="vertical">
                    {steps.map((step, index) => (
                        <Step key={index} active expanded>
                            <StepLabel>
                                <Typography variant="h6" fontWeight={600}>
                                    {step.label}
                                </Typography>
                            </StepLabel>
                            <StepContent>
                                <Typography color="text.secondary">
                                    {step.content}
                                </Typography>
                                {step.code && (
                                    <CodeBlock
                                        language="bash"
                                        code={step.code}
                                    />
                                )}
                            </StepContent>
                        </Step>
                    ))}
                </Stepper>
            </Box>

            <Callout type="success">
                <Typography>
                    <strong>Installation complete!</strong> Your API is now running at{' '}
                    <code>http://localhost:7879</code>. The health endpoint should return a success response.
                </Typography>
            </Callout>

            {/* What's Included */}
            <Box sx={{ mb: 4 }}>
                <Typography variant="h2" gutterBottom>
                    What's Included
                </Typography>

                <Typography>
                    The BaseAPI template comes with everything you need to start building:
                </Typography>

                <List>
                    {[
                        'User model with authentication endpoints',
                        'Basic controllers (Login, Signup, Me, Health, Logout)',
                        'Database configuration (SQLite by default)',
                        'CORS and security middleware',
                        'Rate limiting configuration',
                        'Logging setup',
                    ].map((item, index) => (
                        <ListItem key={index} disableGutters>
                            <ListItemIcon>
                                <CheckIcon color="primary" />
                            </ListItemIcon>
                            <ListItemText primary={item} />
                        </ListItem>
                    ))}
                </List>
            </Box>

            {/* Next Steps */}
            <Box sx={{ mb: 4 }}>
                <Typography variant="h2" gutterBottom>
                    Next Steps
                </Typography>

                <Typography>
                    Now that BaseAPI is installed, you're ready to start building your API:
                </Typography>

                <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap' }}>
                    <Button
                        component={Link}
                        to="/getting-started/first-api"
                        variant="contained"
                        endIcon={<ArrowIcon />}
                    >
                        Create Your First API
                    </Button>

                    <Button
                        component={Link}
                        to="/getting-started/project-structure"
                        variant="outlined"
                        endIcon={<ArrowIcon />}
                    >
                        Explore Project Structure
                    </Button>

                    <Button
                        component={Link}
                        to="/architecture/overview"
                        variant="text"
                        endIcon={<ArrowIcon />}
                    >
                        Learn the Architecture
                    </Button>
                </Box>
            </Box>

            {/* Troubleshooting */}
            <Callout type="info">
                <Typography>
                    <strong>Having issues?</strong> Check our{' '}
                    <Link to="/troubleshooting/common-errors">common errors guide</Link> or{' '}
                    <Link to="/troubleshooting/faq">FAQ section</Link> for help.
                </Typography>
            </Callout>
        </Box>
    );
}
