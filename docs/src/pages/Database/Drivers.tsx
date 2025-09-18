
import { Box, Typography, Alert, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';

export default function Drivers() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Database Drivers
      </Typography>
      <Typography variant="h5" color="text.secondary" paragraph>
        Supported database drivers and configuration options
      </Typography>

      <Typography paragraph>
        BaseAPI supports multiple database drivers out of the box, allowing you to choose the best 
        database solution for your application. Each driver is optimized for performance and includes 
        automatic migration support.
      </Typography>

      <Alert severity="info" sx={{ my: 3 }}>
        BaseAPI automatically detects and configures database drivers based on your .env settings. 
        No additional setup required beyond database credentials.
      </Alert>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Supported Drivers
      </Typography>

      <TableContainer component={Paper} sx={{ my: 3 }}>
        <Table>
          <TableHead>
            <TableRow>
              <TableCell><strong>Driver</strong></TableCell>
              <TableCell><strong>Use Case</strong></TableCell>
              <TableCell><strong>Performance</strong></TableCell>
              <TableCell><strong>Features</strong></TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            <TableRow>
              <TableCell><code>sqlite</code></TableCell>
              <TableCell>Development, small apps</TableCell>
              <TableCell>Fast for small datasets</TableCell>
              <TableCell>Zero configuration, file-based</TableCell>
            </TableRow>
            <TableRow>
              <TableCell><code>mysql</code></TableCell>
              <TableCell>Production, web applications</TableCell>
              <TableCell>Excellent</TableCell>
              <TableCell>ACID, replication, clustering</TableCell>
            </TableRow>
            <TableRow>
              <TableCell><code>postgresql</code></TableCell>
              <TableCell>Complex apps, analytics</TableCell>
              <TableCell>Excellent</TableCell>
              <TableCell>JSON, advanced queries, extensions</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </TableContainer>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        SQLite Driver
      </Typography>

      <Typography paragraph>
        Perfect for development and small applications. Requires no external server setup.
      </Typography>

      <CodeBlock language="bash" code={`# .env configuration
DB_DRIVER=sqlite
DB_NAME=database.sqlite  # File will be created automatically

# Optional: Custom path
DB_NAME=/path/to/database.sqlite`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        MySQL Driver
      </Typography>

      <Typography paragraph>
        Industry standard for web applications with excellent performance and reliability.
      </Typography>

      <CodeBlock language="bash" code={`# .env configuration
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=baseapi
DB_USER=your_username
DB_PASSWORD=your_password`} />

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        PostgreSQL Driver
      </Typography>

      <Typography paragraph>
        Advanced database with powerful features for complex applications and analytics.
      </Typography>

      <CodeBlock language="bash" code={`# .env configuration
DB_DRIVER=postgresql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=baseapi
DB_USER=your_username
DB_PASSWORD=your_password`} />

      <Alert severity="success" sx={{ mt: 4 }}>
        <strong>Best Practices:</strong>
        <br />• Use SQLite for development and testing
        <br />• Choose MySQL for high-traffic web applications
        <br />• Use PostgreSQL for complex data requirements
        <br />• Always use environment variables for credentials
        <br />• Test migrations on staging before production
      </Alert>
    </Box>
  );
}
