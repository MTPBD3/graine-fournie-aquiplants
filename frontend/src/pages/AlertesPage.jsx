import { useState } from 'react';
import {
  Box, Typography, Paper, Table, TableBody, TableCell, TableContainer,
  TableHead, TableRow, Chip, Button, CircularProgress,
  Card, CardContent, CardActions, Divider, useMediaQuery, useTheme,
} from '@mui/material';
import NotificationsActiveIcon from '@mui/icons-material/NotificationsActive';
import CheckCircleOutlinedIcon from '@mui/icons-material/CheckCircleOutlined';
import CheckIcon from '@mui/icons-material/Check';
import { useApi, apiRequest } from '../hooks/useApi';
import { useAuth } from '../context/AuthContext';
import { formatDate } from '../utils/formatDate';

/** Doit correspondre à AlertesController::DELAI_JOURS côté backend. */
const DELAI_JOURS = 3;

function BadgeJours({ jours }) {
  return (
    <Chip
      label={`${jours} jour${jours > 1 ? 's' : ''}`}
      size="small"
      color="error"
      sx={{ fontFamily: '"DM Mono", monospace', fontWeight: 700, fontSize: '0.72rem' }}
    />
  );
}

export default function AlertesPage() {
  const { token, user } = useAuth();
  const isAdmin = user?.roles?.includes('ROLE_ADMIN') ?? false;
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));

  const { data, loading, error, refetch } = useApi('/api/alertes');
  const [markingId, setMarkingId] = useState(null);

  const alertes = Array.isArray(data) ? data : [];

  const handleMarquer = async (id) => {
    setMarkingId(id);
    try {
      await apiRequest(`/api/histo-gf-deposees/${id}`, 'PUT', { statut: 'range' }, token);
      refetch();
    } catch {
      // silencieux
    } finally {
      setMarkingId(null);
    }
  };

  return (
    <Box>
      {/* En-tête */}
      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 3, flexWrap: 'wrap' }}>
        <NotificationsActiveIcon sx={{ color: 'warning.main', fontSize: 28 }} />
        <Typography sx={{ fontWeight: 700, fontSize: { xs: '1.1rem', md: '1.5rem' } }}>
          Alertes — sachets en attente
        </Typography>
        {alertes.length > 0 && (
          <Chip
            label={`${alertes.length} sachet${alertes.length > 1 ? 's' : ''} > ${DELAI_JOURS} jours`}
            color="error"
            size="small"
          />
        )}
      </Box>

      {loading ? (
        <Box sx={{ display: 'flex', justifyContent: 'center', pt: 6 }}>
          <CircularProgress />
        </Box>
      ) : error ? (
        <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider', p: 4, textAlign: 'center' }}>
          <Typography variant="body2" color="text.disabled">Impossible de charger les alertes.</Typography>
        </Paper>

      ) : alertes.length === 0 ? (
        /* ── État vide — vert ── */
        <Paper
          elevation={0}
          sx={{
            border: '1px solid',
            borderColor: '#A5D6A7',
            backgroundColor: '#F1F8E9',
            p: { xs: 3, md: 4 },
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            gap: 1,
          }}
        >
          <CheckCircleOutlinedIcon sx={{ fontSize: 40, color: '#2E7D32' }} />
          <Typography variant="subtitle1" sx={{ fontWeight: 600, color: '#2E7D32' }}>
            Aucune alerte en cours ✓
          </Typography>
          <Typography variant="body2" color="text.secondary">
            Tous les sachets ont été traités dans les {DELAI_JOURS} derniers jours.
          </Typography>
        </Paper>

      ) : isMobile ? (
        /* ── VUE CARTES MOBILE ── */
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
          {alertes.map((a) => (
            <Card
              key={a.id}
              elevation={0}
              sx={{
                border: '1px solid #FFCDD2',
                borderLeft: '4px solid #D32F2F',
                borderRadius: 2,
              }}
            >
              <CardContent sx={{ pt: 1.5, pb: 1, px: 2 }}>
                <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', mb: 0.5 }}>
                  <Typography variant="body1" sx={{ fontWeight: 600, lineHeight: 1.3 }}>
                    {a.plant?.nomPlant ?? '—'}
                  </Typography>
                  <BadgeJours jours={a.joursAttente} />
                </Box>
                <Typography variant="caption" sx={{ fontFamily: '"DM Mono", monospace', color: 'text.secondary', display: 'block' }}>
                  {a.numeroLot ?? '—'}
                </Typography>
                <Typography variant="body2" color="text.secondary" sx={{ mt: 0.25 }}>
                  {a.client?.nom} {a.client?.prenom}
                </Typography>
                <Typography variant="caption" color="text.secondary">
                  Reçu le {formatDate(a.dateReception)} · {a.quantiteDeposee} unités déposées
                </Typography>
              </CardContent>
              <CardActions sx={{ pt: 0, px: 2, pb: 1.5 }}>
                <Button
                  fullWidth
                  variant="contained"
                  color="success"
                  startIcon={markingId === a.id ? <CircularProgress size={16} color="inherit" /> : <CheckIcon />}
                  disabled={markingId === a.id}
                  onClick={() => handleMarquer(a.id)}
                  sx={{ fontWeight: 600, py: 1 }}
                >
                  Marquer comme rangé
                </Button>
              </CardActions>
            </Card>
          ))}
        </Box>

      ) : (
        /* ── TABLEAU DESKTOP ── */
        <Box sx={{ overflowX: 'auto' }}>
          <TableContainer component={Paper} elevation={0} sx={{ border: '1px solid', borderColor: 'divider' }}>
            <Table size="small" sx={{ minWidth: 680 }}>
              <TableHead>
                <TableRow sx={{ backgroundColor: '#FFF8E1' }}>
                  <TableCell sx={{ fontWeight: 600 }}>Plante</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>N° de lot</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>Client</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>Date réception</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>Qté déposée</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>Délai</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>Action</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {alertes.map((a) => (
                  <TableRow
                    key={a.id}
                    hover
                    sx={{ '&:hover': { backgroundColor: '#FFF3E0' } }}
                  >
                    <TableCell sx={{ fontWeight: 500 }}>{a.plant?.nomPlant ?? '—'}</TableCell>
                    <TableCell sx={{ fontFamily: '"DM Mono", monospace', fontSize: '0.8rem' }}>
                      {a.numeroLot ?? '—'}
                    </TableCell>
                    <TableCell>{a.client?.nom} {a.client?.prenom}</TableCell>
                    <TableCell sx={{ fontFamily: '"DM Mono", monospace', fontSize: '0.8rem' }}>
                      {formatDate(a.dateReception)}
                    </TableCell>
                    <TableCell sx={{ fontFamily: '"DM Mono", monospace', fontSize: '0.8rem' }}>
                      {a.quantiteDeposee}
                    </TableCell>
                    <TableCell>
                      <BadgeJours jours={a.joursAttente} />
                    </TableCell>
                    <TableCell>
                      {isAdmin && (
                        <Button
                          size="small"
                          variant="outlined"
                          color="success"
                          startIcon={markingId === a.id ? <CircularProgress size={12} /> : <CheckIcon />}
                          disabled={markingId === a.id}
                          onClick={() => handleMarquer(a.id)}
                          sx={{ whiteSpace: 'nowrap', fontSize: '0.75rem' }}
                        >
                          Marquer comme rangé
                        </Button>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>
        </Box>
      )}
    </Box>
  );
}
