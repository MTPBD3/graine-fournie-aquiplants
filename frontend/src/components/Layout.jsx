import { useState, createContext } from 'react';
import { useNavigate, useLocation, Outlet } from 'react-router-dom';

export const DrawerOpenContext = createContext(() => {});
import {
  Box, Drawer, List, ListItemButton, ListItemIcon, ListItemText,
  AppBar, Toolbar, Typography, IconButton, Avatar, Divider,
  Tooltip, Chip,
  useMediaQuery, useTheme,
} from '@mui/material';
import DashboardIcon from '@mui/icons-material/Dashboard';
import Inventory2Icon from '@mui/icons-material/Inventory2';
import WarehouseIcon from '@mui/icons-material/Warehouse';
import BarChartIcon from '@mui/icons-material/BarChart';
import SettingsIcon from '@mui/icons-material/Settings';
import NotificationsActiveIcon from '@mui/icons-material/NotificationsActive';
import PeopleIcon from '@mui/icons-material/People';
import LogoutIcon from '@mui/icons-material/Logout';
import MenuIcon from '@mui/icons-material/Menu';
import { useAuth } from '../context/AuthContext';
import { useApi } from '../hooks/useApi';
import MeteoWidget from './MeteoWidget';
import TopbarMeteoWidget from './TopbarMeteoWidget';

const SIDEBAR_WIDTH  = 248;
const SIDEBAR_BG     = '#1B5E20';
const SIDEBAR_TEXT   = '#FFFFFF';
const SIDEBAR_ACTIVE = 'rgba(212,225,87,0.18)';

const MENU_SECTIONS = [
  {
    label: 'PRINCIPALE',
    items: [
      { label: 'Tableau de bord',    icon: <DashboardIcon />,          path: '/dashboard',            roles: ['ROLE_EMPLOYE', 'ROLE_ADMIN'] },
      { label: "Arrivée d'un sachet", icon: <Inventory2Icon />,         path: '/arrivees-sachets',     roles: ['ROLE_EMPLOYE', 'ROLE_ADMIN'], badgeKey: 'enAttente' },
      { label: 'Gestion des stocks', icon: <WarehouseIcon />,           path: '/gestion-stocks',       roles: ['ROLE_EMPLOYE', 'ROLE_ADMIN'], badgeKey: 'enStock' },
    ],
  },
  {
    label: 'ANALYSE',
    items: [
      { label: 'Statistiques',       icon: <BarChartIcon />,            path: '/statistiques',         roles: ['ROLE_EMPLOYE', 'ROLE_ADMIN'] },
      { label: 'Alertes stock',      icon: <NotificationsActiveIcon />, path: '/alertes',              roles: ['ROLE_ADMIN'], badgeKey: 'alertes' },
    ],
  },
  {
    label: 'ADMINISTRATION',
    items: [
      { label: 'Utilisateurs',       icon: <PeopleIcon />,              path: '/gestion-utilisateurs', roles: ['ROLE_ADMIN'] },
      { label: 'Paramètres',         icon: <SettingsIcon />,            path: '/parametres',           roles: ['ROLE_EMPLOYE', 'ROLE_ADMIN'] },
    ],
  },
];

