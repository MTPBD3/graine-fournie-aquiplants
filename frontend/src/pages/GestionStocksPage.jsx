import { useState } from 'react';
import {
  Box, Typography, Paper, Chip, Grid, CircularProgress,
  useMediaQuery, useTheme, Dialog, DialogTitle, DialogContent,
  DialogActions, Button, Divider, IconButton,
} from '@mui/material';
import CloseIcon from '@mui/icons-material/Close';
import ChevronLeftIcon from '@mui/icons-material/ChevronLeft';
import ChevronRightIcon from '@mui/icons-material/ChevronRight';
import { useApi } from '../hooks/useApi';

const ETAGERES = ['A', 'B', 'C', 'D'];
const ETAGES = [1, 2, 3, 4];

const COLORS = {
  occupe: { bg: '#E8F5E9', border: '#2E7D32', text: '#1B5E20' },
  vide:   { bg: '#F5F5F5', border: '#E0E0E0', text: '#9E9E9E' },
};

const STATUS_LABELS = {
  range:     'Rangé',
  a_traiter: 'À traiter',
};

function EmplacementCell({ id, sachets = [], onEmplacementClick }) {
  const isLibre = sachets.length === 0;
  const first = sachets[0] ?? null;
  const colors = isLibre ? COLORS.vide : COLORS.occupe;

  const clientLabel = first
    ? `${first.client?.prenom ?? ''} ${first.client?.nom?.[0] ?? ''}.`.trim()
    : null;

  return (
    <Box
      onClick={!isLibre ? () => onEmplacementClick(id, sachets) : undefined}
      sx={{
        position: 'relative',
        height: 72,
        border: `1.5px solid ${colors.border}`,
        borderRadius: 1.5,
        backgroundColor: colors.bg,
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        cursor: !isLibre ? 'pointer' : 'default',
        transition: 'box-shadow 0.15s',
        px: 0.5,
        '&:hover': !isLibre ? { boxShadow: '0 2px 8px rgba(0,0,0,0.12)' } : {},
      }}
    >
      {sachets.length > 1 && (
        <Chip
          label={sachets.length}
          size="small"
          sx={{
            position: 'absolute',
            top: 3,
            right: 3,
            height: 16,
            minWidth: 16,
            fontSize: '0.6rem',
            bgcolor: '#2E7D32',
            color: '#fff',
            '& .MuiChip-label': { px: 0.5 },
          }}
        />
      )}

      <Typography variant="caption" sx={{ fontFamily: '"DM Mono", monospace', fontWeight: 700, color: colors.text, fontSize: '0.68rem' }}>
        {id}
      </Typography>

      {isLibre ? (
        <Typography variant="caption" sx={{ color: colors.text, fontSize: '0.62rem' }}>Libre</Typography>
      ) : (
        <>
          <Typography variant="caption" sx={{ color: colors.text, fontSize: '0.62rem', textAlign: 'center', lineHeight: 1.2, mt: 0.25 }} noWrap>
            {clientLabel}
          </Typography>
          <Typography variant="caption" sx={{ color: colors.text, fontSize: '0.62rem', textAlign: 'center', lineHeight: 1.2 }} noWrap>
            {first.plant?.nomPlant ?? '—'}
          </Typography>
        </>
      )}
    </Box>
  );
}

