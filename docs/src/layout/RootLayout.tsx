import React, { useState, Suspense } from 'react';
import { Outlet } from 'react-router-dom';
import {
  Box,
  Container,
  CssBaseline,
  ThemeProvider,
  useMediaQuery,
  CircularProgress,
} from '@mui/material';
import { createAppTheme } from '../theme';
import AppHeader from './AppHeader';
import AppDrawer from './AppDrawer';

const DRAWER_WIDTH = 280;

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
            width: { lg: `calc(100% - ${DRAWER_WIDTH}px)` },
            ml: { lg: 0 },
          }}
        >
          {/* Account for header height */}
          <Box sx={{ height: 64 }} />
          
          <Container 
            maxWidth="lg" 
            sx={{ 
              px: 3, 
              py: 4,
              minHeight: 'calc(100vh - 64px)',
              display: 'flex',
              flexDirection: 'column',
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
          </Container>
        </Box>
      </Box>
    </ThemeProvider>
  );
}
