
import { Box, Typography } from '@mui/material';

export default function Overview() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Architecture Overview
      </Typography>
      
      <Typography variant="h5" color="text.secondary" paragraph>
        Understanding the core architecture and design principles of BaseAPI.
      </Typography>

      <Typography paragraph>
        BaseAPI follows a simple, predictable architecture built around these core principles:
      </Typography>

      <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
        Core Principles
      </Typography>

      <Typography paragraph>
        <strong>Convention over Configuration:</strong> BaseAPI uses sensible defaults and naming conventions to minimize configuration while maintaining flexibility.
      </Typography>

      <Typography paragraph>
        <strong>Performance First:</strong> Every component is designed for minimal overhead and maximum speed.
      </Typography>

      <Typography paragraph>
        <strong>Security by Default:</strong> Built-in security features are enabled out of the box.
      </Typography>

      <Typography paragraph>
        <strong>More documentation coming soon...</strong> This page will be expanded with detailed architecture diagrams and explanations.
      </Typography>
    </Box>
  );
}
