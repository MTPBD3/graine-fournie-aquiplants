import { useState, useMemo, useRef, useEffect, useCallback, useContext } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Box, Grid, Paper, Typography, Chip, Divider, CircularProgress,
  IconButton, Avatar, LinearProgress,
  InputBase, Badge, Collapse, Tooltip, useMediaQuery, useTheme,
} from '@mui/material';
import {
  PieChart, Pie, Cell, Legend,
  Tooltip as RTooltip, ResponsiveContainer,
} from 'recharts';
import EvolutionDepotsChart from '../components/EvolutionDepotsChart';
import NotificationsIcon from '@mui/icons-material/Notifications';
import SettingsIcon from '@mui/icons-material/Settings';
import MenuIcon from '@mui/icons-material/Menu';
import RemoveIcon from '@mui/icons-material/Remove';
import SearchIcon from '@mui/icons-material/Search';
import CloseIcon from '@mui/icons-material/Close';
import Inventory2Icon from '@mui/icons-material/Inventory2';
import WarningAmberIcon from '@mui/icons-material/WarningAmber';
import HourglassEmptyIcon from '@mui/icons-material/HourglassEmpty';
import GrassIcon from '@mui/icons-material/Grass';
import FiberManualRecordIcon from '@mui/icons-material/FiberManualRecord';
import { useApi } from '../hooks/useApi';
import { useAuth } from '../context/AuthContext';
import { DrawerOpenContext } from '../components/Layout';
import { formatDate } from '../utils/formatDate';

const DONUT_COLORS = ['#1B5E20', '#D4E157', '#FF8F00', '#388E3C', '#81C784', '#E53935'];
const API_BASE = import.meta.env.VITE_API_URL;

const WIDGET_SX = {
  borderRadius: '12px',
  boxShadow: '0 2px 12px rgba(0,0,0,0.06)',
  border: '1px solid rgba(27,94,32,0.08)',
  overflow: 'hidden',
  mb: 2.5,
  bgcolor: '#fff',
};

// ── Widget avec collapse ──────────────────────────────────────────────────────
function Widget({ title, badge, extra, children, loading = false }) {
  const [open, setOpen] = useState(true);
  return (
    <Paper elevation={0} sx={WIDGET_SX}>
      <Box sx={{
        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
        px: 2.5, py: 1.75,
        borderBottom: open ? '1px solid rgba(0,0,0,0.05)' : 'none',
      }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, flexWrap: 'wrap' }}>
          <Typography sx={{ fontWeight: 700, fontSize: '0.875rem' }}>{title}</Typography>
          {badge}
        </Box>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
          {extra}
          <IconButton size="small" onClick={() => setOpen(v => !v)} sx={{ color: 'text.disabled', p: 0.5 }}>
            <RemoveIcon sx={{ fontSize: 16 }} />
          </IconButton>
        </Box>
      </Box>
      <Collapse in={open}>
        <Box sx={{ p: 2.5 }}>
          {loading
            ? <Box sx={{ display: 'flex', justifyContent: 'center', py: 3 }}><CircularProgress size={22} sx={{ color: '#1B5E20' }} /></Box>
            : children}
        </Box>
      </Collapse>
    </Paper>
  );
}

// ── KPI Card ──────────────────────────────────────────────────────────────────
function KpiCard({ icon, label, value, color, loading, badge }) {
  return (
    <Paper elevation={0} sx={{
      p: 2.5, borderRadius: '12px',
      boxShadow: '0 2px 12px rgba(0,0,0,0.06)',
      border: '1px solid rgba(27,94,32,0.08)',
      display: 'flex', alignItems: 'flex-start', gap: 2,
      bgcolor: '#fff', height: '100%',
    }}>
      <Box sx={{
        width: 44, height: 44, borderRadius: '10px',
        bgcolor: `${color}1A`,
        display: 'flex', alignItems: 'center', justifyContent: 'center',
        flexShrink: 0, color,
      }}>
        {icon}
      </Box>
      <Box sx={{ flex: 1, minWidth: 0 }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.75, flexWrap: 'wrap', mb: 0.25 }}>
          <Typography sx={{ fontWeight: 800, fontSize: '1.75rem', fontFamily: '"DM Mono", monospace', lineHeight: 1, color: '#1a1a1a' }}>
            {loading ? '—' : (value ?? '—')}
          </Typography>
          {!loading && badge}
        </Box>
        <Typography variant="caption" sx={{ color: 'text.secondary', fontSize: '0.8rem' }}>
          {label}
        </Typography>
      </Box>
    </Paper>
  );
}

