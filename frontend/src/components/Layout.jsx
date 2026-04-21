import { useState } from 'react';
import { useNavigate, useLocation, Outlet } from 'react-router-dom';
import {
  Box,
  Drawer,
  List,
  ListItemButton,
  ListItemIcon,
  ListItemText,
  AppBar,
  Toolbar,
  Typography,
  IconButton,
  Avatar,
  Divider,
  Tooltip,
  InputBase,
  useMediaQuery,
  useTheme,
  Collapse,
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
import SearchIcon from '@mui/icons-material/Search';
import CloseIcon from '@mui/icons-material/Close';
import { useAuth } from '../context/AuthContext';

const SIDEBAR_WIDTH = 240;
const SIDEBAR_BG = '#1B5E20';
const SIDEBAR_TEXT = '#FFFFFF';
const SIDEBAR_ACTIVE_BG = 'rgba(255,255,255,0.15)';

const menuItems = [
  { label: 'Tableau de bord', icon: <DashboardIcon />, path: '/dashboard', roles: ['ROLE_EMPLOYE', 'ROLE_ADMIN'] },
  { label: 'Arrivée sachet', icon: <Inventory2Icon />, path: '/arrivees-sachets', roles: ['ROLE_EMPLOYE', 'ROLE_ADMIN'] },
  { label: 'Gestion stocks', icon: <WarehouseIcon />, path: '/gestion-stocks', roles: ['ROLE_EMPLOYE', 'ROLE_ADMIN'] },
  { label: 'Statistiques', icon: <BarChartIcon />, path: '/statistiques', roles: ['ROLE_EMPLOYE', 'ROLE_ADMIN'] },
  { label: 'Alertes stock', icon: <NotificationsActiveIcon />, path: '/alertes', roles: ['ROLE_ADMIN'] },
  { label: 'Gestion utilisateurs', icon: <PeopleIcon />, path: '/gestion-utilisateurs', roles: ['ROLE_ADMIN'] },
  { label: 'Paramètres', icon: <SettingsIcon />, path: '/parametres', roles: ['ROLE_EMPLOYE', 'ROLE_ADMIN'] },
];

function SidebarContent({ onNavigate }) {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  const userRoles = user?.roles ?? [];
  const visibleItems = menuItems.filter((item) =>
    item.roles.some((r) => userRoles.includes(r))
  );

  const handleNav = (path) => {
    navigate(path);
    onNavigate?.();
  };

  const handleLogout = () => {
    logout();
    navigate('/');
    onNavigate?.();
  };

  return (
    <Box sx={{ display: 'flex', flexDirection: 'column', height: '100%', backgroundColor: SIDEBAR_BG, color: SIDEBAR_TEXT }}>
      <Box sx={{ px: 2.5, py: 3 }}>
        <Typography variant="subtitle1" sx={{ fontWeight: 700, color: '#D4E157', lineHeight: 1.2, fontSize: '0.95rem' }}>
          Graine Fournie
        </Typography>
        <Typography variant="caption" sx={{ color: 'rgba(255,255,255,0.6)', fontSize: '0.75rem' }}>
          AQUIPLANTS · Eyragues
        </Typography>
      </Box>

      <Divider sx={{ borderColor: 'rgba(255,255,255,0.15)', mx: 2 }} />

      <List sx={{ px: 1, mt: 1, flexGrow: 1 }}>
        {visibleItems.map((item) => {
          const active = location.pathname.startsWith(item.path);
          return (
            <ListItemButton
              key={item.path}
              onClick={() => handleNav(item.path)}
              sx={{
                borderRadius: 2,
                mb: 0.5,
                backgroundColor: active ? SIDEBAR_ACTIVE_BG : 'transparent',
                '&:hover': { backgroundColor: SIDEBAR_ACTIVE_BG },
                color: SIDEBAR_TEXT,
              }}
            >
              <ListItemIcon sx={{ color: active ? '#D4E157' : 'rgba(255,255,255,0.7)', minWidth: 38 }}>
                {item.icon}
              </ListItemIcon>
              <ListItemText
                primary={item.label}
                slotProps={{ primary: { fontSize: '0.875rem', fontWeight: active ? 600 : 400 } }}
              />
            </ListItemButton>
          );
        })}
      </List>

      <Divider sx={{ borderColor: 'rgba(255,255,255,0.15)', mx: 2, mb: 1 }} />
      <Box sx={{ px: 1, pb: 2 }}>
        <ListItemButton
          onClick={handleLogout}
          sx={{
            borderRadius: 2,
            color: 'rgba(255,255,255,0.7)',
            '&:hover': { backgroundColor: SIDEBAR_ACTIVE_BG, color: '#fff' },
          }}
        >
          <ListItemIcon sx={{ color: 'inherit', minWidth: 38 }}>
            <LogoutIcon />
          </ListItemIcon>
          <ListItemText primary="Déconnexion" slotProps={{ primary: { fontSize: '0.875rem' } }} />
        </ListItemButton>
      </Box>
    </Box>
  );
}

export default function Layout() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [searchOpen, setSearchOpen] = useState(false);

  const handleLogout = () => {
    logout();
    navigate('/');
  };

  return (
    <Box sx={{ display: 'flex', minHeight: '100vh' }}>
      {/* Sidebar desktop */}
      {!isMobile && (
        <Drawer
          variant="permanent"
          sx={{
            width: SIDEBAR_WIDTH,
            flexShrink: 0,
            '& .MuiDrawer-paper': { width: SIDEBAR_WIDTH, boxSizing: 'border-box', borderRight: 'none' },
          }}
        >
          <SidebarContent />
        </Drawer>
      )}

      {/* Drawer mobile */}
      {isMobile && (
        <Drawer
          variant="temporary"
          open={drawerOpen}
          onClose={() => setDrawerOpen(false)}
          ModalProps={{ keepMounted: true }}
          sx={{
            '& .MuiDrawer-paper': { width: SIDEBAR_WIDTH, boxSizing: 'border-box', borderRight: 'none' },
          }}
        >
          <SidebarContent onNavigate={() => setDrawerOpen(false)} />
        </Drawer>
      )}

      <Box sx={{ flexGrow: 1, display: 'flex', flexDirection: 'column', minWidth: 0 }}>
        {/* Topbar */}
        <AppBar
          position="sticky"
          elevation={0}
          sx={{ backgroundColor: '#FFFFFF', borderBottom: '1px solid #E0E0E0', color: 'text.primary' }}
        >
          <Toolbar sx={{ minHeight: 56, gap: 1, px: { xs: 1.5, md: 2 } }}>
            {/* Hamburger mobile */}
            {isMobile && (
              <IconButton edge="start" onClick={() => setDrawerOpen(true)} sx={{ color: 'text.primary' }}>
                <MenuIcon />
              </IconButton>
            )}

            {/* Logo mobile */}
            {isMobile && !searchOpen && (
              <Typography variant="subtitle2" sx={{ fontWeight: 700, color: 'primary.dark', flexGrow: 1, fontSize: '0.9rem' }}>
                Graine Fournie
              </Typography>
            )}

            {/* Barre de recherche desktop (toujours visible) */}
            {!isMobile && (
              <Box
                sx={{
                  display: 'flex',
                  alignItems: 'center',
                  backgroundColor: '#F5F5F5',
                  border: '1px solid #E0E0E0',
                  borderRadius: 3,
                  px: 1.5,
                  py: 0.5,
                  flexGrow: 1,
                  maxWidth: 400,
                }}
              >
                <SearchIcon sx={{ color: 'text.disabled', fontSize: 18, mr: 1 }} />
                <InputBase placeholder="Rechercher..." sx={{ fontSize: '0.875rem', width: '100%' }} />
              </Box>
            )}

            {/* Barre de recherche mobile expandable */}
            {isMobile && searchOpen && (
              <Box
                sx={{
                  display: 'flex',
                  alignItems: 'center',
                  backgroundColor: '#F5F5F5',
                  border: '1px solid #E0E0E0',
                  borderRadius: 3,
                  px: 1.5,
                  py: 0.5,
                  flexGrow: 1,
                }}
              >
                <SearchIcon sx={{ color: 'text.disabled', fontSize: 18, mr: 1 }} />
                <InputBase placeholder="Rechercher..." sx={{ fontSize: '0.875rem', width: '100%' }} autoFocus />
              </Box>
            )}

            <Box sx={{ flexGrow: isMobile && !searchOpen ? 0 : 1 }} />

            {/* Icône loupe mobile */}
            {isMobile && (
              <IconButton
                onClick={() => setSearchOpen((v) => !v)}
                sx={{ color: 'text.secondary' }}
                size="small"
              >
                {searchOpen ? <CloseIcon /> : <SearchIcon />}
              </IconButton>
            )}

            {/* Email utilisateur desktop */}
            {!isMobile && (
              <Typography variant="body2" sx={{ color: 'text.secondary', mr: 1 }}>
                {user?.email}
              </Typography>
            )}

            {/* Avatar */}
            <Tooltip title="Déconnexion">
              <Avatar
                sx={{ width: 34, height: 34, bgcolor: 'primary.main', fontSize: '0.85rem', cursor: 'pointer' }}
                onClick={handleLogout}
              >
                {user?.email?.[0]?.toUpperCase() ?? 'U'}
              </Avatar>
            </Tooltip>
          </Toolbar>
        </AppBar>

        {/* Contenu */}
        <Box component="main" sx={{ flexGrow: 1, p: { xs: 1.5, md: 3 }, backgroundColor: 'background.default' }}>
          <Outlet />
        </Box>
      </Box>
    </Box>
  );
}
