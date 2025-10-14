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
            root: ({ theme, ownerState }) => ({
                textTransform: 'none',
                borderRadius: 8,
                fontWeight: 500,
                transition: 'all 0.2s cubic-bezier(0.4, 0, 0.2, 1)',
                position: 'relative',
                overflow: 'hidden',

                // Contained button styles
                ...(ownerState.variant === 'contained' && {
                    background: theme.palette.mode === 'dark'
                        ? `linear-gradient(135deg, ${theme.palette.primary.main} 0%, ${theme.palette.primary.dark} 100%)`
                        : `linear-gradient(135deg, ${theme.palette.primary.main} 0%, ${theme.palette.primary.dark} 100%)`,
                    boxShadow: theme.palette.mode === 'dark'
                        ? '0 2px 8px rgba(33, 150, 243, 0.2)'
                        : '0 2px 8px rgba(33, 150, 243, 0.15)',
                    color: '#ffffff',

                    '&:hover': {
                        background: theme.palette.mode === 'dark'
                            ? `linear-gradient(135deg, ${theme.palette.primary.light} 0%, ${theme.palette.primary.main} 100%)`
                            : `linear-gradient(135deg, ${theme.palette.primary.light} 0%, ${theme.palette.primary.main} 100%)`,
                        boxShadow: theme.palette.mode === 'dark'
                            ? '0 4px 16px rgba(33, 150, 243, 0.3)'
                            : '0 4px 16px rgba(33, 150, 243, 0.25)',
                        transform: 'translateY(-1px)',
                        color: '#ffffff',
                    },

                    '&:focus': {
                        boxShadow: theme.palette.mode === 'dark'
                            ? `0 0 0 3px rgba(33, 150, 243, 0.4), 0 4px 16px rgba(33, 150, 243, 0.3)`
                            : `0 0 0 3px rgba(33, 150, 243, 0.3), 0 4px 16px rgba(33, 150, 243, 0.25)`,
                        color: '#ffffff',
                    },

                    '&:active': {
                        transform: 'translateY(0)',
                        color: '#ffffff',
                    },
                }),

                // Outlined button styles
                ...(ownerState.variant === 'outlined' && {
                    borderColor: theme.palette.primary.main,
                    color: theme.palette.primary.main,
                    background: 'transparent',

                    '&:hover': {
                        background: theme.palette.mode === 'dark'
                            ? `linear-gradient(135deg, rgba(33, 150, 243, 0.08) 0%, rgba(33, 150, 243, 0.12) 100%)`
                            : `linear-gradient(135deg, rgba(33, 150, 243, 0.04) 0%, rgba(33, 150, 243, 0.08) 100%)`,
                        borderColor: theme.palette.primary.light,
                        color: theme.palette.mode === 'dark' ? theme.palette.primary.light : theme.palette.primary.dark,
                        boxShadow: theme.palette.mode === 'dark'
                            ? '0 2px 8px rgba(33, 150, 243, 0.2)'
                            : '0 2px 8px rgba(33, 150, 243, 0.1)',
                        transform: 'translateY(-1px)',
                    },

                    '&:focus': {
                        borderColor: theme.palette.primary.main,
                        boxShadow: theme.palette.mode === 'dark'
                            ? `0 0 0 3px rgba(33, 150, 243, 0.3)`
                            : `0 0 0 3px rgba(33, 150, 243, 0.2)`,
                        color: theme.palette.mode === 'dark' ? theme.palette.primary.light : theme.palette.primary.dark,
                    },

                    '&:active': {
                        transform: 'translateY(0)',
                        background: theme.palette.mode === 'dark'
                            ? 'rgba(33, 150, 243, 0.12)'
                            : 'rgba(33, 150, 243, 0.08)',
                    },
                }),

                // Text button styles
                ...(ownerState.variant === 'text' && {
                    color: theme.palette.primary.main,
                    background: 'transparent',

                    '&:hover': {
                        background: theme.palette.mode === 'dark'
                            ? `linear-gradient(135deg, rgba(33, 150, 243, 0.04) 0%, rgba(33, 150, 243, 0.08) 100%)`
                            : `linear-gradient(135deg, rgba(33, 150, 243, 0.02) 0%, rgba(33, 150, 243, 0.06) 100%)`,
                        color: theme.palette.mode === 'dark' ? theme.palette.primary.light : theme.palette.primary.dark,
                        transform: 'translateY(-1px)',
                    },

                    '&:focus': {
                        background: theme.palette.mode === 'dark'
                            ? 'rgba(33, 150, 243, 0.08)'
                            : 'rgba(33, 150, 243, 0.04)',
                        boxShadow: theme.palette.mode === 'dark'
                            ? `0 0 0 3px rgba(33, 150, 243, 0.2)`
                            : `0 0 0 3px rgba(33, 150, 243, 0.15)`,
                        color: theme.palette.mode === 'dark' ? theme.palette.primary.light : theme.palette.primary.dark,
                    },

                    '&:active': {
                        transform: 'translateY(0)',
                        background: theme.palette.mode === 'dark'
                            ? 'rgba(33, 150, 243, 0.12)'
                            : 'rgba(33, 150, 243, 0.08)',
                    },
                }),
            }),
        },
    },
    MuiChip: {
        styleOverrides: {
            root: ({ theme, ownerState }) => ({
                borderRadius: 16,
                fontWeight: 500,
                transition: 'all 0.2s cubic-bezier(0.4, 0, 0.2, 1)',

                // Outlined chip styles
                ...(ownerState.variant === 'outlined' && {
                    borderColor: theme.palette.primary.main,
                    color: theme.palette.primary.main,
                    backgroundColor: 'transparent',

                    '&:hover': {
                        backgroundColor: theme.palette.mode === 'dark'
                            ? 'rgba(33, 150, 243, 0.08)'
                            : 'rgba(33, 150, 243, 0.04)',
                        borderColor: theme.palette.primary.light,
                        color: theme.palette.mode === 'dark' ? theme.palette.primary.light : theme.palette.primary.dark,
                        transform: 'translateY(-1px)',
                        boxShadow: theme.palette.mode === 'dark'
                            ? '0 2px 8px rgba(33, 150, 243, 0.15)'
                            : '0 2px 8px rgba(33, 150, 243, 0.1)',
                    },
                }),

                // Filled chip styles
                ...(ownerState.variant === 'filled' && {
                    backgroundColor: theme.palette.primary.main,
                    color: '#ffffff',

                    '&:hover': {
                        backgroundColor: theme.palette.primary.dark,
                        color: '#ffffff',
                        transform: 'translateY(-1px)',
                        boxShadow: theme.palette.mode === 'dark'
                            ? '0 2px 8px rgba(33, 150, 243, 0.2)'
                            : '0 2px 8px rgba(33, 150, 243, 0.15)',
                    },
                }),
            }),
        },
    },
    MuiIconButton: {
        styleOverrides: {
            root: ({ theme }) => ({
                borderRadius: 8,
                transition: 'all 0.2s cubic-bezier(0.4, 0, 0.2, 1)',

                '&:hover': {
                    backgroundColor: theme.palette.mode === 'dark'
                        ? 'rgba(255, 255, 255, 0.08)'
                        : 'rgba(0, 0, 0, 0.04)',
                    transform: 'translateY(-1px)',
                    boxShadow: theme.palette.mode === 'dark'
                        ? '0 2px 8px rgba(0, 0, 0, 0.2)'
                        : '0 2px 8px rgba(0, 0, 0, 0.1)',
                },

                '&:focus': {
                    backgroundColor: theme.palette.mode === 'dark'
                        ? 'rgba(255, 255, 255, 0.12)'
                        : 'rgba(0, 0, 0, 0.08)',
                    boxShadow: theme.palette.mode === 'dark'
                        ? '0 0 0 3px rgba(255, 255, 255, 0.2)'
                        : '0 0 0 3px rgba(0, 0, 0, 0.1)',
                },

                '&:active': {
                    transform: 'translateY(0)',
                    backgroundColor: theme.palette.mode === 'dark'
                        ? 'rgba(255, 255, 255, 0.16)'
                        : 'rgba(0, 0, 0, 0.12)',
                },
            }),
        },
    },
    MuiAccordion: {
        styleOverrides: {
            root: ({ theme }) => ({
                border: `1px solid ${theme.palette.divider}`,
                borderRadius: '12px !important',
                boxShadow: 'none',
                background: theme.palette.mode === 'dark'
                    ? 'linear-gradient(135deg, rgba(255, 255, 255, 0.02) 0%, rgba(255, 255, 255, 0.05) 100%)'
                    : 'linear-gradient(135deg, rgba(0, 0, 0, 0.01) 0%, rgba(0, 0, 0, 0.02) 100%)',
                overflow: 'hidden',
                transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
                marginBottom: '12px !important',

                '&:before': {
                    display: 'none',
                },

                '&:hover': {
                    transform: 'translateY(-2px)',
                    boxShadow: theme.palette.mode === 'dark'
                        ? '0 8px 32px rgba(0, 0, 0, 0.3)'
                        : '0 8px 32px rgba(0, 0, 0, 0.08)',
                    borderColor: theme.palette.primary.main + '40',
                },

                '&.Mui-expanded': {
                    margin: '0 0 12px 0 !important',
                    transform: 'translateY(-1px)',
                    borderColor: theme.palette.primary.main + '60',
                    boxShadow: theme.palette.mode === 'dark'
                        ? '0 4px 24px rgba(0, 0, 0, 0.2)'
                        : '0 4px 24px rgba(0, 0, 0, 0.06)',
                },
            }),
        },
    },
    MuiAccordionSummary: {
        styleOverrides: {
            root: ({ theme }) => ({
                padding: '16px 24px',
                minHeight: '72px !important',

                '&.Mui-expanded': {
                    minHeight: '72px !important',
                    borderBottom: `1px solid ${theme.palette.divider}`,
                    background: theme.palette.mode === 'dark'
                        ? 'rgba(255, 255, 255, 0.03)'
                        : 'rgba(0, 0, 0, 0.02)',
                },

                '& .MuiAccordionSummary-expandIconWrapper': {
                    color: theme.palette.primary.main,
                    transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',

                    '&.Mui-expanded': {
                        transform: 'rotate(180deg)',
                        color: theme.palette.primary.dark,
                    },
                },
            }),
            content: {
                margin: '0 !important',

                '&.Mui-expanded': {
                    margin: '0 !important',
                },
            },
        },
    },
    MuiAccordionDetails: {
        styleOverrides: {
            root: ({ theme }) => ({
                padding: '24px',
                background: theme.palette.mode === 'dark'
                    ? 'rgba(255, 255, 255, 0.01)'
                    : 'rgba(0, 0, 0, 0.01)',
            }),
        },
    },
    MuiTextField: {
        styleOverrides: {
            root: ({ theme }) => ({
                '& .MuiOutlinedInput-root': {
                    borderRadius: 12,
                    background: theme.palette.mode === 'dark'
                        ? 'rgba(255, 255, 255, 0.02)'
                        : 'rgba(0, 0, 0, 0.02)',
                    transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',

                    '&:hover': {
                        background: theme.palette.mode === 'dark'
                            ? 'rgba(255, 255, 255, 0.04)'
                            : 'rgba(0, 0, 0, 0.03)',
                    },

                    '&.Mui-focused': {
                        background: theme.palette.mode === 'dark'
                            ? 'rgba(33, 150, 243, 0.05)'
                            : 'rgba(33, 150, 243, 0.02)',
                        boxShadow: theme.palette.mode === 'dark'
                            ? '0 0 0 3px rgba(33, 150, 243, 0.2)'
                            : '0 0 0 3px rgba(33, 150, 243, 0.1)',
                    },
                },
            }),
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
