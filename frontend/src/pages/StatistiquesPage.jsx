import { Box, Typography, Paper, CircularProgress, Alert } from '@mui/material';
import {
  LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend,
} from 'recharts';
import { useApi } from '../hooks/useApi';

export default function StatistiquesPage() {
  const { data: stats, loading, error } = useApi('/api/statistiques');

  const evolution = (stats?.evolutionMensuelle ?? []).map(e => ({
    ...e,
    mois: e.mois
      ? new Date(e.mois + '-01').toLocaleDateString('fr-FR', { month: 'short', year: '2-digit' })
      : e.mois,
  }));

  return (
    <Box>
      <Typography sx={{ fontWeight: 700, fontSize: { xs: '1.1rem', md: '1.5rem' }, mb: 3 }}>
        Statistiques
      </Typography>

      {loading && <CircularProgress size={24} />}
      {error && <Alert severity="error">{error}</Alert>}

      {!loading && stats && (
        <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 2, mb: 3 }}>
          {[
            { label: 'Total sachets', value: stats.totalSachets ?? 0 },
            { label: 'En stock', value: stats.enStock ?? 0 },
            { label: 'En attente', value: stats.enAttente ?? 0 },
            { label: 'Épuisés', value: stats.epuises ?? 0 },
          ].map(({ label, value }) => (
            <Paper key={label} elevation={0} sx={{
              border: '1px solid', borderColor: 'divider', p: 2, minWidth: 140, flex: '1 1 140px',
            }}>
              <Typography variant="h4" sx={{ fontWeight: 700, color: '#1B5E20' }}>{value}</Typography>
              <Typography variant="body2" color="text.secondary">{label}</Typography>
            </Paper>
          ))}
        </Box>
      )}

      {!loading && evolution.length > 0 && (
        <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider', p: 3 }}>
          <Typography variant="subtitle1" sx={{ fontWeight: 600, mb: 2 }}>
            Évolution mensuelle des arrivées
          </Typography>
          <ResponsiveContainer width="100%" height={300}>
            <LineChart data={evolution} margin={{ top: 5, right: 20, left: 0, bottom: 5 }}>
              <CartesianGrid strokeDasharray="3 3" stroke="#E0E0E0" />
              <XAxis dataKey="mois" tick={{ fontSize: 12 }} />
              <YAxis tick={{ fontSize: 12 }} allowDecimals={false} />
              <Tooltip />
              <Legend />
              <Line type="monotone" dataKey="total" name="Sachets reçus" stroke="#1B5E20" strokeWidth={2} dot={{ r: 4 }} />
            </LineChart>
          </ResponsiveContainer>
        </Paper>
      )}
    </Box>
  );
}
