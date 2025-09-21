import type { Components, Theme } from '@mui/material/styles';

export const components: Components<Theme> = {
  MuiLink: {
    styleOverrides: {
      root: {
        textDecoration: 'none',
        '&:hover': {
          textDecoration: 'underline',
        },
      },
    },
  },
  MuiListItemButton: {
    styleOverrides: {
      root: {
        paddingTop: 6,
        paddingBottom: 6,
        borderRadius: 8,
        '&.Mui-selected': {
          backgroundColor: 'rgba(25, 118, 210, 0.08)',
          '&:hover': {
            backgroundColor: 'rgba(25, 118, 210, 0.12)',
          },
        },
        '&:hover': {
          borderRadius: 8,
        },
      },
    },
  },
  MuiButton: {
    styleOverrides: {
      root: {
        textTransform: 'none',
        borderRadius: 8,
        fontWeight: 500,
      },
    },
  },
  MuiPaper: {
    styleOverrides: {
      root: {
        backgroundImage: 'none',
      },
    },
  },
  MuiAppBar: {
    styleOverrides: {
      root: {
        boxShadow: 'none',
        borderBottom: '1px solid',
        borderBottomColor: 'rgba(0, 0, 0, 0.12)',
      },
    },
  },
  MuiDrawer: {
    styleOverrides: {
      paper: {
        borderRight: '1px solid',
        borderRightColor: 'rgba(0, 0, 0, 0.12)',
      },
    },
  },
  MuiTable: {
    styleOverrides: {
      root: {
        borderCollapse: 'collapse',
      },
    },
  },
  MuiTableHead: {
    styleOverrides: {
      root: ({ theme }) => ({
        '& .MuiTableRow-root': {
          backgroundColor: theme.palette.mode === 'dark' 
            ? 'rgba(255, 255, 255, 0.05)'
            : theme.palette.grey[50],
        },
      }),
    },
  },
  MuiTableRow: {
    styleOverrides: {
      root: ({ theme }) => ({
        '&:nth-of-type(even)': {
          backgroundColor: theme.palette.mode === 'dark'
            ? 'rgba(255, 255, 255, 0.02)'
            : theme.palette.action.hover,
        },
        '&:hover': {
          backgroundColor: theme.palette.mode === 'dark'
            ? 'rgba(255, 255, 255, 0.08) !important'
            : 'rgba(0, 0, 0, 0.04) !important',
        },
      }),
    },
  },
  MuiTableCell: {
    styleOverrides: {
      root: ({ theme }) => ({
        fontFamily: [
          'SFMono-Regular',
          'Menlo',
          'Monaco',
          'Consolas',
          '"Liberation Mono"',
          '"Courier New"',
          'monospace',
        ].join(','),
        borderBottom: `1px solid ${theme.palette.divider}`,
        fontSize: '0.875rem',
      }),
      head: ({ theme }) => ({
        fontWeight: 600,
        color: theme.palette.text.primary,
        backgroundColor: 'transparent',
      }),
    },
  },
  MuiCssBaseline: {
    styleOverrides: (theme) => ({
      code: {
        fontFamily: [
          'SFMono-Regular',
          'Menlo',
          'Monaco',
          'Consolas',
          '"Liberation Mono"',
          '"Courier New"',
          'monospace',
        ].join(','),
        fontSize: '0.875em',
        padding: '0.2em 0.4em',
        borderRadius: '3px',
        backgroundColor: theme.palette.mode === 'dark' 
          ? '#2d3748 !important' 
          : 'rgba(0, 0, 0, 0.04) !important',
        color: theme.palette.mode === 'dark' 
          ? '#ffffff !important' 
          : '#d63384 !important',
      },
    }),
  },
};
