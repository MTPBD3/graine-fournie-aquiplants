import { Box, Typography, Paper, Chip, CircularProgress, Alert } from '@mui/material';
import NotificationsActiveIcon from '@mui/icons-material/NotificationsActive';
import { useApi } from '../hooks/useApi';
import { formatDate } from '../utils/formatDate';

export default function DashboardEmployePage() {
  const { data: alertes, loading, error } = useApi('/api/alertes');

  return (
    <Box>
      <Typography sx={{ fontWeight: 700, fontSize: { xs: '1.1rem', md: '1.5rem' }, mb: 3 }}>
        Tableau de bord
      </Typography>

      <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider', p: 3 }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 2 }}>
          <NotificationsActiveIcon sx={{ color: '#E65100' }} />
          <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
            Alertes en attente
          </Typography>
          {Array.isArray(alertes) && (
            <Chip label={alertes.length} size="small" color={alertes.length > 0 ? 'warning' : 'default'} />
          )}
        </Box>

        {loading && <CircularProgress size={24} />}
        {error && <Alert severity="error">{error}</Alert>}

        {!loading && Array.isArray(alertes) && alertes.length === 0 && (
          <Typography variant="body2" color="text.secondary">Aucune alerte en attente.</Typography>
        )}

        {!loading && Array.isArray(alertes) && alertes.length > 0 && (
          <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1 }}>
            {alertes.map(a => (
              <Box key={a.id} sx={{
                display: 'flex', justifyContent: 'space-between', alignItems: 'center',
                p: 1.5, borderRadius: 1, bgcolor: '#FFF3E0', border: '1px solid #FFB74D',
              }}>
                <Box>
                  <Typography variant="body2" sx={{ fontWeight: 600 }}>
                    {a.plant?.nomPlant ?? '—'} — {a.client?.prenomClient} {a.client?.nomClient}
                  </Typography>
                  <Typography variant="caption" color="text.secondary">
                    N° de lot : {a.numeroLot ?? '—'} · Reçu le {formatDate(a.dateReception)}
                  </Typography>
                </Box>
                <Chip label="En attente" size="small" sx={{ bgcolor: '#E65100', color: '#fff' }} />
              </Box>
            ))}
          </Box>
        )}
      </Paper>
    </Box>
  );
}