function Empty({ text = 'Aucune donnée disponible' }) {
  return (
    <Typography variant="body2" sx={{ color: 'text.disabled', textAlign: 'center', py: 2 }}>
      {text}
    </Typography>
  );
}

// ── Barre de recherche intelligente Client / Graine ───────────────────────────
function SmartSearch({ searchMode, onModeChange, onSelect }) {
  const { token } = useAuth();
  const [query, setQuery]     = useState('');
  const [results, setResults] = useState([]);
  const [open, setOpen]       = useState(false);
  const containerRef          = useRef(null);
  const debounceRef           = useRef(null);

  const placeholder = searchMode === 'client'
    ? 'Rechercher par client...'
    : 'Rechercher par numéro de lot / nom du plant';

  // Fermer la liste au clic en dehors
  useEffect(() => {
    const handler = (e) => {
      if (containerRef.current && !containerRef.current.contains(e.target)) setOpen(false);
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, []);

  const handleInput = useCallback((value) => {
    setQuery(value);
    clearTimeout(debounceRef.current);
    if (value.length < 2) { setResults([]); setOpen(false); return; }
    debounceRef.current = setTimeout(async () => {
      const endpoint = searchMode === 'client'
        ? `${API_BASE}/api/clients?search=${encodeURIComponent(value)}`
        : `${API_BASE}/api/gf-clients?search=${encodeURIComponent(value)}`;
      try {
        const res = await fetch(endpoint, {
          headers: { Authorization: `Bearer ${token}` },
        });
        if (res.ok) {
          const data = await res.json();
          setResults(Array.isArray(data) ? data : []);
          setOpen(true);
        }
      } catch { /* ignore */ }
    }, 300);
  }, [searchMode, token]);

  const handleSelect = (item) => {
    const label = searchMode === 'client'
      ? `${item.prenomClient ?? ''} ${item.nomClient ?? ''}`.trim()
      : `${item.numeroLot ?? ''} · ${item.plant?.nomPlant ?? ''}`.trim();
    setQuery(label);
    setOpen(false);
    onSelect(label);
  };

  const handleClear = () => {
    setQuery('');
    setResults([]);
    setOpen(false);
    onSelect('');
  };

  const handleModeChange = (mode) => {
    onModeChange(mode);
    handleClear();
  };

  return (
    <Box ref={containerRef} sx={{ position: 'relative', flex: 1, maxWidth: { xs: '100%', md: 420 }, minWidth: 0 }}>
      <Box sx={{ display: 'flex', alignItems: 'stretch' }}>

        {/* Toggle Client / Graine */}
        <Box sx={{
          display: 'flex',
          border: '1px solid rgba(0,0,0,0.12)',
          borderRight: 'none',
          borderRadius: '8px 0 0 8px',
          overflow: 'hidden',
          flexShrink: 0,
        }}>
          {[['client', 'Client'], ['graine', 'Graine']].map(([val, lbl], idx) => (
            <Box
              key={val}
              onClick={() => handleModeChange(val)}
              sx={{
                px: 1.5, display: 'flex', alignItems: 'center',
                cursor: 'pointer', fontSize: '0.75rem', fontWeight: 600,
                bgcolor: searchMode === val ? '#1B5E20' : '#fff',
                color: searchMode === val ? '#fff' : 'text.secondary',
                transition: 'background 0.15s',
                userSelect: 'none',
                borderRight: idx === 0 ? '1px solid rgba(0,0,0,0.12)' : 'none',
              }}
            >
              {lbl}
            </Box>
          ))}
        </Box>

        {/* Champ de saisie */}
        <Box sx={{
          display: 'flex', alignItems: 'center',
          bgcolor: '#F7FAF3', border: '1px solid rgba(0,0,0,0.12)',
          borderRadius: '0 8px 8px 0', px: 1.5, py: 0.5, flex: 1,
        }}>
          <SearchIcon sx={{ color: 'text.disabled', fontSize: 18, mr: 1, flexShrink: 0 }} />
          <InputBase
            placeholder={placeholder}
            value={query}
            onChange={e => handleInput(e.target.value)}
            sx={{ fontSize: '0.875rem', flex: 1 }}
          />
          {query && (
            <IconButton size="small" onClick={handleClear} sx={{ p: 0.25, ml: 0.5 }}>
              <CloseIcon sx={{ fontSize: 14 }} />
            </IconButton>
          )}
        </Box>
      </Box>

      {/* Liste déroulante */}
      {open && (
        <Paper elevation={6} sx={{
          position: 'absolute', top: '100%', left: 0, right: 0, zIndex: 1400,
          mt: 0.5, borderRadius: '8px', overflow: 'hidden',
          maxHeight: 220, overflowY: 'auto',
        }}>
          {results.length === 0 ? (
            <Typography sx={{ px: 2, py: 1.5, fontSize: '0.85rem', color: 'text.secondary' }}>
              Aucun résultat
            </Typography>
          ) : (
            results.map((item, i) => {
              const label = searchMode === 'client'
                ? `${item.prenomClient ?? ''} ${item.nomClient ?? ''}`.trim()
                : `${item.numeroLot ?? ''} · ${item.plant?.nomPlant ?? ''}`;
              return (
                <Box
                  key={item.idGfClient ?? item.idClient ?? i}
                  onMouseDown={e => e.preventDefault()}
                  onClick={() => handleSelect(item)}
                  sx={{
                    px: 2, py: 1.25, cursor: 'pointer', fontSize: '0.85rem',
                    '&:hover': { bgcolor: '#F7FAF3' },
                    borderBottom: i < results.length - 1 ? '1px solid rgba(0,0,0,0.06)' : 'none',
                  }}
                >
                  {label}
                </Box>
              );
            })
          )}
        </Paper>
      )}
    </Box>
  );
}

// ── Page principale ───────────────────────────────────────────────────────────
export default function DashboardAdminPage() {
  const { user, logout } = useAuth();
  const navigate    = useNavigate();
  const openDrawer  = useContext(DrawerOpenContext);
  const theme       = useTheme();
  const isMobile    = useMediaQuery(theme.breakpoints.down('md'));
  const handleLogout = () => { logout(); navigate('/'); };

  const { data: stats,    loading: lStats  } = useApi('/api/statistiques');
  const { data: alertes,  loading: lAlertes } = useApi('/api/alertes');
  const { data: histoRaw, loading: lHisto  } = useApi('/api/histo-gf-deposees');
  const { data: sachets,  loading: lSachets } = useApi('/api/gf-clients');

  const [searchQuery, setSearchQuery] = useState('');
  const [searchMode, setSearchMode]   = useState('client');

  const today = useMemo(() =>
    new Date().toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }), []);

  const displayName = user?.prenom
    ? `${user.prenom}${user.nom ? ' ' + user.nom[0].toUpperCase() + '.' : ''}`
    : (user?.email ?? '');

  // ── KPI values ──────────────────────────────────────────────────────────────
  const totalEnStock  = stats?.parStatut?.range ?? null;
  const alertesCount  = Array.isArray(alertes) ? alertes.length : null;
  const aValiderCount = stats?.parStatut?.a_traiter ?? null;

  const totalUnites = useMemo(() => {
    if (lSachets || !Array.isArray(sachets)) return null;
    return sachets
      .filter(s => s.statut === 'range')
      .reduce((acc, s) => acc + (s.quantiteDisponible ?? 0), 0);
  }, [sachets, lSachets]);

  // ── Arrivals ────────────────────────────────────────────────────────────────
  const arrivees = useMemo(() => {
    if (!Array.isArray(histoRaw)) return [];
    return [...histoRaw].sort((a, b) => b.id - a.id).slice(0, 5);
  }, [histoRaw]);

  // ── Categories ──────────────────────────────────────────────────────────────
  const categories = stats?.categories ?? [];
  const maxVal     = Math.max(...categories.map(c => c.value), 1);

  // ── Search filtering ────────────────────────────────────────────────────────
  const filterByQuery = (items, clientKey, graineKey) => {
    if (!searchQuery.trim()) return items;
    const q = searchQuery.toLowerCase();
    return items.filter(a => {
      const target = searchMode === 'client'
        ? (typeof clientKey === 'function' ? clientKey(a) : a[clientKey] ?? '')
        : (typeof graineKey === 'function' ? graineKey(a) : a[graineKey] ?? '');
      return target.toLowerCase().includes(q);
    });
  };

  const filteredAlertes = useMemo(() =>
    filterByQuery(
      Array.isArray(alertes) ? alertes : [],
      a => `${a.client?.prenom ?? ''} ${a.client?.nom ?? ''}`,
      a => a.plant?.nomPlant ?? ''
    ),
  // eslint-disable-next-line react-hooks/exhaustive-deps
  [alertes, searchQuery, searchMode]);

  const filteredArrivees = useMemo(() =>
    filterByQuery(
      arrivees,
      a => a.gfClient?.nomClient ?? '',
      a => a.gfClient?.plant?.nomPlant ?? ''
    ),
  // eslint-disable-next-line react-hooks/exhaustive-deps
  [arrivees, searchQuery, searchMode]);

  const statutColor = s => s === 'range' ? '#2E7D32' : '#FF8F00';
  const statutLabel = s => s === 'range' ? 'Rangé' : 'À traiter';

  return (
    <Box sx={{ bgcolor: '#F7FAF3', minHeight: '100%' }}>

      {/* ── HEADER UNIQUE ───────────────────────────────────────────────── */}
      <Box sx={{
        position: 'sticky',
        top: 0,
        zIndex: 100,
        bgcolor: '#fff',
        borderBottom: '1px solid #E8F5E9',
        mx: { xs: -1.5, md: -3 },
        px: { xs: 1, md: 3 },
        py: 0,
        mb: 3,
      }}>
        <Box sx={{
          display: 'flex', alignItems: 'center', gap: { xs: 0.75, md: 2 },
          minHeight: 56,
          flexWrap: 'nowrap',
        }}>

          {/* Hamburger — mobile uniquement */}
          {isMobile && (
            <IconButton edge="start" onClick={openDrawer} sx={{ color: 'text.primary', flexShrink: 0 }}>
              <MenuIcon />
            </IconButton>
          )}

          {/* Titre — desktop uniquement */}
          <Box sx={{ display: { xs: 'none', md: 'block' }, flex: '0 0 auto' }}>
            <Typography sx={{ fontWeight: 800, fontSize: '1rem', color: '#1B5E20', lineHeight: 1.2 }}>
              Tableau de bord
            </Typography>
            <Typography variant="caption" sx={{ color: 'text.secondary', display: { xs: 'none', md: 'block' } }}>
              Vue d'ensemble · {displayName}
            </Typography>
          </Box>

          {/* SmartSearch — centre */}
          <SmartSearch
            searchMode={searchMode}
            onModeChange={v => { setSearchMode(v); setSearchQuery(''); }}
            onSelect={label => setSearchQuery(label)}
          />

          {/* Droite : cloche + engrenage + date + email + avatar */}
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, ml: 'auto', flexShrink: 0 }}>
            <Badge badgeContent={alertesCount ?? 0} color="error" max={99}>
              <IconButton size="small" sx={{ color: '#FF8F00', bgcolor: '#FF8F001A', borderRadius: '8px', p: 0.75 }}>
                <NotificationsIcon fontSize="small" />
              </IconButton>
            </Badge>
            <IconButton size="small" onClick={() => navigate('/parametres')} sx={{ color: 'text.secondary', bgcolor: 'rgba(0,0,0,0.04)', borderRadius: '8px', p: 0.75 }}>
              <SettingsIcon fontSize="small" />
            </IconButton>
            {!isMobile && (
              <Typography variant="caption" sx={{ color: 'text.secondary', fontSize: '0.72rem', mx: 0.5 }}>
                {today}
              </Typography>
            )}
            {!isMobile && (
              <Typography variant="body2" sx={{ color: 'text.secondary', fontSize: '0.8rem', mr: 0.5 }}>
                {user?.email}
              </Typography>
            )}
            <Tooltip title="Déconnexion">
              <Avatar
                sx={{ width: 32, height: 32, bgcolor: '#1B5E20', fontSize: '0.8rem', cursor: 'pointer' }}
                onClick={handleLogout}
              >
                {user?.email?.[0]?.toUpperCase() ?? 'U'}
              </Avatar>
            </Tooltip>
          </Box>
        </Box>
      </Box>

      {/* ── KPI CARDS ───────────────────────────────────────────────────── */}
      <Grid container spacing={2} sx={{ mb: 2.5 }}>
        <Grid size={{ xs: 6, md: 3 }}>
          <KpiCard
            icon={<Inventory2Icon />}
            label="Sachets en stock"
            value={totalEnStock}
            color="#1B5E20"
            loading={lStats}
          />
        </Grid>
        <Grid size={{ xs: 6, md: 3 }}>
          <KpiCard
            icon={<WarningAmberIcon />}
            label="Alertes stock bas"
            value={alertesCount}
            color="#FF8F00"
            loading={lAlertes}
            badge={alertesCount > 0 && (
              <Chip
                label="Urgent"
                size="small"
                sx={{ height: 18, fontSize: '0.62rem', bgcolor: '#FF8F00', color: '#fff', '& .MuiChip-label': { px: 0.75 } }}
              />
            )}
          />
        </Grid>
        <Grid size={{ xs: 6, md: 3 }}>
          <KpiCard
            icon={<HourglassEmptyIcon />}
            label="Arrivées à valider"
            value={aValiderCount}
            color="#D4A017"
            loading={lStats}
            badge={aValiderCount > 0 && (
              <Chip
                label="Attente"
                size="small"
                sx={{ height: 18, fontSize: '0.62rem', bgcolor: '#D4E157', color: '#1B5E20', '& .MuiChip-label': { px: 0.75 } }}
              />
            )}
          />
        </Grid>
        <Grid size={{ xs: 6, md: 3 }}>
          <KpiCard
            icon={<GrassIcon />}
            label="Total unités en stock"
            value={totalUnites !== null ? totalUnites.toLocaleString('fr-FR') : null}
            color="#388E3C"
            loading={lSachets}
          />
        </Grid>
      </Grid>

      {/* ── WIDGETS ─────────────────────────────────────────────────────── */}
      <Grid container spacing={2.5}>

        {/* ── Colonne gauche ── */}
        <Grid size={{ xs: 12, md: 7 }}>

          <EvolutionDepotsChart />

          {/* Arrivées récentes */}
          <Widget title="Arrivées récentes" loading={lHisto}>
            {filteredArrivees.length === 0 ? (
              <Empty text="Aucune arrivée enregistrée" />
            ) : (
              <Box>
                {filteredArrivees.map((a, i) => {
                  const nomPlant  = a.gfClient?.plant?.nomPlant ?? '?';
                  const initials  = nomPlant.slice(0, 2).toUpperCase();
                  const nomClient = a.gfClient?.nomClient ?? '—';
                  const sc = statutColor(a.statut);
                  return (
                    <Box key={a.id}>
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, py: 1.25 }}>
                        <Avatar sx={{ width: 36, height: 36, bgcolor: '#D4E157', color: '#1B5E20', fontWeight: 700, fontSize: '0.8rem', flexShrink: 0 }}>
                          {initials}
                        </Avatar>
                        <Box sx={{ flex: 1, minWidth: 0 }}>
                          <Typography variant="body2" sx={{ fontWeight: 600, fontSize: '0.85rem' }} noWrap>
                            {nomPlant}
                          </Typography>
                          <Typography variant="caption" sx={{ color: 'text.secondary' }} noWrap>
                            {nomClient} · {formatDate(a.dateReception)}
                          </Typography>
                        </Box>
                        <Chip
                          label={statutLabel(a.statut)}
                          size="small"
                          sx={{
                            height: 20, fontSize: '0.65rem', flexShrink: 0,
                            bgcolor: `${sc}18`, color: sc,
                            border: `1px solid ${sc}40`,
                            '& .MuiChip-label': { px: 0.75 },
                          }}
                        />
                      </Box>
                      {i < filteredArrivees.length - 1 && <Divider />}
                    </Box>
                  );
                })}
              </Box>
            )}
          </Widget>
        </Grid>

        {/* ── Colonne droite ── */}
        <Grid size={{ xs: 12, md: 5 }}>

          {/* Alertes stock bas */}
          <Widget
            title="Alertes stock bas"
            loading={lAlertes}
            badge={alertesCount > 0 && (
              <Chip
                label={alertesCount}
                size="small"
                sx={{ height: 18, fontSize: '0.65rem', bgcolor: '#FF8F0018', color: '#FF8F00', border: '1px solid #FF8F0040', '& .MuiChip-label': { px: 0.75 } }}
              />
            )}
          >
            {filteredAlertes.length === 0 ? (
              <Empty text="Aucune alerte active" />
            ) : (
              <Box>
                {filteredAlertes.slice(0, 5).map((a, i) => (
                  <Box key={a.id}>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.25, py: 1 }}>
                      <FiberManualRecordIcon sx={{
                        fontSize: 10, flexShrink: 0,
                        color: a.joursAttente > 7 ? '#E53935' : '#FF8F00',
                      }} />
                      <Box sx={{ flex: 1, minWidth: 0 }}>
                        <Typography variant="body2" sx={{ fontWeight: 600, fontSize: '0.82rem' }} noWrap>
                          {a.plant?.nomPlant ?? '—'}
                        </Typography>
                        <Typography variant="caption" sx={{ color: 'text.secondary' }} noWrap>
                          {a.client?.prenom} {a.client?.nom} · {a.joursAttente}j d'attente
                        </Typography>
                      </Box>
                      <Typography variant="caption" sx={{
                        fontFamily: '"DM Mono", monospace', fontWeight: 700,
                        color: '#FF8F00', flexShrink: 0, fontSize: '0.75rem',
                      }}>
                        {a.quantiteDeposee} u.
                      </Typography>
                    </Box>
                    {i < Math.min(filteredAlertes.length, 5) - 1 && <Divider />}
                  </Box>
                ))}
              </Box>
            )}
          </Widget>

          {/* Répartition catégories */}
          <Widget title="Répartition catégories" loading={lStats}>
            {categories.length === 0 ? (
              <Empty />
            ) : (
              <ResponsiveContainer width="100%" height={200}>
                <PieChart>
                  <Pie
                    data={categories} cx="50%" cy="45%"
                    innerRadius={50} outerRadius={75}
                    dataKey="value" paddingAngle={2}
                  >
                    {categories.map((_, i) => (
                      <Cell key={i} fill={DONUT_COLORS[i % DONUT_COLORS.length]} />
                    ))}
                  </Pie>
                  <Legend iconSize={10} wrapperStyle={{ fontSize: 11 }} />
                  <RTooltip contentStyle={{ fontSize: 12, borderRadius: 8 }} />
                </PieChart>
              </ResponsiveContainer>
            )}
          </Widget>

          {/* Niveau de stock par plante */}
          <Widget title="Niveau de stock par plante" loading={lStats}>
            {categories.length === 0 ? (
              <Empty />
            ) : (
              <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
                {categories.slice(0, 6).map((cat, i) => (
                  <Box key={cat.name}>
                    <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 0.5 }}>
                      <Typography variant="caption" sx={{ fontWeight: 600, fontSize: '0.78rem' }} noWrap>
                        {cat.name}
                      </Typography>
                      <Typography variant="caption" sx={{
                        fontFamily: '"DM Mono", monospace', fontSize: '0.75rem',
                        color: 'text.secondary', flexShrink: 0, ml: 1,
                      }}>
                        {cat.value}
                      </Typography>
                    </Box>
                    <LinearProgress
                      variant="determinate"
                      value={(cat.value / maxVal) * 100}
                      sx={{
                        height: 6, borderRadius: 3,
                        bgcolor: 'rgba(0,0,0,0.06)',
                        '& .MuiLinearProgress-bar': {
                          bgcolor: DONUT_COLORS[i % DONUT_COLORS.length],
                          borderRadius: 3,
                        },
                      }}
                    />
                  </Box>
                ))}
              </Box>
            )}
          </Widget>
        </Grid>
      </Grid>
    </Box>
  );
}
