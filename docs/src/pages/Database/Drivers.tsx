import React from 'react';
import { Box, Typography } from '@mui/material';

export default function Drivers() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Database Drivers
      </Typography>
      <Typography variant="h5" color="text.secondary" paragraph>
        Supported database drivers and configuration.
      </Typography>
      <Typography paragraph>Documentation coming soon...</Typography>
    </Box>
  );
}
