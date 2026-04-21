import { Box, Grid, Paper, Typography, Chip, Divider, CircularProgress, useMediaQuery, useTheme } from '@mui/material';
import {
  LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip,
  ResponsiveContainer, PieChart, Pie, Cell, Legend,
} from 'recharts';
import Inventory2Icon from '@mui/icons-material/Inventory2';
import ScheduleIcon from '@mui/icons-material/Schedule';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import BlockIcon from '@mui/icons-material/Block';
import { useApi } from '../hooks/useApi';
import { useAuth } from '../context/AuthContext';

const DONUT_COLORS = ['#2E7D32', '#D4E157', '#FF8F00', '#388E3C'];

function KpiCard({ icon, label, value, color }) {
  return (
    <Paper
      elevation={0}
      sx={{
        p: { xs: 1.5, md: 2.5 },
        border: '1px solid',
        borderColor: 'divider',
        borderLeft: `4px solid ${color}`,
        display: 'flex',
        alignItems: 'center',
        gap: 1.5,
      }}
    >
      <Box sx={{ color, display: { xs: 'none', sm: 'flex' } }}>{icon}</Box>
      <Box>
        <Typography variant="h5" sx={{ fontWeight: 700, fontFamily: '"DM Mono", monospace', fontSize: { xs: '1.25rem', md: '1.5rem' } }}>
          {value ?? '—'}
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ fontSize: { xs: '0.75rem', md: '0.875rem' } }}>
          {label}
        </Typography>
      </Box>
    </Paper>
  );
}

function EmptyState({ message }) {
  return (
    <Typography variant="body2" color="text.disabled" sx={{ py: 2, textAlign: 'center' }}>
      {message}
    </Typography>
  );
}

