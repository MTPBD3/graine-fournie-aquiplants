import { lazy, Suspense } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { Box, CircularProgress } from '@mui/material';
import { useAuth } from './context/AuthContext';
import Layout from './components/Layout';
import PrivateRoute from './components/PrivateRoute';
import LoginPage from './pages/LoginPage';

const DashboardAdminPage    = lazy(() => import('./pages/DashboardAdminPage'));
const DashboardEmployePage  = lazy(() => import('./pages/DashboardEmployePage'));
const ArriveesSachetsPage   = lazy(() => import('./pages/ArriveesSachetsPage'));
const GestionStocksPage     = lazy(() => import('./pages/GestionStocksPage'));
const StatistiquesPage      = lazy(() => import('./pages/StatistiquesPage'));
const AlertesPage           = lazy(() => import('./pages/AlertesPage'));
const GestionUtilisateursPage = lazy(() => import('./pages/GestionUtilisateursPage'));
const ParametresPage        = lazy(() => import('./pages/ParametresPage'));

function PageLoader() {
  return (
    <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: '60vh' }}>
      <CircularProgress size={32} sx={{ color: '#1B5E20' }} />
    </Box>
  );
}

function DashboardRedirect() {
  const { user } = useAuth();
  const isAdmin = user?.roles?.includes('ROLE_ADMIN');
  return <Navigate to={isAdmin ? '/dashboard/admin' : '/dashboard/employe'} replace />;
}

export default function App() {
  return (
    <BrowserRouter>
      <Suspense fallback={<PageLoader />}>
        <Routes>
          <Route path="/" element={<LoginPage />} />

          <Route
            element={
              <PrivateRoute>
                <Layout />
              </PrivateRoute>
            }
          >
            <Route path="/dashboard" element={<DashboardRedirect />} />
            <Route
              path="/dashboard/admin"
              element={
                <PrivateRoute requiredRole="ROLE_ADMIN">
                  <DashboardAdminPage />
                </PrivateRoute>
              }
            />
            <Route path="/dashboard/employe" element={<DashboardEmployePage />} />
            <Route path="/arrivees-sachets" element={<ArriveesSachetsPage />} />
            <Route path="/gestion-stocks" element={<GestionStocksPage />} />
            <Route path="/statistiques" element={<StatistiquesPage />} />
            <Route
              path="/alertes"
              element={
                <PrivateRoute requiredRole="ROLE_ADMIN">
                  <AlertesPage />
                </PrivateRoute>
              }
            />
            <Route
              path="/gestion-utilisateurs"
              element={
                <PrivateRoute requiredRole="ROLE_ADMIN">
                  <GestionUtilisateursPage />
                </PrivateRoute>
              }
            />
            <Route path="/parametres" element={<ParametresPage />} />
          </Route>

          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </Suspense>
    </BrowserRouter>
  );
}
