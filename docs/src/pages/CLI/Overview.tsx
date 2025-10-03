
import { Box, Typography, Alert, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper, List, ListItem, ListItemText } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';

export default function CLIOverview() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                CLI Overview
            </Typography>
            <Typography variant="h5" color="text.secondary" paragraph>
                BaseAPI's powerful command-line interface for development and deployment
            </Typography>

            <Typography>
                BaseAPI includes a comprehensive CLI tool that handles development tasks like code generation,
                database migrations, cache management, and deployment operations. The CLI is built for
                developer productivity and automation.
            </Typography>

            <Alert severity="info" sx={{ my: 3 }}>
                All CLI commands are accessed through <code>./mason</code> and include helpful
                documentation and examples built-in.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Getting Started
            </Typography>

            <Typography>
                Run the console without arguments to see all available commands:
            </Typography>

            <CodeBlock language="bash" code={`# Show all available commands
./mason

# Get help for a specific command
./mason migrate:generate --help

# Get version information
./mason --version`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Development Workflow
            </Typography>

            <Typography>
                Common CLI commands for daily development:
            </Typography>

            <CodeBlock language="bash" code={`# 1. Start development server
./mason serve

# 2. Create a new model
./mason make:model Product

# 3. Create a controller
./mason make:controller ProductController

# 4. Generate migrations from models
./mason migrate:generate

# 5. Apply migrations to database
./mason migrate:apply

# 6. Generate API documentation
./mason types:generate`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Complete Command Reference
            </Typography>

            <Typography>
                BaseAPI provides a comprehensive CLI with commands for every aspect of development and deployment:
            </Typography>

            {/* Development Commands */}
            <Typography variant="h3" gutterBottom sx={{ mt: 4 }}>
                Development Commands
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }} elevation={0}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Command</strong></TableCell>
                            <TableCell><strong>Description</strong></TableCell>
                            <TableCell><strong>Examples</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            <TableCell><code>serve</code></TableCell>
                            <TableCell>Start the development server</TableCell>
                            <TableCell>
                                <code>./mason serve</code><br />
                                <code>./mason serve --host=0.0.0.0 --port=8080</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>make:controller</code></TableCell>
                            <TableCell>Generate a new controller class</TableCell>
                            <TableCell>
                                <code>./mason make:controller UserController</code><br />
                                <code>./mason make:controller Admin/UserController</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>make:model</code></TableCell>
                            <TableCell>Generate a new model class</TableCell>
                            <TableCell>
                                <code>./mason make:model User</code><br />
                                <code>./mason make:model Product --with-controller</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>make:service</code></TableCell>
                            <TableCell>Generate a new service class</TableCell>
                            <TableCell>
                                <code>./mason make:service EmailService</code><br />
                                <code>./mason make:service Payment/StripeService</code>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>

            {/* Database Commands */}
            <Typography variant="h3" gutterBottom sx={{ mt: 4 }}>
                Database Commands
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }} elevation={0}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Command</strong></TableCell>
                            <TableCell><strong>Description</strong></TableCell>
                            <TableCell><strong>Examples</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            <TableCell><code>migrate:generate</code></TableCell>
                            <TableCell>Generate migrations from model definitions</TableCell>
                            <TableCell>
                                <code>./mason migrate:generate</code><br />
                                <code>./mason migrate:generate --dry-run</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>migrate:apply</code></TableCell>
                            <TableCell>Apply pending migrations to database</TableCell>
                            <TableCell>
                                <code>./mason migrate:apply</code><br />
                                <code>./mason migrate:apply --force</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>migrate:rollback</code></TableCell>
                            <TableCell>Rollback the last migration batch</TableCell>
                            <TableCell>
                                <code>./mason migrate:rollback</code><br />
                                <code>./mason migrate:rollback --steps=3</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>db:seed</code></TableCell>
                            <TableCell>Seed database with test data</TableCell>
                            <TableCell>
                                <code>./mason db:seed</code><br />
                                <code>./mason db:seed --class=UserSeeder</code>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>

            {/* Cache Commands */}
            <Typography variant="h3" gutterBottom sx={{ mt: 4 }}>
                Cache Commands
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }} elevation={0}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Command</strong></TableCell>
                            <TableCell><strong>Description</strong></TableCell>
                            <TableCell><strong>Examples</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            <TableCell><code>cache:clear</code></TableCell>
                            <TableCell>Clear all or specific cache entries</TableCell>
                            <TableCell>
                                <code>./mason cache:clear</code><br />
                                <code>./mason cache:clear --driver=redis</code><br />
                                <code>./mason cache:clear --tags=users,products</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>cache:stats</code></TableCell>
                            <TableCell>Display cache statistics and hit rates</TableCell>
                            <TableCell>
                                <code>./mason cache:stats</code><br />
                                <code>./mason cache:stats --driver=file</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>cache:cleanup</code></TableCell>
                            <TableCell>Remove expired cache entries</TableCell>
                            <TableCell>
                                <code>./mason cache:cleanup</code><br />
                                <code>./mason cache:cleanup --force</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>cache:warm</code></TableCell>
                            <TableCell>Warm up cache with essential data</TableCell>
                            <TableCell>
                                <code>./mason cache:warm</code><br />
                                <code>./mason cache:warm --routes --config</code>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>

            {/* Documentation Commands */}
            <Typography variant="h3" gutterBottom sx={{ mt: 4 }}>
                Documentation Commands
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }} elevation={0}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Command</strong></TableCell>
                            <TableCell><strong>Description</strong></TableCell>
                            <TableCell><strong>Examples</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            <TableCell><code>types:generate</code></TableCell>
                            <TableCell>Generate OpenAPI specs and TypeScript types</TableCell>
                            <TableCell>
                                <code>./mason types:generate</code><br />
                                <code>./mason types:generate --out-ts=types.d.ts</code><br />
                                <code>./mason types:generate --out-openapi=api.json</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>docs:generate</code></TableCell>
                            <TableCell>Generate API documentation from annotations</TableCell>
                            <TableCell>
                                <code>./mason docs:generate</code><br />
                                <code>./mason docs:generate --format=html</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>routes:list</code></TableCell>
                            <TableCell>List all registered routes</TableCell>
                            <TableCell>
                                <code>./mason routes:list</code><br />
                                <code>./mason routes:list --method=GET</code><br />
                                <code>./mason routes:list --filter=user</code>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>

            {/* Internationalization Commands */}
            <Typography variant="h3" gutterBottom sx={{ mt: 4 }}>
                Internationalization Commands
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }} elevation={0}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Command</strong></TableCell>
                            <TableCell><strong>Description</strong></TableCell>
                            <TableCell><strong>Examples</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            <TableCell><code>i18n:scan</code></TableCell>
                            <TableCell>Scan codebase for translation tokens</TableCell>
                            <TableCell>
                                <code>./mason i18n:scan</code><br />
                                <code>./mason i18n:scan --update</code><br />
                                <code>./mason i18n:scan --path=app/Controllers</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>i18n:add-lang</code></TableCell>
                            <TableCell>Add a new language to the project</TableCell>
                            <TableCell>
                                <code>./mason i18n:add-lang fr</code><br />
                                <code>./mason i18n:add-lang de --copy-from=en</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>i18n:fill</code></TableCell>
                            <TableCell>Fill missing translations using AI providers</TableCell>
                            <TableCell>
                                <code>./mason i18n:fill</code><br />
                                <code>./mason i18n:fill --provider=openai --locale=fr</code><br />
                                <code>./mason i18n:fill --provider=deepl --all</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>i18n:lint</code></TableCell>
                            <TableCell>Validate translation files</TableCell>
                            <TableCell>
                                <code>./mason i18n:lint</code><br />
                                <code>./mason i18n:lint --locale=fr</code><br />
                                <code>./mason i18n:lint --fix</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>i18n:export</code></TableCell>
                            <TableCell>Export translations to external formats</TableCell>
                            <TableCell>
                                <code>./mason i18n:export --format=po</code><br />
                                <code>./mason i18n:export --format=csv --locale=fr</code>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>

            {/* Queue Commands */}
            <Typography variant="h3" gutterBottom sx={{ mt: 4 }}>
                Queue Commands
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }} elevation={0}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Command</strong></TableCell>
                            <TableCell><strong>Description</strong></TableCell>
                            <TableCell><strong>Examples</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            <TableCell><code>queue:work</code></TableCell>
                            <TableCell>Process jobs in the queue</TableCell>
                            <TableCell>
                                <code>./mason queue:work</code><br />
                                <code>./mason queue:work --queue=emails --sleep=3</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>queue:status</code></TableCell>
                            <TableCell>Display queue status and statistics</TableCell>
                            <TableCell>
                                <code>./mason queue:status</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>queue:retry</code></TableCell>
                            <TableCell>Retry failed jobs by ID</TableCell>
                            <TableCell>
                                <code>./mason queue:retry --id=job_uuid</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>queue:install</code></TableCell>
                            <TableCell>Create the jobs table migration</TableCell>
                            <TableCell>
                                <code>./mason queue:install</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>make:job</code></TableCell>
                            <TableCell>Generate a new job class</TableCell>
                            <TableCell>
                                <code>./mason make:job SendEmailJob</code>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>

            {/* System Commands */}
            <Typography variant="h3" gutterBottom sx={{ mt: 4 }}>
                System Commands
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }} elevation={0}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Command</strong></TableCell>
                            <TableCell><strong>Description</strong></TableCell>
                            <TableCell><strong>Examples</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            <TableCell><code>config:cache</code></TableCell>
                            <TableCell>Cache configuration files for performance</TableCell>
                            <TableCell>
                                <code>./mason config:cache</code><br />
                                <code>./mason config:cache --clear</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>optimize</code></TableCell>
                            <TableCell>Optimize application for production</TableCell>
                            <TableCell>
                                <code>./mason optimize</code><br />
                                <code>./mason optimize --force</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>env:decrypt</code></TableCell>
                            <TableCell>Decrypt environment files</TableCell>
                            <TableCell>
                                <code>./mason env:decrypt</code><br />
                                <code>./mason env:decrypt --key=your-key</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>env:encrypt</code></TableCell>
                            <TableCell>Encrypt environment files for secure storage</TableCell>
                            <TableCell>
                                <code>./mason env:encrypt</code><br />
                                <code>./mason env:encrypt --env=production</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>queue:work</code></TableCell>
                            <TableCell>Process background job queues</TableCell>
                            <TableCell>
                                <code>./mason queue:work</code><br />
                                <code>./mason queue:work --queue=emails</code><br />
                                <code>./mason queue:work --timeout=60</code>
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>storage:link</code></TableCell>
                            <TableCell>Create symlink from public/storage to storage/public</TableCell>
                            <TableCell>
                                <code>./mason storage:link</code>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>

            {/* Global Options */}
            <Typography variant="h3" gutterBottom sx={{ mt: 4 }}>
                Global Options
            </Typography>

            <Typography>
                These options work with any command:
            </Typography>

            <List>
                <ListItem>
                    <ListItemText
                        primary="--help (-h)"
                        secondary="Show help information for the command"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="--verbose (-v)"
                        secondary="Increase verbosity of output (-v, -vv, -vvv for more detail)"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="--quiet (-q)"
                        secondary="Suppress all output"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="--version (-V)"
                        secondary="Show BaseAPI version information"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="--env"
                        secondary="Specify environment (local, staging, production)"
                    />
                </ListItem>
                <ListItem>
                    <ListItemText
                        primary="--no-interaction (-n)"
                        secondary="Run command without asking for user input"
                    />
                </ListItem>
            </List>

            <Alert severity="success" sx={{ mt: 4 }}>
                <strong>CLI Best Practices:</strong>
                <br />• Use <code>--help</code> to understand command options
                <br />• Include CLI commands in deployment scripts
                <br />• Use <code>--verbose</code> for debugging
                <br />• Test CLI commands in staging before production
            </Alert>
        </Box>
    );
}
