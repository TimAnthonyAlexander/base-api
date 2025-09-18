import React from 'react';
import { Box, Typography } from '@mui/material';

export default function Internationalization() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Internationalization
      </Typography>
      <Typography variant="h5" color="text.secondary" paragraph>
        Multi-language support
      </Typography>
      <Typography paragraph>Documentation coming soon...</Typography>
    </Box>
  );
}