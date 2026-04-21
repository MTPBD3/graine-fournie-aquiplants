import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { createTheme, ThemeProvider, CssBaseline } from '@mui/material';
import { AuthProvider, useAuth } from './context/AuthContext';
import LoginPage from './pages/LoginPage';
import DashboardAdminPage from './pages/DashboardAdminPage';
import DashboardEmployePage from './pages/DashboardEmployePage';
import ArriveesSachetsPage from './pages/ArriveesSachetsPage';
import GestionStocksPage from './pages/GestionStocksPage';
import AlertesPage from './pages/AlertesPage';
import StatistiquesPage from './pages/StatistiquesPage';
import GestionUtilisateursPage from './pages/GestionUtilisateursPage';
import ParametresPage from './pages/ParametresPage';
import Layout from './components/Layout';

const theme = createTheme({
  palette: {
    primary:    { main: '#1B5E20' },
    secondary:  { main: '#D4E157' },
    background: { default: '#F7FAF3' },
    warning:    { main: '#FF8F00' },
    error:      { main: '#E53935' },
  },
  typography: {
    fontFamily: '"DM Sans", sans-serif',
  },
});

function RequireAuth({ children }) {
  const { token } = useAuth();
  return token ? children : <Navigate to="/login" replace />;
}

function RequireAdmin({ children }) {
  const { user } = useAuth();
  return user?.role === 'admin' ? children : <Navigate to="/" replace />;
}

function DashboardRedirect() {
  const { user } = useAuth();
  return user?.role === 'admin'
    ? <Navigate to="/dashboard-admin" replace />
    : <Navigate to="/dashboard" replace />;
}

export default function App() {
  return (
    <ThemeProvider theme={theme}>
      <CssBaseline />
      <AuthProvider>
        <BrowserRouter>
          <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route path="/" element={<RequireAuth><Layout /></RequireAuth>}>
              <Route index element={<DashboardRedirect />} />
              <Route path="dashboard-admin" element={<RequireAdmin><DashboardAdminPage /></RequireAdmin>} />
              <Route path="dashboard" element={<DashboardEmployePage />} />
              <Route path="arrivees" element={<ArriveesSachetsPage />} />
              <Route path="stocks" element={<GestionStocksPage />} />
              <Route path="alertes" element={<AlertesPage />} />
              <Route path="statistiques" element={<RequireAdmin><StatistiquesPage /></RequireAdmin>} />
              <Route path="utilisateurs" element={<RequireAdmin><GestionUtilisateursPage /></RequireAdmin>} />
              <Route path="parametres" element={<ParametresPage />} />
            </Route>
          </Routes>
        </BrowserRouter>
      </AuthProvider>
    </ThemeProvider>
  );
}
