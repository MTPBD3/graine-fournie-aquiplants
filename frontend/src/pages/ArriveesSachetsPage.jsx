import { useState } from 'react';
import {
  Box, Typography, Paper, Table, TableHead, TableRow, TableCell, TableBody,
  Button, Dialog, DialogTitle, DialogContent, DialogActions, TextField,
  Select, MenuItem, FormControl, InputLabel, Alert, CircularProgress,
  IconButton, Chip, Divider,
} from '@mui/material';
import AddIcon from '@mui/icons-material/Add';
import InventoryIcon from '@mui/icons-material/Inventory';
import HistoryIcon from '@mui/icons-material/History';
import AgricultureIcon from '@mui/icons-material/Agriculture';
import CloseIcon from '@mui/icons-material/Close';
import { useApi, apiRequest } from '../hooks/useApi';
import { useAuth } from '../context/AuthContext';
import { formatDate } from '../utils/formatDate';

const STATUT_LABELS = { en_attente: 'En attente', en_stock: 'En stock', epuise: 'Épuisé' };
const STATUT_COLORS = { en_attente: '#E65100', en_stock: '#2E7D32', epuise: '#757575' };
const LETTRES = ['A', 'B', 'C', 'D'];
const ETAGES  = [1, 2, 3, 4, 5];

const EMPTY_SACHET = {
  referenceGf: '', numeroLot: '', quantiteDisponible: '', seuilAlerte: 0,
  dateReception: new Date().toISOString().slice(0, 10),
  idClient: '', idPlant: '', statut: 'en_attente',
};

