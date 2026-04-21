import { useState } from 'react';
import {
  Box, Typography, Paper, Chip, CircularProgress, Alert, Button, Table,
  TableHead, TableRow, TableCell, TableBody,
} from '@mui/material';
import { useApi, apiRequest } from '../hooks/useApi';
import { useAuth } from '../context/AuthContext';
import { formatDate } from '../utils/formatDate';

export default function AlertesPage() {
  const { token } = useAuth();
  const { data: alertes, loading, error, refetch } = useApi('/api/alertes');
  const [saving, setSaving] = useState(null);
  const [saveError, setSaveError] = useState('');

  const marquerEnStock = async (id) => {
    setSaving(id); setSaveError('');
    try {
      await apiRequest(`/api/histo-gf-deposees/${id}`, 'PUT', { statut: 'en_stock' }, token);
      refetch();
    } catch (err) {
      setSaveError(err.message);
    } finally {
      setSaving(null);
    }
  };

  return (
    <Box>
      <Typography sx={{ fontWeight: 700, fontSize: { xs: '1.1rem', md: '1.5rem' }, mb: 2 }}>
        Alertes
      </Typography>

      {saveError && <Alert severity="error" sx={{ mb: 2 }}>{saveError}</Alert>}

      <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider' }}>
        {loading && <Box sx={{ p: 3 }}><CircularProgress size={24} /></Box>}
        {error && <Alert severity="error" sx={{ m: 2 }}>{error}</Alert>}

        {!loading && Array.isArray(alertes) && alertes.length === 0 && (
          <Box sx={{ p: 3 }}>
            <Typography variant="body2" color="text.secondary">Aucune alerte en attente.</Typography>
          </Box>
        )}

        {!loading && Array.isArray(alertes) && alertes.length > 0 && (
          <Table size="small">
            <TableHead>
              <TableRow sx={{ bgcolor: '#F5F5F5' }}>
                <TableCell sx={{ fontWeight: 600 }}>N° de lot</TableCell>
                <TableCell sx={{ fontWeight: 600 }}>Plante</TableCell>
                <TableCell sx={{ fontWeight: 600 }}>Client</TableCell>
                <TableCell sx={{ fontWeight: 600 }}>Date réception</TableCell>
                <TableCell sx={{ fontWeight: 600 }}>Statut</TableCell>
                <TableCell />
              </TableRow>
            </TableHead>
            <TableBody>
              {alertes.map(a => (
                <TableRow key={a.id} hover>
                  <TableCell>{a.numeroLot ?? '—'}</TableCell>
                  <TableCell>{a.plant?.nomPlant ?? '—'}</TableCell>
                  <TableCell>{a.client?.prenomClient} {a.client?.nomClient}</TableCell>
                  <TableCell>{formatDate(a.dateReception)}</TableCell>
                  <TableCell>
                    <Chip label="En attente" size="small" sx={{ bgcolor: '#E65100', color: '#fff' }} />
                  </TableCell>
                  <TableCell align="right">
                    <Button
                      size="small" variant="outlined" color="success"
                      disabled={saving === a.id}
                      onClick={() => marquerEnStock(a.id)}
                    >
                      Marquer en stock
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </Paper>
    </Box>
  );
}
