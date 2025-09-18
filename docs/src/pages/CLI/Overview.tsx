
import { Box, Typography, Alert, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper } from '@mui/material';
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

      <Typography paragraph>
        BaseAPI includes a comprehensive CLI tool that handles development tasks like code generation, 
        database migrations, cache management, and deployment operations. The CLI is built for 
        developer productivity and automation.
      </Typography>

      <Alert severity="info" sx={{ my: 3 }}>
        All CLI commands are accessed through <code>php bin/console</code> and include helpful 
        documentation and examples built-in.
      </Alert>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Getting Started
      </Typography>

      <Typography paragraph>
        Run the console without arguments to see all available commands:
      </Typography>

      <CodeBlock language="bash" code={`# Show all available commands
php bin/console

# Get help for a specific command
php bin/console migrate:generate --help

# Get version information
php bin/console --version`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Development Workflow
      </Typography>

      <Typography paragraph>
        Common CLI commands for daily development:
      </Typography>

      <CodeBlock language="bash" code={`# 1. Start development server
php bin/console serve

# 2. Create a new model
php bin/console make:model Product

# 3. Create a controller
php bin/console make:controller ProductController

# 4. Generate migrations from models
php bin/console migrate:generate

# 5. Apply migrations to database
php bin/console migrate:apply

# 6. Generate API documentation
php bin/console types:generate --openapi --typescript`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Available Commands
      </Typography>

      <TableContainer component={Paper} sx={{ my: 3 }}>
        <Table>
          <TableHead>
            <TableRow>
              <TableCell><strong>Category</strong></TableCell>
              <TableCell><strong>Commands</strong></TableCell>
              <TableCell><strong>Purpose</strong></TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            <TableRow>
              <TableCell><strong>Development</strong></TableCell>
              <TableCell>
                <code>serve</code><br />
                <code>make:controller</code><br />
                <code>make:model</code>
              </TableCell>
              <TableCell>Start dev server, generate code</TableCell>
            </TableRow>
            <TableRow>
              <TableCell><strong>Database</strong></TableCell>
              <TableCell>
                <code>migrate:generate</code><br />
                <code>migrate:apply</code><br />
                <code>migrate:status</code>
              </TableCell>
              <TableCell>Manage database schema</TableCell>
            </TableRow>
            <TableRow>
              <TableCell><strong>Cache</strong></TableCell>
              <TableCell>
                <code>cache:clear</code><br />
                <code>cache:stats</code><br />
                <code>cache:cleanup</code>
              </TableCell>
              <TableCell>Cache management</TableCell>
            </TableRow>
            <TableRow>
              <TableCell><strong>Documentation</strong></TableCell>
              <TableCell>
                <code>types:generate</code>
              </TableCell>
              <TableCell>Generate OpenAPI specs and TypeScript types</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </TableContainer>

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