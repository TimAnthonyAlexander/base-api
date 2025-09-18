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
  MuiTableCell: {
    styleOverrides: {
      root: {
        fontFamily: [
          'SFMono-Regular',
          'Menlo',
          'Monaco',
          'Consolas',
          '"Liberation Mono"',
          '"Courier New"',
          'monospace',
        ].join(','),
        fontSize: '0.875rem',
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
};
