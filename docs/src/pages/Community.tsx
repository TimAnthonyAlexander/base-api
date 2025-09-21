import {
    Box,
    Typography,
    Card,
    CardContent,
    CardActions,
    Button,
    Grid,
    List,
    ListItem,
    ListItemIcon,
    ListItemText,
    Alert,
    Chip,
    Link as MuiLink
} from '@mui/material';
import {
    GitHub as GitHubIcon,
    Forum as DiscussionIcon,
    QuestionAnswer as QAIcon,
    BugReport as BugIcon,
    Lightbulb as FeatureIcon,
    Book as DocsIcon,
    School as LearningIcon
} from '@mui/icons-material';

const communityResources = [
    {
        title: 'GitHub Repository',
        description: 'Source code, issues, and contributions',
        icon: <GitHubIcon />,
        link: 'https://github.com/timanthonyalexander/base-api',
        color: 'primary',
        actions: [
            { label: 'View Source', href: 'https://github.com/timanthonyalexander/base-api' },
            { label: 'Report Bug', href: 'https://github.com/timanthonyalexander/base-api/issues/new?template=bug_report.md' }
        ]
    },
    {
        title: 'GitHub Discussions',
        description: 'Community Q&A, announcements, and general discussion',
        icon: <DiscussionIcon />,
        link: 'https://github.com/timanthonyalexander/base-api/discussions',
        color: 'secondary',
        actions: [
            { label: 'Join Discussions', href: 'https://github.com/timanthonyalexander/base-api/discussions' },
            { label: 'Ask Question', href: 'https://github.com/timanthonyalexander/base-api/discussions/new?category=q-a' }
        ]
    },
    {
        title: 'Feature Requests',
        description: 'Suggest new features and vote on existing proposals',
        icon: <FeatureIcon />,
        link: 'https://github.com/timanthonyalexander/base-api/issues/new?template=feature_request.md',
        color: 'warning',
        actions: [
            { label: 'Request Feature', href: 'https://github.com/timanthonyalexander/base-api/issues/new?template=feature_request.md' },
        ],
    },
    {
        title: 'Stack Overflow',
        description: 'Technical questions and programming help',
        icon: <QAIcon />,
        link: 'https://stackoverflow.com/questions/tagged/baseapi',
        color: 'info',
        actions: [
            { label: 'Ask Question', href: 'https://stackoverflow.com/questions/ask?tags=baseapi,php' },
            { label: 'Browse Questions', href: 'https://stackoverflow.com/questions/tagged/baseapi' }
        ]
    }
];

const contributionAreas = [
    {
        title: 'Code Contributions',
        description: 'Help improve BaseAPI core, fix bugs, and add features',
        tasks: ['Bug fixes', 'Feature implementation', 'Performance improvements', 'Code refactoring']
    },
    {
        title: 'Documentation',
        description: 'Improve guides, add examples, and fix typos',
        tasks: ['Writing tutorials', 'API documentation', 'Code examples', 'Translation']
    },
    {
        title: 'Testing',
        description: 'Help test new releases and report issues',
        tasks: ['Manual testing', 'Writing test cases', 'Performance testing', 'Bug reporting']
    },
    {
        title: 'Community Support',
        description: 'Help other developers in discussions and forums',
        tasks: ['Answer questions', 'Code reviews', 'Mentoring', 'Community moderation']
    }
];

