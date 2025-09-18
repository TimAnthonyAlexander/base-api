import React from 'react';
import { Box, Typography } from '@mui/material';

export default function Seeding() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Database Seeding
      </Typography>
      <Typography variant="h5" color="text.secondary" paragraph>
        Seeding your database with test data.
      </Typography>
      <Typography paragraph>Documentation coming soon...</Typography>
    </Box>
  );
}