function SidebarContent({ onNavigate }) {
  const { user, logout } = useAuth();
  const navigate  = useNavigate();
  const location  = useLocation();

  const { data: stats  } = useApi('/api/statistiques');
  const { data: alertes } = useApi('/api/alertes');

  const userRoles = user?.roles ?? [];
  const isAdmin   = userRoles.includes('ROLE_ADMIN');

  const badges = {
    enAttente: stats?.parStatut?.a_traiter ?? 0,
    enStock:   stats?.parStatut?.range     ?? 0,
    alertes:   Array.isArray(alertes) ? alertes.length : 0,
  };

  const displayName = user?.prenom
    ? `${user.prenom}${user.nom ? ' ' + user.nom[0].toUpperCase() + '.' : ''}`
    : (user?.email?.split('@')[0] ?? 'Utilisateur');

  const initials = displayName
    .split(' ')
    .map(w => w[0] ?? '')
    .join('')
    .slice(0, 2)
    .toUpperCase();

  const roleLabel = isAdmin ? 'Administrateur' : 'Employé';

  const handleNav = (path) => { navigate(path); onNavigate?.(); };
  const handleLogout = () => { logout(); navigate('/'); onNavigate?.(); };

  return (
    <Box sx={{ display: 'flex', flexDirection: 'column', height: '100%', bgcolor: SIDEBAR_BG, color: SIDEBAR_TEXT }}>

      {/* Logo */}
      <Box sx={{ px: 2.5, pt: 3, pb: 2 }}>
        <Typography sx={{ fontWeight: 800, color: '#D4E157', lineHeight: 1.1, fontSize: '1rem', letterSpacing: '0.01em' }}>
          AQUIPLANTS
        </Typography>
        <Typography sx={{ color: 'rgba(255,255,255,0.55)', fontSize: '0.72rem', letterSpacing: '0.08em', mt: 0.25 }}>
          GRAINE FOURNIE
        </Typography>
      </Box>

      <Divider sx={{ borderColor: 'rgba(255,255,255,0.12)', mx: 2, mb: 1 }} />

      {/* Sections de navigation */}
      <Box sx={{ flex: 1, overflowY: 'auto', px: 1 }}>
        {MENU_SECTIONS.map((section, si) => {
          const visibleItems = section.items.filter(item =>
            item.roles.some(r => userRoles.includes(r))
          );
          if (visibleItems.length === 0) return null;

          return (
            <Box key={section.label} sx={{ mt: si === 0 ? 0.5 : 2 }}>
              <Typography sx={{
                px: 1.5, mb: 0.5,
                fontSize: '0.65rem', fontWeight: 700,
                color: 'rgba(255,255,255,0.35)',
                letterSpacing: '0.1em',
              }}>
                {section.label}
              </Typography>
              <List disablePadding>
                {visibleItems.map(item => {
                  const active     = location.pathname.startsWith(item.path);
                  const badgeCount = item.badgeKey ? (badges[item.badgeKey] ?? 0) : 0;
                  return (
                    <ListItemButton
                      key={item.path}
                      onClick={() => handleNav(item.path)}
                      sx={{
                        borderRadius: 2, mb: 0.25, px: 1.5, py: 0.75,
                        bgcolor: active ? SIDEBAR_ACTIVE : 'transparent',
                        '&:hover': { bgcolor: SIDEBAR_ACTIVE },
                        color: SIDEBAR_TEXT,
                      }}
                    >
                      <ListItemIcon sx={{ color: active ? '#D4E157' : 'rgba(255,255,255,0.65)', minWidth: 36 }}>
                        {item.icon}
                      </ListItemIcon>
                      <ListItemText
                        primary={item.label}
                        slotProps={{ primary: { sx: { fontSize: '0.85rem', fontWeight: active ? 600 : 400 } } }}
                      />
                      {badgeCount > 0 && (
                        <Chip
                          label={badgeCount}
                          size="small"
                          sx={{
                            height: 18, minWidth: 18,
                            fontSize: '0.65rem', fontWeight: 700,
                            bgcolor: active ? '#D4E157' : 'rgba(212,225,87,0.25)',
                            color: active ? '#1B5E20' : '#D4E157',
                            '& .MuiChip-label': { px: 0.5 },
                          }}
                        />
                      )}
                    </ListItemButton>
                  );
                })}
              </List>
            </Box>
          );
        })}
      </Box>

      {/* Widget météo */}
      <Box sx={{ px: 1.5, pb: 0.5 }}>
        <MeteoWidget />
      </Box>

      {/* Avatar + nom + rôle + déconnexion */}
      <Divider sx={{ borderColor: 'rgba(255,255,255,0.12)', mx: 2, mt: 1 }} />
      <Box sx={{ px: 1.5, py: 1.75 }}>
        <Box sx={{
          display: 'flex', alignItems: 'center', gap: 1.5,
          bgcolor: 'rgba(255,255,255,0.08)', borderRadius: 2, px: 1.5, py: 1,
        }}>
          <Avatar sx={{
            width: 34, height: 34,
            bgcolor: '#D4E157', color: '#1B5E20',
            fontWeight: 800, fontSize: '0.8rem', flexShrink: 0,
          }}>
            {initials}
          </Avatar>
          <Box sx={{ flex: 1, minWidth: 0 }}>
            <Typography noWrap sx={{ color: '#fff', fontSize: '0.82rem', fontWeight: 600, lineHeight: 1.2 }}>
              {displayName}
            </Typography>
            <Typography noWrap sx={{ color: 'rgba(255,255,255,0.55)', fontSize: '0.7rem' }}>
              {roleLabel}
            </Typography>
          </Box>
          <Tooltip title="Déconnexion">
            <IconButton onClick={handleLogout} size="small" sx={{ color: 'rgba(255,255,255,0.55)', p: 0.5, '&:hover': { color: '#fff' } }}>
              <LogoutIcon sx={{ fontSize: 18 }} />
            </IconButton>
          </Tooltip>
        </Box>
      </Box>
    </Box>
  );
}

