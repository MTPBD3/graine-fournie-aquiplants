import { Box, Typography, Paper, Chip, CircularProgress, Alert, Table, TableHead, TableRow, TableCell, TableBody } from '@mui/material';
import {
  LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
} from 'recharts';
import NotificationsActiveIcon from '@mui/icons-material/NotificationsActive';
import { useApi } from '../hooks/useApi';
import { formatDate } from '../utils/formatDate';

export default function DashboardAdminPage() {
  const { data: stats, loading: lStats, error: eStats } = useApi('/api/statistiques');
  const { data: alertes, loading: lAlerts, error: eAlerts } = useApi('/api/alertes');

  const evolution = (stats?.evolutionMensuelle ?? []).map(e => ({
    ...e,
    mois: e.mois
      ? new Date(e.mois + '-01').toLocaleDateString('fr-FR', { month: 'short', year: '2-digit' })
      : e.mois,
  }));

  return (
    <Box>
      <Typography sx={{ fontWeight: 700, fontSize: { xs: '1.1rem', md: '1.5rem' }, mb: 3 }}>
        Dashboard Admin
      </Typography>

      {(lStats || lAlerts) && <CircularProgress size={24} sx={{ mb: 2 }} />}
      {eStats && <Alert severity="error" sx={{ mb: 2 }}>{eStats}</Alert>}
      {eAlerts && <Alert severity="error" sx={{ mb: 2 }}>{eAlerts}</Alert>}

      {stats && (
        <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 2, mb: 3 }}>
          {[
            { label: 'Total sachets', value: stats.totalSachets ?? 0 },
            { label: 'En stock', value: stats.enStock ?? 0 },
            { label: 'En attente', value: stats.enAttente ?? 0 },
            { label: 'Épuisés', value: stats.epuises ?? 0 },
          ].map(({ label, value }) => (
            <Paper key={label} elevation={0} sx={{
              border: '1px solid', borderColor: 'divider', p: 2, minWidth: 130, flex: '1 1 130px',
            }}>
              <Typography variant="h4" sx={{ fontWeight: 700, color: '#1B5E20' }}>{value}</Typography>
              <Typography variant="body2" color="text.secondary">{label}</Typography>
            </Paper>
          ))}
        </Box>
      )}

      <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap' }}>
        {evolution.length > 0 && (
          <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider', p: 3, flex: '2 1 400px' }}>
            <Typography variant="subtitle1" sx={{ fontWeight: 600, mb: 2 }}>Évolution mensuelle</Typography>
            <ResponsiveContainer width="100%" height={220}>
              <LineChart data={evolution} margin={{ top: 5, right: 20, left: 0, bottom: 5 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="#E0E0E0" />
                <XAxis dataKey="mois" tick={{ fontSize: 11 }} />
                <YAxis tick={{ fontSize: 11 }} allowDecimals={false} />
                <Tooltip />
                <Line type="monotone" dataKey="total" name="Sachets" stroke="#1B5E20" strokeWidth={2} dot={{ r: 3 }} />
              </LineChart>
            </ResponsiveContainer>
          </Paper>
        )}

        <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider', p: 3, flex: '1 1 280px' }}>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 2 }}>
            <NotificationsActiveIcon sx={{ color: '#E65100', fontSize: 20 }} />
            <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>Alertes en attente</Typography>
            {Array.isArray(alertes) && (
              <Chip label={alertes.length} size="small" color={alertes.length > 0 ? 'warning' : 'default'} />
            )}
          </Box>

          {!lAlerts && Array.isArray(alertes) && alertes.length === 0 && (
            <Typography variant="body2" color="text.secondary">Aucune alerte.</Typography>
          )}

          {Array.isArray(alertes) && alertes.length > 0 && (
            <Table size="small">
              <TableHead>
                <TableRow>
                  <TableCell sx={{ fontWeight: 600, fontSize: '0.75rem' }}>N° de lot</TableCell>
                  <TableCell sx={{ fontWeight: 600, fontSize: '0.75rem' }}>Plante</TableCell>
                  <TableCell sx={{ fontWeight: 600, fontSize: '0.75rem' }}>Date</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {alertes.slice(0, 8).map(a => (
                  <TableRow key={a.id} hover>
                    <TableCell sx={{ fontSize: '0.75rem' }}>{a.numeroLot ?? '—'}</TableCell>
                    <TableCell sx={{ fontSize: '0.75rem' }}>{a.plant?.nomPlant ?? '—'}</TableCell>
                    <TableCell sx={{ fontSize: '0.75rem' }}>{formatDate(a.dateReception)}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </Paper>
      </Box>
    </Box>
  );
}
