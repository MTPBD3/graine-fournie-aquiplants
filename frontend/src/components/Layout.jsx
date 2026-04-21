import { useState } from 'react';
import { Outlet, useNavigate, useLocation, Link } from 'react-router-dom';
import {
  Box, Drawer, List, ListItemButton, ListItemIcon, ListItemText,
  AppBar, Toolbar, Typography, IconButton, Avatar, Menu, MenuItem,
  useMediaQuery, useTheme, Divider,
} from '@mui/material';
import MenuIcon from '@mui/icons-material/Menu';
import DashboardIcon from '@mui/icons-material/Dashboard';
import Inventory2Icon from '@mui/icons-material/Inventory2';
import WarehouseIcon from '@mui/icons-material/Warehouse';
import NotificationsActiveIcon from '@mui/icons-material/NotificationsActive';
import BarChartIcon from '@mui/icons-material/BarChart';
import PeopleIcon from '@mui/icons-material/People';
import SettingsIcon from '@mui/icons-material/Settings';
import { useAuth } from '../context/AuthContext';

const DRAWER_WIDTH = 220;

export default function Layout() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));
  const [mobileOpen, setMobileOpen] = useState(false);
  const [anchorEl, setAnchorEl] = useState(null);

  const isAdmin = user?.role === 'admin';

  const navItems = [
    ...(isAdmin
      ? [{ label: 'Dashboard', icon: <DashboardIcon />, path: '/dashboard-admin' }]
      : [{ label: 'Dashboard', icon: <DashboardIcon />, path: '/dashboard' }]),
    { label: 'Arrivées sachets', icon: <Inventory2Icon />, path: '/arrivees' },
    { label: 'Gestion stocks',   icon: <WarehouseIcon />,  path: '/stocks' },
    { label: 'Alertes',          icon: <NotificationsActiveIcon />, path: '/alertes' },
    ...(isAdmin ? [
      { label: 'Statistiques',   icon: <BarChartIcon />,  path: '/statistiques' },
      { label: 'Utilisateurs',   icon: <PeopleIcon />,    path: '/utilisateurs' },
    ] : []),
    { label: 'Paramètres', icon: <SettingsIcon />, path: '/parametres' },
  ];

  const drawer = (
    <Box sx={{ height: '100%', bgcolor: '#1B5E20', color: '#fff' }}>
      <Box sx={{ p: 2, pb: 1 }}>
        <Typography variant="h6" sx={{ fontWeight: 700, color: '#D4E157', fontSize: '1rem' }}>
          🌱 Aquiplants
        </Typography>
        <Typography variant="caption" sx={{ color: 'rgba(255,255,255,0.6)' }}>
          Graine Fournie
        </Typography>
      </Box>
      <Divider sx={{ borderColor: 'rgba(255,255,255,0.15)', mb: 1 }} />
      <List dense>
        {navItems.map(({ label, icon, path }) => (
          <ListItemButton
            key={path}
            component={Link}
            to={path}
            selected={location.pathname === path}
            onClick={() => setMobileOpen(false)}
            sx={{
              color: '#fff',
              borderRadius: 1,
              mx: 0.5,
              '&.Mui-selected': { bgcolor: 'rgba(212,225,87,0.2)', color: '#D4E157' },
              '&:hover': { bgcolor: 'rgba(255,255,255,0.1)' },
            }}
          >
            <ListItemIcon sx={{ color: 'inherit', minWidth: 36 }}>{icon}</ListItemIcon>
            <ListItemText primary={label} primaryTypographyProps={{ fontSize: '0.875rem' }} />
          </ListItemButton>
        ))}
      </List>
    </Box>
  );

  return (
    <Box sx={{ display: 'flex', minHeight: '100vh', bgcolor: 'background.default' }}>
      {isMobile ? (
        <Drawer
          open={mobileOpen}
          onClose={() => setMobileOpen(false)}
          ModalProps={{ keepMounted: true }}
          sx={{ '& .MuiDrawer-paper': { width: DRAWER_WIDTH, boxSizing: 'border-box' } }}
        >
          {drawer}
        </Drawer>
      ) : (
        <Drawer
          variant="permanent"
          sx={{ width: DRAWER_WIDTH, flexShrink: 0, '& .MuiDrawer-paper': { width: DRAWER_WIDTH, boxSizing: 'border-box', border: 'none' } }}
        >
          {drawer}
        </Drawer>
      )}

      <Box sx={{ flex: 1, display: 'flex', flexDirection: 'column', minWidth: 0 }}>
        <AppBar position="static" elevation={0} sx={{ bgcolor: '#fff', borderBottom: '1px solid', borderColor: 'divider' }}>
          <Toolbar variant="dense" sx={{ minHeight: 52 }}>
            {isMobile && (
              <IconButton edge="start" onClick={() => setMobileOpen(true)} sx={{ mr: 1, color: '#1B5E20' }}>
                <MenuIcon />
              </IconButton>
            )}
            <Box sx={{ flex: 1 }} />
            <IconButton onClick={(e) => setAnchorEl(e.currentTarget)} size="small">
              <Avatar sx={{ width: 32, height: 32, bgcolor: '#1B5E20', fontSize: '0.8rem' }}>
                {user?.prenom?.[0]}{user?.nom?.[0]}
              </Avatar>
            </IconButton>
            <Menu anchorEl={anchorEl} open={Boolean(anchorEl)} onClose={() => setAnchorEl(null)}>
              <MenuItem disabled sx={{ fontSize: '0.8rem' }}>
                {user?.prenom} {user?.nom} · {user?.role}
              </MenuItem>
              <MenuItem onClick={() => { setAnchorEl(null); navigate('/parametres'); }}>Paramètres</MenuItem>
              <MenuItem onClick={() => { setAnchorEl(null); logout(); navigate('/login'); }}>Déconnexion</MenuItem>
            </Menu>
          </Toolbar>
        </AppBar>

        <Box component="main" sx={{ flex: 1, p: { xs: 1.5, md: 3 }, overflow: 'auto' }}>
          <Outlet />
        </Box>
      </Box>
    </Box>
  );
}
