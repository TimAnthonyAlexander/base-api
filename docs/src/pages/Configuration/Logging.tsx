import React from 'react';
import { Box, Typography } from '@mui/material';

export default function Logging() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Logging
      </Typography>
      <Typography variant="h5" color="text.secondary" paragraph>
        Application logging configuration
      </Typography>
      <Typography paragraph>Documentation coming soon...</Typography>
    </Box>
  );
}