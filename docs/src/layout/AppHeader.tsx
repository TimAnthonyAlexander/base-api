import {
  AppBar,
  Toolbar,
  Typography,
  IconButton,
  Box,
  Select,
  MenuItem,
  FormControl,
  Link as MuiLink,
} from '@mui/material';
import {
  Menu as MenuIcon,
  DarkMode,
  LightMode,
  GitHub,
  Forum as CommunityIcon,
} from '@mui/icons-material';
import { Link } from 'react-router-dom';
import { VERSIONS, getCurrentVersion } from '../data/versions';
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
  const currentVersion = getCurrentVersion();

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
          }}
        >
          BaseAPI
        </Typography>

        {/* Search */}
        <Box sx={{ flexGrow: 1, maxWidth: 500, mx: 3 }}>
          <Search />
        </Box>

        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, ml: 'auto' }}>
          {/* Version Selector */}
          <FormControl size="small">
            <Select
              value={currentVersion.version}
              variant="outlined"
              sx={{
                minWidth: 80,
                '& .MuiOutlinedInput-notchedOutline': {
                  border: 'none',
                },
                '&:hover .MuiOutlinedInput-notchedOutline': {
                  border: '1px solid',
                  borderColor: 'divider',
                },
              }}
            >
              {VERSIONS.map((version) => (
                <MenuItem key={version.version} value={version.version}>
                  {version.label}
                </MenuItem>
              ))}
            </Select>
          </FormControl>

          {/* Dark Mode Toggle */}
          <IconButton
            color="inherit"
            onClick={onToggleDarkMode}
            aria-label="toggle dark mode"
          >
            {darkMode ? <LightMode /> : <DarkMode />}
          </IconButton>

          {/* Community Link */}
          <IconButton
            color="inherit"
            component={Link}
            to="/community"
            aria-label="Community and support"
          >
            <CommunityIcon />
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
