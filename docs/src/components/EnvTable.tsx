import React from 'react';
import {
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Typography,
  Chip,
  Box,
} from '@mui/material';

interface EnvVar {
  key: string;
  default?: string;
  description: string;
  required?: boolean;
  type?: 'string' | 'number' | 'boolean' | 'enum';
  options?: string[];
}

interface EnvTableProps {
  envVars: EnvVar[];
  title?: string;
}

export default function EnvTable({ envVars, title }: EnvTableProps) {
  return (
    <Box sx={{ my: 3 }}>
      {title && (
        <Typography variant="h6" gutterBottom>
          {title}
        </Typography>
      )}
      
      <TableContainer 
        component={Paper} 
        elevation={0}
        sx={{ 
          border: 1, 
          borderColor: 'divider',
          borderRadius: 2,
        }}
      >
        <Table>
          <TableHead>
            <TableRow sx={{ backgroundColor: 'grey.50' }}>
              <TableCell sx={{ fontWeight: 600 }}>Variable</TableCell>
              <TableCell sx={{ fontWeight: 600 }}>Default</TableCell>
              <TableCell sx={{ fontWeight: 600 }}>Description</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {envVars.map((envVar) => (
              <TableRow 
                key={envVar.key}
                sx={{
                  '&:nth-of-type(odd)': {
                    backgroundColor: 'action.hover',
                  },
                }}
              >
                <TableCell>
                  <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                    <Typography
                      component="code"
                      sx={{
                        fontFamily: 'monospace',
                        fontSize: '0.875rem',
                        fontWeight: 600,
                        color: 'primary.main',
                      }}
                    >
                      {envVar.key}
                    </Typography>
                    {envVar.required && (
                      <Chip
                        label="Required"
                        size="small"
                        color="error"
                        variant="outlined"
                        sx={{ fontSize: '0.7rem', height: 20 }}
                      />
                    )}
                  </Box>
                </TableCell>
                
                <TableCell>
                  {envVar.default ? (
                    <Typography
                      component="code"
                      sx={{
                        fontFamily: 'monospace',
                        fontSize: '0.875rem',
                        backgroundColor: 'grey.100',
                        px: 1,
                        py: 0.25,
                        borderRadius: 1,
                        color: 'text.secondary',
                      }}
                    >
                      {envVar.default}
                    </Typography>
                  ) : (
                    <Typography variant="body2" color="text.disabled">
                      -
                    </Typography>
                  )}
                </TableCell>
                
                <TableCell>
                  <Typography variant="body2">
                    {envVar.description}
                  </Typography>
                  {envVar.options && (
                    <Box sx={{ mt: 1 }}>
                      <Typography variant="caption" color="text.secondary">
                        Options: 
                      </Typography>
                      {envVar.options.map((option, index) => (
                        <Typography
                          key={option}
                          component="code"
                          sx={{
                            fontFamily: 'monospace',
                            fontSize: '0.75rem',
                            backgroundColor: 'grey.100',
                            px: 0.5,
                            py: 0.25,
                            borderRadius: 0.5,
                            mx: 0.5,
                          }}
                        >
                          {option}{index < envVar.options!.length - 1 && ','}
                        </Typography>
                      ))}
                    </Box>
                  )}
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </TableContainer>
    </Box>
  );
}