export default function DashboardAdminPage() {
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));
  const { user } = useAuth();

  const displayName = user?.prenom
    ? `${user.prenom}${user.nom ? ' ' + user.nom.charAt(0).toUpperCase() + '.' : ''}`
    : (user?.email ?? '');

  const { data: sachets, loading: lSachets, error: eSachets } = useApi('/api/gf-clients');
  const { data: stats } = useApi('/api/statistiques');

  const allSachets     = Array.isArray(sachets) ? sachets : [];
  const totalSachets   = lSachets ? null : allSachets.length;
  const nbATraiter     = lSachets ? null : allSachets.filter((s) => s.statut === 'en_attente').length;
  const nbRanges       = lSachets ? null : allSachets.filter((s) => s.statut === 'en_stock').length;
  const nbEpuises      = lSachets ? null : allSachets.filter((s) => s.statut === 'epuise').length;
  const aTraiterList   = allSachets.filter((s) => s.statut === 'en_attente').slice(0, 5);
  const arrivees       = allSachets.filter((s) => s.statut !== 'epuise').slice(0, 5);
  const evolutionData = stats?.evolutionMensuelle ?? [];
  const categoriesData = stats?.categories ?? [];

  const chartHeight = isMobile ? 160 : 200;

  return (
    <Box>
      <Typography sx={{ fontWeight: 700, mb: 0.5, fontSize: { xs: '1.1rem', md: '1.5rem' } }}>
        Bonjour, {displayName}
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2.5 }}>
        Tableau de bord — Administration
      </Typography>

      {/* KPI Cards — xs=6 (2/ligne), md=3 (4/ligne) */}
      <Grid container spacing={{ xs: 1, md: 2 }} sx={{ mb: { xs: 2, md: 3 } }}>
        <Grid size={{ xs: 6, md: 3 }}>
          <KpiCard icon={<Inventory2Icon />} label="Total sachets" value={lSachets ? '…' : totalSachets} color="#1565C0" />
        </Grid>
        <Grid size={{ xs: 6, md: 3 }}>
          <KpiCard icon={<ScheduleIcon />} label="À traiter" value={lSachets ? '…' : nbATraiter} color="#FF8F00" />
        </Grid>
        <Grid size={{ xs: 6, md: 3 }}>
          <KpiCard icon={<CheckCircleIcon />} label="Rangés" value={lSachets ? '…' : nbRanges} color="#2E7D32" />
        </Grid>
        <Grid size={{ xs: 6, md: 3 }}>
          <KpiCard icon={<BlockIcon />} label="Épuisés" value={lSachets ? '…' : nbEpuises} color="#E53935" />
        </Grid>
      </Grid>

      <Grid container spacing={{ xs: 1, md: 2 }} sx={{ mb: { xs: 2, md: 3 } }}>
        {/* Graphique évolution */}
        <Grid size={{ xs: 12, md: 8 }}>
          <Paper elevation={0} sx={{ p: { xs: 1.5, md: 2.5 }, border: '1px solid', borderColor: 'divider' }}>
            <Typography variant="subtitle1" sx={{ fontWeight: 600, mb: 1.5, fontSize: { xs: '0.875rem', md: '1rem' } }}>
              Évolution des sachets traités
            </Typography>
            {evolutionData.length === 0 ? (
              <EmptyState message="Données non disponibles" />
            ) : (
              <ResponsiveContainer width="100%" height={chartHeight}>
                <LineChart data={evolutionData}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#E8F5E9" />
                  <XAxis dataKey="mois" tick={{ fontSize: 11 }} />
                  <YAxis tick={{ fontSize: 11, fontFamily: '"DM Mono", monospace' }} />
                  <Tooltip />
                  <Line type="monotone" dataKey="sachets" stroke="#2E7D32" strokeWidth={2} dot={{ r: 3 }} activeDot={{ r: 5 }} />
                </LineChart>
              </ResponsiveContainer>
            )}
          </Paper>
        </Grid>

        {/* Donut */}
        <Grid size={{ xs: 12, md: 4 }}>
          <Paper elevation={0} sx={{ p: { xs: 1.5, md: 2.5 }, border: '1px solid', borderColor: 'divider' }}>
            <Typography variant="subtitle1" sx={{ fontWeight: 600, mb: 1, fontSize: { xs: '0.875rem', md: '1rem' } }}>
              Répartition catégories
            </Typography>
            {categoriesData.length === 0 ? (
              <EmptyState message="Données non disponibles" />
            ) : (
              <ResponsiveContainer width="100%" height={isMobile ? 180 : 220}>
                <PieChart>
                  <Pie data={categoriesData} cx="50%" cy="45%" innerRadius={isMobile ? 40 : 55} outerRadius={isMobile ? 65 : 80} dataKey="value">
                    {categoriesData.map((_, i) => (
                      <Cell key={i} fill={DONUT_COLORS[i % DONUT_COLORS.length]} />
                    ))}
                  </Pie>
                  <Legend iconSize={10} wrapperStyle={{ fontSize: 11 }} />
                  <Tooltip />
                </PieChart>
              </ResponsiveContainer>
            )}
          </Paper>
        </Grid>
      </Grid>

      <Grid container spacing={{ xs: 1, md: 2 }}>
        {/* Sachets à traiter */}
        <Grid size={{ xs: 12, md: 5 }}>
          <Paper elevation={0} sx={{ p: { xs: 1.5, md: 2.5 }, border: '1px solid', borderColor: 'divider' }}>
            <Typography variant="subtitle1" sx={{ fontWeight: 600, mb: 1.5, fontSize: { xs: '0.875rem', md: '1rem' } }}>
              Sachets à traiter
            </Typography>
            {lSachets ? (
              <Box sx={{ display: 'flex', justifyContent: 'center', py: 2 }}><CircularProgress size={24} /></Box>
            ) : aTraiterList.length === 0 ? (
              <EmptyState message="Aucun sachet en attente" />
            ) : (
              aTraiterList.map((s, i) => (
                <Box key={s.id}>
                  <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', py: 1 }}>
                    <Box sx={{ minWidth: 0 }}>
                      <Typography variant="body2" sx={{ fontWeight: 500 }} noWrap>{s.plant?.nomPlant ?? '—'}</Typography>
                      <Typography variant="caption" color="text.secondary" noWrap sx={{ display: 'block' }}>
                        {s.numeroLot ?? '—'} · {s.client?.nom ?? '—'}
                      </Typography>
                    </Box>
                    <Chip
                      label="À traiter"
                      size="small"
                      sx={{ fontSize: '0.7rem', ml: 1, flexShrink: 0, bgcolor: '#FFF3E0', color: '#FF8F00', border: '1px solid #FFB74D' }}
                    />
                  </Box>
                  {i < aTraiterList.length - 1 && <Divider />}
                </Box>
              ))
            )}
          </Paper>
        </Grid>

        {/* Arrivées récentes */}
        <Grid size={{ xs: 12, md: 7 }}>
          <Paper elevation={0} sx={{ p: { xs: 1.5, md: 2.5 }, border: '1px solid', borderColor: 'divider' }}>
            <Typography variant="subtitle1" sx={{ fontWeight: 600, mb: 1.5, fontSize: { xs: '0.875rem', md: '1rem' } }}>
              Arrivées récentes
            </Typography>
            {lSachets ? (
              <Box sx={{ display: 'flex', justifyContent: 'center', py: 2 }}><CircularProgress size={24} /></Box>
            ) : eSachets ? (
              <EmptyState message="Données non disponibles" />
            ) : arrivees.length === 0 ? (
              <EmptyState message="Aucune arrivée enregistrée" />
            ) : (
              arrivees.map((a, i) => (
                <Box key={a.id}>
                  <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', py: 1 }}>
                    <Box sx={{ minWidth: 0 }}>
                      <Typography variant="body2" sx={{ fontWeight: 500 }} noWrap>
                        {a.plant?.nomPlant ?? '—'}
                      </Typography>
                      <Typography variant="caption" color="text.secondary" noWrap sx={{ display: 'block' }}>
                        {a.numeroLot ?? '—'} · {a.client?.nom ?? '—'}
                      </Typography>
                    </Box>
                    <Chip
                      label={a.statut === 'en_stock' ? 'Rangé' : a.statut === 'epuise' ? 'Épuisé' : 'À traiter'}
                      size="small"
                      color={a.statut === 'en_stock' ? 'success' : a.statut === 'epuise' ? 'error' : 'default'}
                      sx={{ fontSize: '0.7rem', ml: 1, flexShrink: 0 }}
                    />
                  </Box>
                  {i < arrivees.length - 1 && <Divider />}
                </Box>
              ))
            )}
          </Paper>
        </Grid>
      </Grid>
    </Box>
  );
}
