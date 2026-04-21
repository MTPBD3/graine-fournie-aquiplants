import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from './context/AuthContext';
import Layout from './components/Layout';
import PrivateRoute from './components/PrivateRoute';

import LoginPage from './pages/LoginPage';
import DashboardAdminPage from './pages/DashboardAdminPage';
import DashboardEmployePage from './pages/DashboardEmployePage';
import ArriveesSachetsPage from './pages/ArriveesSachetsPage';
import GestionStocksPage from './pages/GestionStocksPage';
import StatistiquesPage from './pages/StatistiquesPage';
import AlertesPage from './pages/AlertesPage';
import GestionUtilisateursPage from './pages/GestionUtilisateursPage';
import ParametresPage from './pages/ParametresPage';

function DashboardRedirect() {
  const { user } = useAuth();
  const isAdmin = user?.roles?.includes('ROLE_ADMIN');
  return <Navigate to={isAdmin ? '/dashboard/admin' : '/dashboard/employe'} replace />;
}

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        {/* Page publique */}
        <Route path="/" element={<LoginPage />} />

        {/* Routes protégées */}
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

        {/* Fallback */}
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  );
}
