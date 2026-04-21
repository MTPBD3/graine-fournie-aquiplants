import { useState } from 'react';
import {
  Box, Typography, Paper, CircularProgress, Alert, Chip, Dialog, DialogTitle,
  DialogContent, IconButton, Divider,
} from '@mui/material';
import ChevronLeftIcon from '@mui/icons-material/ChevronLeft';
import ChevronRightIcon from '@mui/icons-material/ChevronRight';
import CloseIcon from '@mui/icons-material/Close';
import { useApi } from '../hooks/useApi';
import { formatDate } from '../utils/formatDate';

const LETTRES = ['A', 'B', 'C', 'D'];
const ETAGES  = [1, 2, 3, 4, 5];

function EmplacementCell({ sachets = [], emplacementCode, onEmplacementClick }) {
  if (sachets.length === 0) {
    return (
      <Box sx={{
        minHeight: 72, border: '1px dashed #BDBDBD', borderRadius: 1,
        display: 'flex', alignItems: 'center', justifyContent: 'center',
        bgcolor: '#FAFAFA',
      }}>
        <Typography variant="caption" color="text.disabled">Libre</Typography>
      </Box>
    );
  }

  const first = sachets[0];
  const plantName = first.plant?.nomPlant ?? '—';
  const clientLabel = first.client
    ? `${first.client.prenomClient ?? ''} ${(first.client.nomClient ?? '')[0] ?? ''}.`
    : '—';

  return (
    <Box
      onClick={() => onEmplacementClick(emplacementCode, sachets)}
      sx={{
        minHeight: 72, border: '1px solid #A5D6A7', borderRadius: 1, p: 1,
        bgcolor: '#F1F8E9', cursor: 'pointer', position: 'relative',
        '&:hover': { bgcolor: '#E8F5E9', borderColor: '#66BB6A' },
      }}
    >
      <Typography variant="caption" sx={{ fontWeight: 600, display: 'block', lineHeight: 1.3 }}>
        {plantName}
      </Typography>
      <Typography variant="caption" color="text.secondary" sx={{ display: 'block' }}>
        {clientLabel}
      </Typography>
      {sachets.length > 1 && (
        <Chip
          label={`×${sachets.length}`} size="small"
          sx={{ position: 'absolute', top: 4, right: 4, height: 18, fontSize: '0.65rem', bgcolor: '#1B5E20', color: '#fff' }}
        />
      )}
    </Box>
  );
}

function DetailField({ label, value }) {
  return (
    <Box sx={{ display: 'flex', justifyContent: 'space-between', py: 0.75 }}>
      <Typography variant="body2" color="text.secondary">{label}</Typography>
      <Typography variant="body2" sx={{ fontWeight: 500 }}>{value ?? '—'}</Typography>
    </Box>
  );
}

export default function GestionStocksPage() {
  const { data: emplacements, loading, error } = useApi('/api/emplacements');
  const [detailData, setDetailData] = useState(null);
  const [modalIdx, setModalIdx]     = useState(0);

  const openModal = (emplacementCode, sachets) => {
    setDetailData({ emplacementCode, sachets });
    setModalIdx(0);
  };
  const closeModal = () => setDetailData(null);
  const prevSachet = () => setModalIdx(i => Math.max(0, i - 1));
  const nextSachet = () => setModalIdx(i => Math.min(detailData.sachets.length - 1, i + 1));

  const byCode = {};
  if (Array.isArray(emplacements)) {
    emplacements.forEach(e => {
      const code = `${e.lettreEtagere}${e.numeroEtage}`;
      byCode[code] = e.sachets ?? [];
    });
  }

  const currentSachet = detailData?.sachets?.[modalIdx];

  return (
    <Box>
      <Typography sx={{ fontWeight: 700, fontSize: { xs: '1.1rem', md: '1.5rem' }, mb: 2 }}>
        Gestion des stocks
      </Typography>

      {loading && <CircularProgress size={24} />}
      {error && <Alert severity="error">{error}</Alert>}

      {!loading && (
        <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider', p: 2, overflowX: 'auto' }}>
          <Box sx={{ display: 'grid', gridTemplateColumns: `80px repeat(${ETAGES.length}, 1fr)`, gap: 1, minWidth: 480 }}>
            <Box />
            {ETAGES.map(e => (
              <Typography key={e} variant="caption" sx={{ fontWeight: 600, textAlign: 'center', color: 'text.secondary' }}>
                Étage {e}
              </Typography>
            ))}
            {LETTRES.map(lettre => (
              <>
                <Box key={`label-${lettre}`} sx={{ display: 'flex', alignItems: 'center' }}>
                  <Typography variant="caption" sx={{ fontWeight: 700, color: '#1B5E20' }}>Rang {lettre}</Typography>
                </Box>
                {ETAGES.map(etage => {
                  const code = `${lettre}${etage}`;
                  return (
                    <EmplacementCell
                      key={code}
                      sachets={byCode[code] ?? []}
                      emplacementCode={code}
                      onEmplacementClick={openModal}
                    />
                  );
                })}
              </>
            ))}
          </Box>
        </Paper>
      )}

      <Dialog open={Boolean(detailData)} onClose={closeModal} maxWidth="xs" fullWidth>
        {detailData && currentSachet && (
          <>
            <DialogTitle sx={{ pb: 0 }}>
              <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
                  <IconButton size="small" onClick={prevSachet} disabled={modalIdx === 0}>
                    <ChevronLeftIcon />
                  </IconButton>
                  <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                    Emplacement {detailData.emplacementCode}
                  </Typography>
                  <IconButton size="small" onClick={nextSachet} disabled={modalIdx === detailData.sachets.length - 1}>
                    <ChevronRightIcon />
                  </IconButton>
                </Box>
                <IconButton size="small" onClick={closeModal}><CloseIcon /></IconButton>
              </Box>
              <Typography variant="caption" color="text.secondary">
                {currentSachet.client?.prenomClient ?? ''} {(currentSachet.client?.nomClient ?? '')[0] ?? ''}.
                {' '}· N° lot {currentSachet.numeroLot ?? '—'}
              </Typography>
            </DialogTitle>
            <DialogContent>
              <Divider sx={{ mb: 1 }} />
              <DetailField label="Plante"      value={currentSachet.plant?.nomPlant} />
              <DetailField label="Client"      value={`${currentSachet.client?.prenomClient ?? ''} ${currentSachet.client?.nomClient ?? ''}`} />
              <DetailField label="N° de lot"   value={currentSachet.numeroLot} />
              <DetailField label="Qté dispo"   value={currentSachet.quantiteDisponible} />
              <DetailField label="Statut"      value={currentSachet.statut} />
              <DetailField label="Reçu le"     value={formatDate(currentSachet.dateReception)} />
              {detailData.sachets.length > 1 && (
                <Typography variant="caption" color="text.secondary" sx={{ display: 'block', textAlign: 'center', mt: 1 }}>
                  {modalIdx + 1}/{detailData.sachets.length}
                </Typography>
              )}
            </DialogContent>
          </>
        )}
      </Dialog>
    </Box>
  );
}
