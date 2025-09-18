import React from 'react';
import { Box, Typography } from '@mui/material';

export default function CommonErrors() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Common Errors
      </Typography>
      <Typography variant="h5" color="text.secondary" paragraph>
        Common issues and solutions
      </Typography>
      <Typography paragraph>Documentation coming soon...</Typography>
    </Box>
  );
}