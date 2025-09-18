import { Chip } from '@mui/material';

interface ApiMethodProps {
  method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
  size?: 'small' | 'medium';
}

const methodColors = {
  GET: {
    backgroundColor: '#10b981',
    color: 'white',
  },
  POST: {
    backgroundColor: '#3b82f6',
    color: 'white',
  },
  PUT: {
    backgroundColor: '#f59e0b',
    color: 'white',
  },
  PATCH: {
    backgroundColor: '#8b5cf6',
    color: 'white',
  },
  DELETE: {
    backgroundColor: '#ef4444',
    color: 'white',
  },
};

export default function ApiMethod({ method, size = 'small' }: ApiMethodProps) {
  const colors = methodColors[method];

  return (
    <Chip
      label={method}
      size={size}
      sx={{
        ...colors,
        fontFamily: 'monospace',
        fontWeight: 600,
        fontSize: size === 'small' ? '0.75rem' : '0.875rem',
        minWidth: size === 'small' ? 60 : 80,
        '&:hover': {
          ...colors,
          opacity: 0.8,
        },
      }}
    />
  );
}
