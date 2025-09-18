import React from 'react';
import {
  Alert,
  AlertTitle,
  Box,
} from '@mui/material';
import {
  Info as InfoIcon,
  Warning as WarningIcon,
  Error as ErrorIcon,
  CheckCircle as SuccessIcon,
  Lightbulb as TipIcon,
} from '@mui/icons-material';

interface CalloutProps {
  type: 'info' | 'warning' | 'error' | 'success' | 'tip';
  title?: string;
  children: React.ReactNode;
}

const calloutConfig = {
  info: {
    severity: 'info' as const,
    icon: <InfoIcon />,
    defaultTitle: 'Info',
  },
  warning: {
    severity: 'warning' as const,
    icon: <WarningIcon />,
    defaultTitle: 'Warning',
  },
  error: {
    severity: 'error' as const,
    icon: <ErrorIcon />,
    defaultTitle: 'Error',
  },
  success: {
    severity: 'success' as const,
    icon: <SuccessIcon />,
    defaultTitle: 'Success',
  },
  tip: {
    severity: 'info' as const,
    icon: <TipIcon />,
    defaultTitle: 'Tip',
  },
};

export default function Callout({ type, title, children }: CalloutProps) {
  const config = calloutConfig[type];
  const displayTitle = title || config.defaultTitle;

  return (
    <Alert
      severity={config.severity}
      icon={config.icon}
      sx={{
        my: 2,
        borderRadius: 2,
        '& .MuiAlert-message': {
          width: '100%',
        },
        ...(type === 'tip' && {
          borderLeft: 4,
          borderLeftColor: 'warning.main',
          backgroundColor: 'warning.50',
          ...(theme => theme.palette.mode === 'dark' && {
            backgroundColor: 'rgba(255, 193, 7, 0.1)',
          }),
        }),
      }}
    >
      <AlertTitle sx={{ fontWeight: 600 }}>
        {displayTitle}
      </AlertTitle>
      <Box sx={{ '& > *:last-child': { mb: 0 } }}>
        {children}
      </Box>
    </Alert>
  );
}