export default function ArriveesSachetsPage() {
  const { token } = useAuth();
  const { data: sachets,      loading: lS, error: eS, refetch: refetchSachets }   = useApi('/api/histo-gf-deposees');
  const { data: clients,      loading: lC }                                         = useApi('/api/clients');
  const { data: plants,       loading: lP }                                         = useApi('/api/plants');
  const { data: emplacements, loading: lE, refetch: refetchEmplacements }           = useApi('/api/emplacements');

  const [newOpen,  setNewOpen]  = useState(false);
  const [form,     setForm]     = useState(EMPTY_SACHET);
  const [formErr,  setFormErr]  = useState('');
  const [saving,   setSaving]   = useState(false);

  const [rangerOpen,  setRangerOpen]  = useState(false);
  const [rangerSachet, setRangerSachet] = useState(null);
  const [rangerErr,    setRangerErr]   = useState('');
  const [rangerSaving, setRangerSaving] = useState(false);

  const [utiliserOpen,  setUtiliserOpen]  = useState(false);
  const [utiliserSachet, setUtiliserSachet] = useState(null);
  const [nbMottes,       setNbMottes]     = useState('');
  const [utiliserErr,    setUtiliserErr]  = useState('');
  const [utiliserSaving, setUtiliserSaving] = useState(false);

  const [histoOpen,  setHistoOpen]  = useState(false);
  const [histoSachet, setHistoSachet] = useState(null);
  const { data: histo, loading: lH } = useApi(
    histoSachet ? `/api/gf-histo-clients?idGfClient=${histoSachet.id}` : null,
    { skip: !histoSachet }
  );

  const handleFormChange = (e) => setForm(f => ({ ...f, [e.target.name]: e.target.value }));

  const handleCreate = async () => {
    setFormErr('');
    if (!form.numeroLot || !form.quantiteDisponible || !form.idClient || !form.idPlant) {
      setFormErr('Tous les champs obligatoires doivent être remplis.'); return;
    }
    setSaving(true);
    try {
      await apiRequest('/api/histo-gf-deposees', 'POST', {
        ...form,
        quantiteDisponible: Number(form.quantiteDisponible),
        seuilAlerte: Number(form.seuilAlerte),
        idClient: Number(form.idClient),
        idPlant:  Number(form.idPlant),
      }, token);
      refetchSachets();
      setNewOpen(false);
      setForm(EMPTY_SACHET);
    } catch (err) {
      setFormErr(err.message);
    } finally {
      setSaving(false);
    }
  };

  const openRanger = (sachet) => { setRangerSachet(sachet); setRangerErr(''); setRangerOpen(true); };
  const handleRanger = async (emplacementId) => {
    setRangerSaving(true); setRangerErr('');
    try {
      await apiRequest(`/api/emplacements/${emplacementId}/assigner`, 'POST', { idGfClient: rangerSachet.id }, token);
      refetchSachets(); refetchEmplacements();
      setRangerOpen(false);
    } catch (err) {
      setRangerErr(err.message);
    } finally {
      setRangerSaving(false);
    }
  };

  const openUtiliser = (sachet) => { setUtiliserSachet(sachet); setNbMottes(''); setUtiliserErr(''); setUtiliserOpen(true); };
  const handleUtiliser = async () => {
    const n = parseInt(nbMottes, 10);
    if (!n || n <= 0) { setUtiliserErr('Nombre de mottes invalide.'); return; }
    setUtiliserSaving(true); setUtiliserErr('');
    try {
      await apiRequest(`/api/gf-clients/${utiliserSachet.gfClient?.id ?? utiliserSachet.id}/utiliser`, 'POST', {
        nbMottes: n,
      }, token);
      refetchSachets();
      setUtiliserOpen(false);
    } catch (err) {
      setUtiliserErr(err.message);
    } finally {
      setUtiliserSaving(false);
    }
  };

  const openHisto = (sachet) => { setHistoSachet(sachet); setHistoOpen(true); };

  const emplacementsFree = Array.isArray(emplacements)
    ? emplacements.filter(e => !e.sachets || e.sachets.length === 0)
    : [];

  return (
    <Box>
      <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 2 }}>
        <Typography sx={{ fontWeight: 700, fontSize: { xs: '1.1rem', md: '1.5rem' } }}>
          Arrivées sachets
        </Typography>
        <Button variant="contained" startIcon={<AddIcon />} onClick={() => setNewOpen(true)} sx={{ bgcolor: '#1B5E20' }}>
          Nouvelle arrivée
        </Button>
      </Box>

      {eS && <Alert severity="error" sx={{ mb: 2 }}>{eS}</Alert>}

      <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider' }}>
        {lS && <Box sx={{ p: 3 }}><CircularProgress size={24} /></Box>}
        {!lS && Array.isArray(sachets) && (
          <Table size="small">
            <TableHead>
              <TableRow sx={{ bgcolor: '#F5F5F5' }}>
                <TableCell sx={{ fontWeight: 600 }}>N° de lot</TableCell>
                <TableCell sx={{ fontWeight: 600 }}>Plante</TableCell>
                <TableCell sx={{ fontWeight: 600 }}>Client</TableCell>
                <TableCell sx={{ fontWeight: 600 }}>Qté</TableCell>
                <TableCell sx={{ fontWeight: 600 }}>Date réception</TableCell>
                <TableCell sx={{ fontWeight: 600 }}>Statut</TableCell>
                <TableCell sx={{ fontWeight: 600 }}>Emplacement</TableCell>
                <TableCell />
              </TableRow>
            </TableHead>
            <TableBody>
              {sachets.map(s => (
                <TableRow key={s.id} hover>
                  <TableCell>{s.numeroLot ?? '—'}</TableCell>
                  <TableCell>{s.plant?.nomPlant ?? '—'}</TableCell>
                  <TableCell>{s.client?.prenomClient} {s.client?.nomClient}</TableCell>
                  <TableCell>{s.quantiteDisponible}</TableCell>
                  <TableCell>{formatDate(s.dateReception)}</TableCell>
                  <TableCell>
                    <Chip
                      label={STATUT_LABELS[s.statut] ?? s.statut} size="small"
                      sx={{ bgcolor: STATUT_COLORS[s.statut] ?? '#9E9E9E', color: '#fff' }}
                    />
                  </TableCell>
                  <TableCell>
                    {s.emplacement
                      ? `${s.emplacement.lettreEtagere}${s.emplacement.numeroEtage}`
                      : <Typography variant="caption" color="text.disabled">—</Typography>}
                  </TableCell>
                  <TableCell align="right">
                    <Box sx={{ display: 'flex', gap: 0.5, justifyContent: 'flex-end' }}>
                      {s.statut === 'en_attente' && (
                        <IconButton size="small" title="Ranger" onClick={() => openRanger(s)} sx={{ color: '#1B5E20' }}>
                          <InventoryIcon fontSize="small" />
                        </IconButton>
                      )}
                      {s.statut === 'en_stock' && (
                        <IconButton size="small" title="Utiliser" onClick={() => openUtiliser(s)} sx={{ color: '#1565C0' }}>
                          <AgricultureIcon fontSize="small" />
                        </IconButton>
                      )}
                      <IconButton size="small" title="Historique" onClick={() => openHisto(s)} sx={{ color: '#6D4C41' }}>
                        <HistoryIcon fontSize="small" />
                      </IconButton>
                    </Box>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </Paper>

      {/* Nouvelle arrivée */}
      <Dialog open={newOpen} onClose={() => setNewOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>Nouvelle arrivée de sachet</DialogTitle>
        <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2, pt: '16px !important' }}>
          {formErr && <Alert severity="error">{formErr}</Alert>}
          <TextField label="Référence GF" name="referenceGf" value={form.referenceGf} onChange={handleFormChange} fullWidth />
          <TextField label="N° de lot *"  name="numeroLot"   value={form.numeroLot}   onChange={handleFormChange} required fullWidth />
          <TextField label="Qté disponible *" name="quantiteDisponible" type="number" value={form.quantiteDisponible} onChange={handleFormChange} required fullWidth />
          <TextField label="Seuil alerte" name="seuilAlerte" type="number" value={form.seuilAlerte} onChange={handleFormChange} fullWidth />
          <TextField label="Date réception *" name="dateReception" type="date" value={form.dateReception} onChange={handleFormChange} required fullWidth InputLabelProps={{ shrink: true }} />
          <FormControl fullWidth required>
            <InputLabel>Client</InputLabel>
            <Select name="idClient" value={form.idClient} label="Client" onChange={handleFormChange}>
              {(Array.isArray(clients) ? clients : []).map(c => (
                <MenuItem key={c.id} value={c.id}>{c.prenomClient} {c.nomClient}</MenuItem>
              ))}
            </Select>
          </FormControl>
          <FormControl fullWidth required>
            <InputLabel>Plante</InputLabel>
            <Select name="idPlant" value={form.idPlant} label="Plante" onChange={handleFormChange}>
              {(Array.isArray(plants) ? plants : []).map(p => (
                <MenuItem key={p.id} value={p.id}>{p.nomPlant}</MenuItem>
              ))}
            </Select>
          </FormControl>
          <FormControl fullWidth>
            <InputLabel>Statut</InputLabel>
            <Select name="statut" value={form.statut} label="Statut" onChange={handleFormChange}>
              <MenuItem value="en_attente">En attente</MenuItem>
              <MenuItem value="en_stock">En stock</MenuItem>
            </Select>
          </FormControl>
        </DialogContent>
        <DialogActions sx={{ px: 3, pb: 2 }}>
          <Button onClick={() => setNewOpen(false)}>Annuler</Button>
          <Button variant="contained" onClick={handleCreate} disabled={saving} sx={{ bgcolor: '#1B5E20' }}>
            {saving ? <CircularProgress size={18} color="inherit" /> : 'Créer'}
          </Button>
        </DialogActions>
      </Dialog>

      {/* Ranger dans emplacement */}
      <Dialog open={rangerOpen} onClose={() => setRangerOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>
          <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            Choisir un emplacement
            <IconButton size="small" onClick={() => setRangerOpen(false)}><CloseIcon /></IconButton>
          </Box>
        </DialogTitle>
        <DialogContent>
          {rangerErr && <Alert severity="error" sx={{ mb: 2 }}>{rangerErr}</Alert>}
          {lE && <CircularProgress size={20} />}
          {!lE && (
            <Box sx={{ display: 'grid', gridTemplateColumns: `60px repeat(${ETAGES.length}, 1fr)`, gap: 1 }}>
              <Box />
              {ETAGES.map(e => (
                <Typography key={e} variant="caption" sx={{ textAlign: 'center', fontWeight: 600, color: 'text.secondary' }}>
                  {e}
                </Typography>
              ))}
              {LETTRES.map(lettre => (
                <>
                  <Box key={`l-${lettre}`} sx={{ display: 'flex', alignItems: 'center' }}>
                    <Typography variant="caption" sx={{ fontWeight: 700, color: '#1B5E20' }}>{lettre}</Typography>
                  </Box>
                  {ETAGES.map(etage => {
                    const emp = Array.isArray(emplacements)
                      ? emplacements.find(e => e.lettreEtagere === lettre && e.numeroEtage === etage)
                      : null;
                    const isFree = !emp || !emp.sachets || emp.sachets.length === 0;
                    return (
                      <Box
                        key={`${lettre}${etage}`}
                        onClick={() => isFree && emp && handleRanger(emp.id)}
                        sx={{
                          minHeight: 44, border: '1px solid', borderRadius: 1,
                          borderColor: isFree ? '#A5D6A7' : '#BDBDBD',
                          bgcolor: isFree ? '#F1F8E9' : '#F5F5F5',
                          cursor: isFree && emp ? 'pointer' : 'default',
                          display: 'flex', alignItems: 'center', justifyContent: 'center',
                          '&:hover': isFree && emp ? { bgcolor: '#C8E6C9' } : {},
                        }}
                      >
                        {isFree
                          ? <Typography variant="caption" color="success.main">Libre</Typography>
                          : <Typography variant="caption" color="text.disabled">Occupé</Typography>}
                      </Box>
                    );
                  })}
                </>
              ))}
            </Box>
          )}
        </DialogContent>
      </Dialog>

      {/* Utiliser sachet */}
      <Dialog open={utiliserOpen} onClose={() => setUtiliserOpen(false)} maxWidth="xs" fullWidth>
        <DialogTitle>Utiliser le sachet</DialogTitle>
        <DialogContent sx={{ pt: '16px !important' }}>
          {utiliserErr && <Alert severity="error" sx={{ mb: 2 }}>{utiliserErr}</Alert>}
          {utiliserSachet && (
            <Box sx={{ mb: 2 }}>
              <Typography variant="body2"><strong>Plante :</strong> {utiliserSachet.plant?.nomPlant ?? '—'}</Typography>
              <Typography variant="body2"><strong>N° lot :</strong> {utiliserSachet.numeroLot ?? '—'}</Typography>
              <Typography variant="body2"><strong>Qté dispo :</strong> {utiliserSachet.quantiteDisponible}</Typography>
            </Box>
          )}
          <TextField
            label="Nombre de mottes à semer" type="number" value={nbMottes}
            onChange={e => setNbMottes(e.target.value)} fullWidth autoFocus
            inputProps={{ min: 1 }}
          />
        </DialogContent>
        <DialogActions sx={{ px: 3, pb: 2 }}>
          <Button onClick={() => setUtiliserOpen(false)}>Annuler</Button>
          <Button variant="contained" onClick={handleUtiliser} disabled={utiliserSaving} sx={{ bgcolor: '#1565C0' }}>
            {utiliserSaving ? <CircularProgress size={18} color="inherit" /> : 'Confirmer'}
          </Button>
        </DialogActions>
      </Dialog>

      {/* Historique */}
      <Dialog open={histoOpen} onClose={() => setHistoOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>
          <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            Historique d'utilisation
            <IconButton size="small" onClick={() => setHistoOpen(false)}><CloseIcon /></IconButton>
          </Box>
        </DialogTitle>
        <DialogContent>
          {histoSachet && (
            <Box sx={{ mb: 2 }}>
              <Typography variant="body2" color="text.secondary">
                {histoSachet.plant?.nomPlant ?? '—'} — N° lot {histoSachet.numeroLot ?? '—'}
              </Typography>
            </Box>
          )}
          <Divider sx={{ mb: 1 }} />
          {lH && <CircularProgress size={20} />}
          {!lH && Array.isArray(histo) && histo.length === 0 && (
            <Typography variant="body2" color="text.secondary">Aucun historique.</Typography>
          )}
          {!lH && Array.isArray(histo) && histo.length > 0 && (
            <Table size="small">
              <TableHead>
                <TableRow>
                  <TableCell sx={{ fontWeight: 600 }}>Date semis</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>Qté semée</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>Mottes/graines</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>UV</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {histo.map(h => (
                  <TableRow key={h.id}>
                    <TableCell>{formatDate(h.dateSemis)}</TableCell>
                    <TableCell>{h.quantiteSemee}</TableCell>
                    <TableCell>{h.nbGraineParMotte}</TableCell>
                    <TableCell>{h.nomUv ?? '—'}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </DialogContent>
      </Dialog>
    </Box>
  );
}
