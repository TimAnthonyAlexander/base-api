import React from 'react';
import { Box, Typography } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';

const projectStructure = `app/
├── Controllers/     # Request handlers
├── Models/          # Database models
routes/
└── api.php          # Route definitions
config/
├── app.php          # Application configuration
├── i18n.php         # Translation configuration
storage/
├── logs/            # Application logs  
├── cache/           # File-based cache storage
├── ratelimits/      # File based rate limiting storage
└── migrations.json  # Migration state`;

export default function ProjectStructure() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Project Structure
      </Typography>
      
      <Typography variant="h5" color="text.secondary" paragraph>
        Understanding how BaseAPI organizes your code and configuration.
      </Typography>

      <Typography paragraph>
        BaseAPI follows a simple, predictable structure that keeps your code organized and easy to navigate:
      </Typography>

      <CodeBlock
        language="bash"
        code={projectStructure}
        title="BaseAPI Project Structure"
      />

      <Typography paragraph>
        <strong>More documentation coming soon...</strong> This page will be expanded with detailed explanations of each directory and file.
      </Typography>
    </Box>
  );
}