export default function GestionStocksPage() {
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));

  const { data: emplacements, loading, error } = useApi('/api/emplacements');

  const [detailOpen, setDetailOpen] = useState(false);
  const [detailData, setDetailData] = useState(null); // { emplacementCode, sachets[] }
  const [modalIdx, setModalIdx] = useState(0);

  const emplacementsMap = {};
  if (Array.isArray(emplacements)) {
    emplacements.forEach((e) => { emplacementsMap[e.code] = e; });
  }

  const totalOccupes = Object.keys(emplacementsMap).length;

  const handleEmplacementClick = (emplacementCode, sachets) => {
    setDetailData({ emplacementCode, sachets });
    setModalIdx(0);
    setDetailOpen(true);
  };

  const currentSachet = detailData?.sachets?.[modalIdx] ?? null;
  const hasMany = (detailData?.sachets?.length ?? 0) > 1;

  const prevSachet = () => setModalIdx((i) => (i - 1 + detailData.sachets.length) % detailData.sachets.length);
  const nextSachet = () => setModalIdx((i) => (i + 1) % detailData.sachets.length);

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2, flexWrap: 'wrap', gap: 1 }}>
        <Typography sx={{ fontWeight: 700, fontSize: { xs: '1.1rem', md: '1.5rem' } }}>
          Gestion des stocks
        </Typography>
        {!loading && !error && (
          <Chip label={`${totalOccupes}/16 occupés`} color="primary" variant="outlined" size="small" />
        )}
      </Box>

      {/* Légende */}
      <Box sx={{ display: 'flex', gap: 2, mb: 2, flexWrap: 'wrap' }}>
        {[
          { label: 'Occupé', type: 'occupe' },
          { label: 'Libre',  type: 'vide' },
        ].map(({ label, type }) => (
          <Box key={type} sx={{ display: 'flex', alignItems: 'center', gap: 0.75 }}>
            <Box sx={{ width: 12, height: 12, borderRadius: 0.5, backgroundColor: COLORS[type].bg, border: `1.5px solid ${COLORS[type].border}` }} />
            <Typography variant="caption" color="text.secondary">{label}</Typography>
          </Box>
        ))}
      </Box>

      {loading ? (
        <Box sx={{ display: 'flex', justifyContent: 'center', pt: 6 }}><CircularProgress /></Box>
      ) : error ? (
        <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider', p: 4, textAlign: 'center' }}>
          <Typography variant="body2" color="text.disabled">Aucune donnée d'emplacement disponible</Typography>
        </Paper>
      ) : isMobile ? (
        /* ── VUE MOBILE : scroll horizontal ── */
        <Box sx={{ overflowX: 'auto', pb: 1 }}>
          <Box sx={{ display: 'flex', gap: 1.5, minWidth: `${ETAGERES.length * 160}px` }}>
            {ETAGERES.map((etagere) => (
              <Paper key={etagere} elevation={0} sx={{ border: '1px solid', borderColor: 'divider', p: 1.5, minWidth: 148, flex: '0 0 148px' }}>
                <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1, color: 'primary.dark', fontSize: '0.8rem' }}>
                  Étagère {etagere}
                </Typography>
                <Box sx={{ display: 'flex', flexDirection: 'column', gap: 0.75 }}>
                  {ETAGES.map((etage) => {
                    const id = `${etagere}-${etage}`;
                    const emp = emplacementsMap[id] ?? null;
                    return (
                      <Box key={id}>
                        <Typography variant="caption" color="text.secondary" sx={{ fontSize: '0.6rem', fontFamily: '"DM Mono", monospace' }}>
                          Étage {etage}
                        </Typography>
                        <EmplacementCell
                          id={id}
                          sachets={emp?.sachets ?? []}
                          onEmplacementClick={handleEmplacementClick}
                        />
                      </Box>
                    );
                  })}
                </Box>
              </Paper>
            ))}
          </Box>
        </Box>
      ) : (
        /* ── VUE DESKTOP : grille 4 colonnes ── */
        <Grid container spacing={2}>
          {ETAGERES.map((etagere) => (
            <Grid size={{ xs: 12, sm: 6, md: 3 }} key={etagere}>
              <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider', p: 2 }}>
                <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1.5, color: 'primary.dark' }}>
                  Étagère {etagere}
                </Typography>
                <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1 }}>
                  {ETAGES.map((etage) => {
                    const id = `${etagere}-${etage}`;
                    const emp = emplacementsMap[id] ?? null;
                    return (
                      <Box key={id}>
                        <Typography variant="caption" color="text.secondary" sx={{ fontSize: '0.65rem', fontFamily: '"DM Mono", monospace' }}>
                          Étage {etage}
                        </Typography>
                        <EmplacementCell
                          id={id}
                          sachets={emp?.sachets ?? []}
                          onEmplacementClick={handleEmplacementClick}
                        />
                      </Box>
                    );
                  })}
                </Box>
              </Paper>
            </Grid>
          ))}
        </Grid>
      )}

      {/* ── Modal détail sachet ─────────────────────────────────────────────── */}
      <Dialog open={detailOpen} onClose={() => setDetailOpen(false)} maxWidth="xs" fullWidth>
        <DialogTitle sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', pb: 1 }}>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.25, flex: 1 }}>
            {hasMany && (
              <IconButton size="small" onClick={prevSachet} sx={{ p: 0.25 }}>
                <ChevronLeftIcon fontSize="small" />
              </IconButton>
            )}
            <Box sx={{ flex: 1, minWidth: 0 }}>
              <Typography variant="subtitle1" sx={{ fontWeight: 700, lineHeight: 1.3 }}>
                Emplacement {detailData?.emplacementCode}
              </Typography>
              {currentSachet && (
                <Typography variant="caption" color="text.secondary" sx={{ display: 'block' }}>
                  {currentSachet.client?.prenom} {currentSachet.client?.nom?.[0]}.&nbsp;·&nbsp;N° lot {currentSachet.numeroLot}
                </Typography>
              )}
            </Box>
            {hasMany && (
              <IconButton size="small" onClick={nextSachet} sx={{ p: 0.25 }}>
                <ChevronRightIcon fontSize="small" />
              </IconButton>
            )}
          </Box>
          <IconButton onClick={() => setDetailOpen(false)} size="small" sx={{ ml: 0.5, mt: -0.25 }}>
            <CloseIcon />
          </IconButton>
        </DialogTitle>
        <Divider />
        {currentSachet && (
          <DialogContent sx={{ pt: 2 }}>
            <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
              {[
                { label: 'Plante',    value: currentSachet.plant?.nomPlant ?? '—' },
                { label: 'Client',    value: `${currentSachet.client?.prenom ?? ''} ${currentSachet.client?.nom ?? currentSachet.nomClient ?? ''}`.trim() },
                { label: 'N° de lot', value: currentSachet.numeroLot ?? '—' },
                { label: 'Qté dispo', value: `${currentSachet.quantiteDisponible} u.` },
                { label: 'Statut',    value: STATUS_LABELS[currentSachet.statut] ?? currentSachet.statut },
              ].map(({ label, value }) => (
                <Box key={label} sx={{ display: 'flex', gap: 1 }}>
                  <Typography variant="body2" sx={{ fontWeight: 600, minWidth: 110, color: 'text.secondary' }}>{label}</Typography>
                  <Typography variant="body2">{value}</Typography>
                </Box>
              ))}
            </Box>
            {hasMany && (
              <Typography variant="caption" color="text.secondary" sx={{ display: 'block', textAlign: 'center', mt: 2 }}>
                {modalIdx + 1}/{detailData.sachets.length}
              </Typography>
            )}
          </DialogContent>
        )}
        <Divider />
        <DialogActions sx={{ px: 2, py: 1.5 }}>
          <Button onClick={() => setDetailOpen(false)} color="inherit">Fermer</Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}
