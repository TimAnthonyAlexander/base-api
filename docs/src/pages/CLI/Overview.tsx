import React from 'react';
import { Box, Typography } from '@mui/material';

export default function CLIOverview() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        CLI Overview
      </Typography>
      <Typography variant="h5" color="text.secondary" paragraph>
        Command line interface overview
      </Typography>
      <Typography paragraph>Documentation coming soon...</Typography>
    </Box>
  );
}