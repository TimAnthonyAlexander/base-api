import React from 'react';
import {
  Box,
  Drawer,
  Toolbar,
} from '@mui/material';
import SidebarTree from './SidebarTree';

interface AppDrawerProps {
  width: number;
  mobileOpen: boolean;
  onMobileClose: () => void;
  isMobile: boolean;
}

export default function AppDrawer({
  width,
  mobileOpen,
  onMobileClose,
  isMobile,
}: AppDrawerProps) {
  const drawer = (
    <Box sx={{ height: '100%', display: 'flex', flexDirection: 'column' }}>
      <Toolbar /> {/* Account for header height */}
      <Box sx={{ flex: 1, overflow: 'auto', p: 2 }}>
        <SidebarTree onItemClick={isMobile ? onMobileClose : undefined} />
      </Box>
    </Box>
  );

  return (
    <Box
      component="nav"
      sx={{ width: { lg: width }, flexShrink: { lg: 0 } }}
    >
      {isMobile ? (
        <Drawer
          variant="temporary"
          open={mobileOpen}
          onClose={onMobileClose}
          ModalProps={{
            keepMounted: true, // Better open performance on mobile.
          }}
          sx={{
            '& .MuiDrawer-paper': {
              boxSizing: 'border-box',
              width: width,
              borderRight: 1,
              borderColor: 'divider',
            },
          }}
        >
          {drawer}
        </Drawer>
      ) : (
        <Drawer
          variant="permanent"
          sx={{
            '& .MuiDrawer-paper': {
              boxSizing: 'border-box',
              width: width,
              borderRight: 1,
              borderColor: 'divider',
            },
          }}
          open
        >
          {drawer}
        </Drawer>
      )}
    </Box>
  );
}
