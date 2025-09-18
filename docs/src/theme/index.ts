import { createTheme, ThemeOptions } from '@mui/material/styles';
import { lightPalette, darkPalette } from './palette';
import { typography } from './typography';
import { components } from './components';

export const createAppTheme = (mode: 'light' | 'dark') => {
  const palette = mode === 'light' ? lightPalette : darkPalette;
  
  const themeOptions: ThemeOptions = {
    palette,
    typography,
    components,
    shape: {
      borderRadius: 8,
    },
    spacing: 8,
  };

  return createTheme(themeOptions);
};

export type AppTheme = ReturnType<typeof createAppTheme>;