export default function Community() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Community & Support
            </Typography>

            <Typography variant="h5" color="text.secondary" paragraph>
                Join the BaseAPI community and get help from fellow developers
            </Typography>

            <Typography>
                BaseAPI has a growing community of developers who are eager to help each other build better APIs.
                Whether you need help, want to contribute, or just want to stay up-to-date with the latest developments,
                there are several ways to get involved.
            </Typography>

            {/* Community Resources */}
            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Community Resources
            </Typography>

            <Grid container spacing={3} sx={{ mb: 6 }}>
                {communityResources.map((resource, index) => (
                    <Grid {...({ item: true } as any)} xs={12} sm={6} md={6} key={index}>
                        <Card
                            sx={{
                                height: '100%',
                                display: 'flex',
                                flexDirection: 'column',
                                border: 1,
                                borderColor: 'divider',
                                '&:hover': {
                                    boxShadow: (theme) => theme.shadows[4]
                                }
                            }}
                            elevation={0}
                        >
                            <CardContent sx={{ flexGrow: 1 }}>
                                <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                                    <Box sx={{ color: `${resource.color}.main`, mr: 1 }}>
                                        {resource.icon}
                                    </Box>
                                    <Typography variant="h6" component="h3">
                                        {resource.title}
                                    </Typography>
                                </Box>
                                <Typography variant="body2" color="text.secondary">
                                    {resource.description}
                                </Typography>
                            </CardContent>
                            <CardActions>
                                {resource.actions.map((action, actionIndex) => (
                                    <Button
                                        key={actionIndex}
                                        size="small"
                                        component={MuiLink}
                                        href={action.href}
                                        target={action.href.startsWith('http') ? '_blank' : '_self'}
                                        rel={action.href.startsWith('http') ? 'noopener noreferrer' : undefined}
                                    >
                                        {action.label}
                                    </Button>
                                ))}
                            </CardActions>
                        </Card>
                    </Grid>
                ))}
            </Grid>

            {/* Getting Help */}
            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Getting Help
            </Typography>

            <Alert severity="info" sx={{ mb: 3 }}>
                Before asking for help, please check the documentation and search existing discussions/issues
                to see if your question has already been answered.
            </Alert>

            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Where to Get Help
            </Typography>

            <List>
                <ListItem>
                    <ListItemIcon>
                        <DocsIcon color="primary" />
                    </ListItemIcon>
                    <ListItemText
                        primary="Documentation First"
                        secondary="Check our comprehensive docs, guides, and FAQ section"
                    />
                </ListItem>
                <ListItem>
                    <ListItemIcon>
                        <DiscussionIcon color="secondary" />
                    </ListItemIcon>
                    <ListItemText
                        primary="GitHub Discussions"
                        secondary="Best for general questions, architecture advice, and community help"
                    />
                </ListItem>
                <ListItem>
                    <ListItemIcon>
                        <QAIcon color="info" />
                    </ListItemIcon>
                    <ListItemText
                        primary="Stack Overflow"
                        secondary="Great for specific technical questions with the #baseapi tag"
                    />
                </ListItem>
                <ListItem>
                    <ListItemIcon>
                        <BugIcon color="error" />
                    </ListItemIcon>
                    <ListItemText
                        primary="GitHub Issues"
                        secondary="For bug reports and feature requests (not general support)"
                    />
                </ListItem>
            </List>

            {/* How to Ask Questions */}
            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                How to Ask Good Questions
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="1. Be Specific"
                        secondary="Include exact error messages, code snippets, and environment details"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="2. Show What You've Tried"
                        secondary="Explain what approaches you've attempted and what didn't work"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="3. Provide Context"
                        secondary="Describe what you're trying to achieve and your use case"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="4. Include Relevant Code"
                        secondary="Share minimal, reproducible examples when possible"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="5. Use Proper Tags"
                        secondary="Tag your questions with 'baseapi' and relevant technology tags"
                    />
                </ListItem>
            </List>

            {/* Contributing */}
            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Contributing to BaseAPI
            </Typography>

            <Typography>
                BaseAPI is open source and we welcome contributions from the community. There are many ways
                to contribute, regardless of your experience level:
            </Typography>

            <Grid container spacing={3} sx={{ mb: 4 }}>
                {contributionAreas.map((area, index) => (
                    <Grid {...({ item: true } as any)} xs={12} sm={6} key={index}>
                        <Card
                            sx={{
                                height: '100%',
                                border: 1,
                                borderColor: 'divider'
                            }}
                            elevation={0}
                        >
                            <CardContent>
                                <Typography variant="h6" gutterBottom>
                                    {area.title}
                                </Typography>
                                <Typography variant="body2" color="text.secondary" paragraph>
                                    {area.description}
                                </Typography>
                                <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5 }}>
                                    {area.tasks.map((task, taskIndex) => (
                                        <Chip
                                            key={taskIndex}
                                            label={task}
                                            size="small"
                                            variant="outlined"
                                        />
                                    ))}
                                </Box>
                            </CardContent>
                        </Card>
                    </Grid>
                ))}
            </Grid>

            {/* Contribution Process */}
            <Typography variant="h3" gutterBottom sx={{ mt: 3 }}>
                Contribution Process
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="1. Check Existing Issues"
                        secondary="Look for existing issues or discussions related to your contribution"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="2. Fork the Repository"
                        secondary="Create a fork of the BaseAPI repository on GitHub"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="3. Create a Branch"
                        secondary="Create a feature branch for your changes (feature/your-feature-name)"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="4. Make Changes"
                        secondary="Implement your changes with proper tests and documentation"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="5. Submit Pull Request"
                        secondary="Create a pull request with a clear description of your changes"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="6. Code Review"
                        secondary="Collaborate with maintainers to refine your contribution"
                    />
                </ListItem>
            </List>

            {/* Code of Conduct */}
            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Code of Conduct
            </Typography>

            <Typography>
                Our community values inclusivity, respect, and constructive collaboration. We expect all
                community members to:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText primary="Be respectful and inclusive in all interactions" />
                </ListItem>
                <ListItem>
                    <ListItemText primary="Focus on constructive feedback and solutions" />
                </ListItem>
                <ListItem>
                    <ListItemText primary="Help create a welcoming environment for newcomers" />
                </ListItem>
                <ListItem>
                    <ListItemText primary="Respect different perspectives and experience levels" />
                </ListItem>
                <ListItem>
                    <ListItemText primary="Follow the golden rule: treat others as you'd like to be treated" />
                </ListItem>
            </List>

            {/* Stay Updated */}
            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Stay Updated
            </Typography>

            <Typography>
                Keep up with BaseAPI development and community news:
            </Typography>

            <List>
                <ListItem>
                    <ListItemIcon>
                        <GitHubIcon />
                    </ListItemIcon>
                    <ListItemText
                        primary="Watch the Repository"
                        secondary="Get notifications about new releases and important updates"
                    />
                </ListItem>
                <ListItem>
                    <ListItemIcon>
                        <DiscussionIcon />
                    </ListItemIcon>
                    <ListItemText
                        primary="Follow Discussions"
                        secondary="Subscribe to announcement discussions for major updates"
                    />
                </ListItem>
            </List>

            {/* Call to Action */}
            <Alert severity="success" sx={{ mt: 4 }}>
                <Typography variant="h6" gutterBottom>
                    Ready to Join the Community?
                </Typography>
                <Typography>
                    Start by introducing yourself in{' '}
                    <MuiLink
                        href="https://github.com/timanthonyalexander/base-api/discussions"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        GitHub Discussions
                    </MuiLink>
                    {' '}or help answer a question on{' '}
                    <MuiLink
                        href="https://stackoverflow.com/questions/tagged/baseapi"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Stack Overflow
                    </MuiLink>
                    . Every contribution, big or small, helps make BaseAPI better for everyone!
                </Typography>
            </Alert>
        </Box>
    );
}
