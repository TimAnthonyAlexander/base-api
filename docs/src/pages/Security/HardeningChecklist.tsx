import React from 'react';
import { Box, Typography } from '@mui/material';

export default function HardeningChecklist() {
  return (
    <Box>
      <Typography variant="h1" gutterBottom>
        Hardening Checklist
      </Typography>
      <Typography variant="h5" color="text.secondary" paragraph>
        Security hardening guide
      </Typography>
      <Typography paragraph>Documentation coming soon...</Typography>
    </Box>
  );
}