export default function Layout() {
  const { user, logout } = useAuth();
  const navigate  = useNavigate();
  const theme     = useTheme();
  const isMobile  = useMediaQuery(theme.breakpoints.down('md'));
  const [drawerOpen, setDrawerOpen] = useState(false);

  const handleLogout = () => { logout(); navigate('/'); };

  return (
    <DrawerOpenContext.Provider value={() => setDrawerOpen(true)}>
      <Box sx={{ display: 'flex', minHeight: '100vh' }}>

        {/* Sidebar desktop permanente */}
        {!isMobile && (
          <Drawer
            variant="permanent"
            sx={{
              width: SIDEBAR_WIDTH, flexShrink: 0,
              '& .MuiDrawer-paper': { width: SIDEBAR_WIDTH, boxSizing: 'border-box', borderRight: 'none' },
            }}
          >
            <SidebarContent />
          </Drawer>
        )}

        {/* Drawer mobile temporaire */}
        {isMobile && (
          <Drawer
            variant="temporary"
            open={drawerOpen}
            onClose={() => setDrawerOpen(false)}
            ModalProps={{ keepMounted: true }}
            sx={{ '& .MuiDrawer-paper': { width: SIDEBAR_WIDTH, boxSizing: 'border-box', borderRight: 'none' } }}
          >
            <SidebarContent onNavigate={() => setDrawerOpen(false)} />
          </Drawer>
        )}

        <Box sx={{ flexGrow: 1, display: 'flex', flexDirection: 'column', minWidth: 0 }}>

          {/* Topbar mobile — toutes les pages */}
          {isMobile && (
            <AppBar
              position="sticky"
              elevation={0}
              sx={{ bgcolor: '#FFFFFF', borderBottom: '1px solid #E8F5E9', color: 'text.primary' }}
            >
              <Toolbar sx={{ minHeight: 56, gap: 1, px: 1.5 }}>
                <IconButton edge="start" onClick={() => setDrawerOpen(true)} sx={{ color: 'text.primary' }}>
                  <MenuIcon />
                </IconButton>
                <Typography variant="subtitle2" sx={{ fontWeight: 700, color: '#1B5E20', fontSize: '0.9rem' }}>
                  Graine Fournie
                </Typography>
                <Box sx={{ flexGrow: 1 }} />
                <TopbarMeteoWidget />
                <Tooltip title="Déconnexion">
                  <Avatar
                    sx={{ width: 34, height: 34, bgcolor: '#1B5E20', fontSize: '0.85rem', cursor: 'pointer' }}
                    onClick={handleLogout}
                  >
                    {user?.email?.[0]?.toUpperCase() ?? 'U'}
                  </Avatar>
                </Tooltip>
              </Toolbar>
            </AppBar>
          )}

          {/* Contenu de la page */}
          <Box
            component="main"
            sx={{ flexGrow: 1, p: { xs: 1.5, md: 3 }, bgcolor: '#F7FAF3' }}
          >
            <Outlet />
          </Box>
        </Box>
      </Box>
    </DrawerOpenContext.Provider>
  );
}
