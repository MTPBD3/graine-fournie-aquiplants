import { createTheme } from '@mui/material/styles';

const theme = createTheme({
  palette: {
    primary: {
      main: '#2E7D32',
      dark: '#1B5E20',
      light: '#388E3C',
    },
    secondary: {
      main: '#D4E157',
    },
    warning: {
      main: '#FF8F00',
    },
    error: {
      main: '#E53935',
    },
    background: {
      default: '#F7FAF3',
      paper: '#FFFFFF',
    },
  },
  typography: {
    fontFamily: '"DM Sans", sans-serif',
    fontSize: 14,
  },
  components: {
    MuiCssBaseline: {
      styleOverrides: {
        body: {
          fontFamily: '"DM Sans", sans-serif',
        },
      },
    },
  },
});

export default theme;
