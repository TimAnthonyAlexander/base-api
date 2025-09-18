import React from 'react';
import {
  Box,
  Paper,
  Typography,
  alpha,
} from '@mui/material';
import {
  Info as InfoIcon,
  Warning as WarningIcon,
  Error as ErrorIcon,
  CheckCircle as SuccessIcon,
  Lightbulb as TipIcon,
  Security as SecurityIcon,
} from '@mui/icons-material';

interface AdmonitionProps {
  type: 'note' | 'warning' | 'danger' | 'tip' | 'important' | 'caution';
  title?: string;
  children: React.ReactNode;
}

const admonitionConfig = {
  note: {
    icon: <InfoIcon />,
    color: '#0ea5e9',
    defaultTitle: 'Note',
  },
  warning: {
    icon: <WarningIcon />,
    color: '#f59e0b',
    defaultTitle: 'Warning',
  },
  danger: {
    icon: <ErrorIcon />,
    color: '#ef4444',
    defaultTitle: 'Danger',
  },
  tip: {
    icon: <TipIcon />,
    color: '#10b981',
    defaultTitle: 'Tip',
  },
  important: {
    icon: <SecurityIcon />,
    color: '#8b5cf6',
    defaultTitle: 'Important',
  },
  caution: {
    icon: <WarningIcon />,
    color: '#f97316',
    defaultTitle: 'Caution',
  },
};

export default function Admonition({ type, title, children }: AdmonitionProps) {
  const config = admonitionConfig[type];
  const displayTitle = title || config.defaultTitle;

  return (
    <Paper
      elevation={0}
      sx={{
        my: 3,
        border: 1,
        borderColor: config.color,
        borderRadius: 2,
        overflow: 'hidden',
      }}
    >
      {/* Header */}
      <Box
        sx={{
          backgroundColor: config.color,
          color: 'white',
          px: 2,
          py: 1.5,
          display: 'flex',
          alignItems: 'center',
          gap: 1,
        }}
      >
        {config.icon}
        <Typography variant="subtitle2" fontWeight={600}>
          {displayTitle}
        </Typography>
      </Box>

      {/* Content */}
      <Box
        sx={{
          p: 2,
          backgroundColor: alpha(config.color, 0.05),
          '& > *:first-of-type': { mt: 0 },
          '& > *:last-child': { mb: 0 },
        }}
      >
        {children}
      </Box>
    </Paper>
  );
}
