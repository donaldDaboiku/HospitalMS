import { createTheme } from '@mui/material/styles'

export const theme = createTheme({
  palette: {
    mode: 'light',
    primary: {
      main: '#0F4C5C',
      dark: '#0A3340',
      light: '#1B7A6E',
      contrastText: '#ffffff',
    },
    secondary: {
      main: '#1B7A6E',
    },
    background: {
      default: '#F4F7F8',
      paper: '#FFFFFF',
    },
    error: { main: '#B42318' },
    warning: { main: '#B54708' },
    success: { main: '#027A48' },
    text: {
      primary: '#101828',
      secondary: '#475467',
    },
  },
  typography: {
    fontFamily: '"Source Sans 3", "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
    h4: { fontWeight: 700 },
    h5: { fontWeight: 700 },
    h6: { fontWeight: 650 },
    button: { textTransform: 'none', fontWeight: 600 },
  },
  shape: { borderRadius: 10 },
  components: {
    MuiAppBar: {
      styleOverrides: {
        root: { boxShadow: 'none', borderBottom: '1px solid #EAECF0' },
      },
    },
    MuiDrawer: {
      styleOverrides: {
        paper: { borderRight: '1px solid #EAECF0' },
      },
    },
  },
})
