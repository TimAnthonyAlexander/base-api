
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
import ReactMarkdown from 'react-markdown';

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
        extraContent: 'Composer can be installed by following the instructions on [Composer Official Website](https://getcomposer.org/download/).',
    },
    {
        label: 'Create New Project',
        content: 'Use Composer to create a new BaseAPI project from the template.',
        code: `composer create-project baseapi/baseapi-template my-api`,
        extraContent: 'Replace `my-api` with your desired project directory name. This will create a new folder with all necessary files. \n Source: [BaseAPI Template REPO](https://github.com/timanthonyalexander/base-api-template)',
    },
    {
        label: 'Configure Environment',
        content: 'The .env file is automatically created from .env.example during installation.',
        extraContent: 'You can optionally customize this file if you want to change database settings, default port, or other configurations. Uses SQLite per default.',
    },
    {
        label: 'Start Development Server',
        content: 'Launch the built-in development server to test your installation.',
        code: `./mason serve`,
        extraContent: 'All built-in BaseAPI commands are run via the `./mason` command.',
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
                                {step.extraContent !== undefined &&
                                    <Typography color="text.secondary"
                                        sx={{
                                            whiteSpace: 'pre-wrap',
                                        }}
                                    >
                                        <ReactMarkdown
                                        >
                                            {step.extraContent}
                                        </ReactMarkdown>
                                    </Typography>
                                }
                            </StepContent>
                        </Step>
                    ))}
                </Stepper>
            </Box>

            <Callout type="success">
                <Typography>
                    <strong>Installation complete!</strong> Your API is now running at{' '} <code>http://localhost:7879</code>.<br />
                    The health endpoint should return a success response.
                </Typography>
            </Callout>

            {/* Next Steps */}
            <Box sx={{ my: 4 }}>
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
                        to="/fundamentals/overview"
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
