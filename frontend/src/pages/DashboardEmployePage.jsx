import { Box, Grid, Paper, Typography, Chip, Divider, CircularProgress } from '@mui/material';
import { useAuth } from '../context/AuthContext';
import { useApi } from '../hooks/useApi';

function EmptyState({ message }) {
  return (
    <Typography variant="body2" color="text.disabled" sx={{ py: 2, textAlign: 'center' }}>
      {message}
    </Typography>
  );
}

export default function DashboardEmployePage() {
  const { user } = useAuth();
  const { data: sachets, loading, error } = useApi('/api/gf-clients');

  const displayName = user?.prenom
    ? `${user.prenom}${user.nom ? ' ' + user.nom.charAt(0).toUpperCase() + '.' : ''}`
    : (user?.email ?? '');

  const arrivees = Array.isArray(sachets) ? sachets.slice(0, 5) : [];
  const aTraiter = Array.isArray(sachets) ? sachets.filter(s => s.statut === 'a_traiter') : [];

  return (
    <Box>
      <Typography variant="h5" sx={{ fontWeight: 700, mb: 0.5 }}>
        Bonjour, {displayName}
      </Typography>

      <Grid container spacing={2}>
        {/* Arrivées récentes */}
        <Grid size={{ xs: 12, md: 7 }}>
          <Paper elevation={0} sx={{ p: 2.5, border: '1px solid', borderColor: 'divider' }}>
            <Typography variant="subtitle1" sx={{ fontWeight: 600, mb: 2 }}>
              Arrivées récentes
            </Typography>
            {loading ? (
              <Box sx={{ display: 'flex', justifyContent: 'center', py: 2 }}>
                <CircularProgress size={24} />
              </Box>
            ) : error ? (
              <EmptyState message="Aucune donnée disponible" />
            ) : arrivees.length === 0 ? (
              <EmptyState message="Aucune arrivée enregistrée" />
            ) : (
              arrivees.map((a, i) => (
                <Box key={a.id}>
                  <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', py: 1 }}>
                    <Box>
                      <Typography variant="body2" sx={{ fontWeight: 500 }}>
                        {a.plant?.nomPlant ?? '—'}
                      </Typography>
                      <Typography variant="caption" color="text.secondary">
                        {a.numeroLot ?? '—'} · {a.client?.nom ?? '—'}
                      </Typography>
                    </Box>
                    <Chip
                      label={a.statut === 'range' ? 'Rangé' : 'À traiter'}
                      size="small"
                      color={a.statut === 'range' ? 'success' : 'default'}
                      sx={{ fontSize: '0.75rem' }}
                    />
                  </Box>
                  {i < arrivees.length - 1 && <Divider />}
                </Box>
              ))
            )}
          </Paper>
        </Grid>

        {/* À traiter */}
        <Grid size={{ xs: 12, md: 5 }}>
          <Paper elevation={0} sx={{ p: 2.5, border: '1px solid', borderColor: 'divider' }}>
            <Typography variant="subtitle1" sx={{ fontWeight: 600, mb: 2 }}>
              Sachets à traiter
            </Typography>
            {loading ? (
              <Box sx={{ display: 'flex', justifyContent: 'center', py: 2 }}>
                <CircularProgress size={24} />
              </Box>
            ) : aTraiter.length === 0 ? (
              <EmptyState message="Aucun sachet à traiter" />
            ) : (
              <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
                {aTraiter.slice(0, 5).map((s) => (
                  <Box key={s.id} sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <Typography variant="body2">{s.plant?.nomPlant ?? '—'}</Typography>
                    <Chip label="À traiter" size="small" color="warning" sx={{ fontSize: '0.7rem' }} />
                  </Box>
                ))}
              </Box>
            )}
          </Paper>
        </Grid>
      </Grid>
    </Box>
  );
}
