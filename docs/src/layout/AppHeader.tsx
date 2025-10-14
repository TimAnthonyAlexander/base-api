import {
    AppBar,
    Toolbar,
    Typography,
    IconButton,
    Box,
    Link as MuiLink,
} from '@mui/material';
import {
    Menu as MenuIcon,
    DarkMode,
    LightMode,
    GitHub,
} from '@mui/icons-material';
import { Link } from 'react-router-dom';
import Search from './Search';

interface AppHeaderProps {
    drawerWidth: number;
    onMenuClick: () => void;
    darkMode: boolean;
    onToggleDarkMode: () => void;
    showMenuButton: boolean;
}

export default function AppHeader({
    drawerWidth,
    onMenuClick,
    darkMode,
    onToggleDarkMode,
    showMenuButton,
}: AppHeaderProps) {
    const isMobile = window.innerWidth < 1024;

    return (
        <AppBar
            position="fixed"
            sx={{
                width: { lg: `calc(100% - ${drawerWidth}px)` },
                ml: { lg: `${drawerWidth}px` },
                backgroundColor: 'background.paper',
                color: 'text.primary',
                borderBottom: 1,
                borderColor: 'divider',
            }}
            elevation={0}
        >
            <Toolbar>
                {showMenuButton && (
                    <IconButton
                        color="inherit"
                        aria-label="open drawer"
                        edge="start"
                        onClick={onMenuClick}
                        sx={{ mr: 2 }}
                    >
                        <MenuIcon />
                    </IconButton>
                )}

                <Typography
                    variant="h6"
                    noWrap
                    component={Link}
                    to="/"
                    sx={{
                        flexGrow: 0,
                        mr: 3,
                        textDecoration: 'none',
                        color: 'inherit',
                        fontWeight: 700,
                        justifyContent: 'center',
                        display: 'flex',
                        alignItems: 'center',
                    }}
                >
                    {!isMobile &&
                        <img
                            src="/appstore.png"
                            alt="Base API Logo"
                            style={{
                                height: 40,
                                verticalAlign: 'middle',
                                marginRight: 8,
                                borderRadius: 8,
                            }}
                        />
                    }
                    BaseAPI
                </Typography>

                {/* Search */}
                <Box sx={{ flexGrow: 1, maxWidth: 500, mx: 3 }}>
                    <Search />
                </Box>

                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, ml: 'auto' }}>

                    {/* Dark Mode Toggle */}
                    <IconButton
                        color="inherit"
                        onClick={onToggleDarkMode}
                        aria-label="toggle dark mode"
                    >
                        {darkMode ? <LightMode /> : <DarkMode />}
                    </IconButton>

                    {/* GitHub Link */}
                    <IconButton
                        color="inherit"
                        component={MuiLink}
                        href="https://github.com/timanthonyalexander/base-api"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="GitHub repository"
                    >
                        <GitHub />
                    </IconButton>
                </Box>
            </Toolbar>
        </AppBar>
    );
}
