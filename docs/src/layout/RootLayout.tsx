import { useState, Suspense, useEffect } from 'react';
import { Outlet } from 'react-router-dom';
import {
    Box,
    CssBaseline,
    ThemeProvider,
    useMediaQuery,
    useTheme,
    CircularProgress,
} from '@mui/material';
import { createAppTheme } from '../theme';
import AppHeader from './AppHeader';
import AppDrawer from './AppDrawer';
import ScrollToTop from '../components/ScrollToTop';

const DRAWER_WIDTH = 280;

function useSyncThemeColorWithStatusBar() {
  const theme = useTheme();

  useEffect(() => {
    const color =
      theme.palette.mode === 'dark'
        ? theme.palette.background.default || '#000000'
        : theme.palette.background.default || '#ffffff';

    let tag = document.querySelector<HTMLMetaElement>('meta[name="theme-color"]');
    if (!tag) {
      tag = document.createElement('meta');
      tag.name = 'theme-color';
      document.head.appendChild(tag);
    }
    tag.content = color;
    document.body.style.backgroundColor = color; // avoids flash behind the bar
  }, [theme.palette.mode, theme.palette.background.default]);
}

function ThemeColorSyncProvider() {
  useSyncThemeColorWithStatusBar();
  return null;
}

export default function RootLayout() {
    const [darkMode, setDarkMode] = useState(() => {
        const saved = localStorage.getItem('darkMode');
        return saved ? JSON.parse(saved) : false;
    });

    const [mobileOpen, setMobileOpen] = useState(false);
    const theme = createAppTheme(darkMode ? 'dark' : 'light');
    const isMobile = useMediaQuery(theme.breakpoints.down('lg'));

    const toggleDarkMode = () => {
        const newMode = !darkMode;
        setDarkMode(newMode);
        localStorage.setItem('darkMode', JSON.stringify(newMode));
    };

    const handleDrawerToggle = () => {
        setMobileOpen(!mobileOpen);
    };

    return (
        <ThemeProvider theme={theme}>
            <CssBaseline />
            <ScrollToTop />
            <ThemeColorSyncProvider />
            <Box sx={{ display: 'flex', minHeight: '100vh' }}>
                <AppHeader
                    drawerWidth={DRAWER_WIDTH}
                    onMenuClick={handleDrawerToggle}
                    darkMode={darkMode}
                    onToggleDarkMode={toggleDarkMode}
                    showMenuButton={isMobile}
                />

                <AppDrawer
                    width={DRAWER_WIDTH}
                    mobileOpen={mobileOpen}
                    onMobileClose={handleDrawerToggle}
                    isMobile={isMobile}
                />

                <Box
                    component="main"
                    sx={{
                        flexGrow: 1,
                        width: {
                            xs: '100%',
                            lg: `calc(100% - ${DRAWER_WIDTH}px)`
                        },
                        ml: { lg: 0 },
                        minWidth: 0, // Prevent overflow issues
                    }}
                >
                    {/* Account for header height */}
                    <Box sx={{ height: 64 }} />

                    <Box
                        sx={{
                            px: { xs: 1.5, sm: 2.5, lg: 3, xl: 4 },
                            py: 4,
                            minHeight: 'calc(100vh - 64px)',
                            display: 'flex',
                            flexDirection: 'column',
                            width: '100%',
                            maxWidth: '100%',
                        }}
                    >
                        <Suspense
                            fallback={
                                <Box
                                    display="flex"
                                    justifyContent="center"
                                    alignItems="center"
                                    minHeight="200px"
                                >
                                    <CircularProgress />
                                </Box>
                            }
                        >
                            <Outlet />
                        </Suspense>
                    </Box>
                </Box>
            </Box>
        </ThemeProvider>
    );
}
