import { Navigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

function isTokenExpired(token) {
  try {
    const payload = JSON.parse(atob(token.split('.')[1]));
    return payload.exp * 1000 < Date.now();
  } catch {
    return true;
  }
}

export default function PrivateRoute({ children, requiredRole }) {
  const { token, user } = useAuth();

  if (!token || isTokenExpired(token)) {
    return <Navigate to="/" replace />;
  }

  if (requiredRole && !user?.roles?.includes(requiredRole)) {
    return <Navigate to="/dashboard" replace />;
  }

  return children;
}
