
import { Box, Typography } from '@mui/material';

export default function Container() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Container
      </Typography>
      <Typography variant="h5" color="text.secondary" paragraph>
        Dependency injection container
      </Typography>
      <Typography paragraph>Documentation coming soon...</Typography>
    </Box>
  );
}