import { useState, useEffect } from 'react';
import {
  Box, Typography, Paper, Table, TableBody, TableCell, TableContainer,
  TableHead, TableRow, Chip, Button, ToggleButton, ToggleButtonGroup,
  Dialog, DialogTitle, DialogContent, DialogActions, TextField, MenuItem,
  Stepper, Step, StepLabel, Divider, IconButton, CircularProgress,
  Card, CardContent, CardActions, useMediaQuery, useTheme, Alert,
  Select, InputLabel, FormControl, FormHelperText, Tooltip, Snackbar,
} from '@mui/material';
import { sanitize } from '../utils/sanitize';
import { formatDate } from '../utils/formatDate';
import AddIcon from '@mui/icons-material/Add';
import CloseIcon from '@mui/icons-material/Close';
import CheckIcon from '@mui/icons-material/Check';
import ContentCutIcon from '@mui/icons-material/ContentCut';
import HistoryIcon from '@mui/icons-material/History';
import { useApi, apiRequest } from '../hooks/useApi';
import { useAuth } from '../context/AuthContext';

const DRAFT_KEY = 'gf_sachet_draft';

const emptyForm = {
  idClient: '',
  idPlant: '',
  numeroLot: '',
  quantiteDisponible: '',
};

const STEPS = ['Saisie des informations', 'Confirmation'];

// ── Mini-modal générique ──────────────────────────────────────────────────────
function QuickCreateDialog({ open, onClose, title, fields, onSubmit }) {
  const [values, setValues] = useState({});
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (open) { setValues({}); setError(''); }
  }, [open]);

  const handleSubmit = async () => {
    setSaving(true);
    setError('');
    try {
      await onSubmit(values);
      onClose();
    } catch (err) {
      setError(err.message || 'Erreur lors de la création.');
    } finally {
      setSaving(false);
    }
  };

  const allFilled = fields.every((f) => (values[f.name] ?? '').trim() !== '');

  return (
    <Dialog open={open} onClose={onClose} maxWidth="xs" fullWidth>
      <DialogTitle sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', pb: 1 }}>
        {title}
        <IconButton onClick={onClose} size="small"><CloseIcon /></IconButton>
      </DialogTitle>
      <Divider />
      <DialogContent sx={{ pt: 2 }}>
        {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
          {fields.map((f) => (
            <TextField
              key={f.name}
              label={f.label}
              type={f.type ?? 'text'}
              value={values[f.name] ?? ''}
              onChange={(e) => setValues((v) => ({ ...v, [f.name]: e.target.value }))}
              fullWidth
              size="small"
              required
              inputProps={f.type === 'number' ? { min: 1 } : undefined}
            />
          ))}
        </Box>
      </DialogContent>
      <Divider />
      <DialogActions sx={{ px: 2, py: 1.5 }}>
        <Button onClick={onClose} color="inherit">Annuler</Button>
        <Button
          variant="contained"
          onClick={handleSubmit}
          disabled={saving || !allFilled}
          startIcon={saving ? <CircularProgress size={14} color="inherit" /> : undefined}
        >
          Créer
        </Button>
      </DialogActions>
    </Dialog>
  );
}

// ── Page principale ───────────────────────────────────────────────────────────
export default function ArriveesSachetsPage() {
  const { token, user } = useAuth();
  const isAdmin = user?.roles?.includes('ROLE_ADMIN') ?? false;
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));

  const { data: sachets, loading, error, refetch } = useApi('/api/gf-clients');
  const { data: clientsData, refetch: refetchClients } = useApi('/api/clients');
  const { data: plantsData, refetch: refetchPlants } = useApi('/api/plants');
  const { data: uvsData, refetch: refetchUvs } = useApi('/api/uvs');

  // ── State (tous les useState AVANT les useEffect) ─────────────────────────
  const [filtre, setFiltre] = useState('tous');
  const [modalOpen, setModalOpen] = useState(false);
  const [step, setStep] = useState(0);
  const [form, setForm] = useState(() => {
    try {
      const saved = localStorage.getItem(DRAFT_KEY);
      if (saved) return { ...emptyForm, ...JSON.parse(saved) };
    } catch {}
    return emptyForm;
  });
  const [formErrors, setFormErrors] = useState({});
  const [saving, setSaving] = useState(false);
  const [saveError, setSaveError] = useState('');
  const [snackOpen, setSnackOpen] = useState(false);

  // Modal "Ranger" — sélection emplacement
  const [rangerModalOpen,    setRangerModalOpen]    = useState(false);
  const [rangerSachet,       setRangerSachet]       = useState(null);
  const [rangerEmplacements, setRangerEmplacements] = useState({}); // map code → emplacement
  const [rangerLoading,      setRangerLoading]      = useState(false);
  const [rangerSaving,       setRangerSaving]       = useState(false);
  const [rangerError,        setRangerError]        = useState('');
  const [rangerCell,         setRangerCell]         = useState(null); // { lettreEtagere, numeroEtage, emplacementId? }
  const [rangerPending,      setRangerPending]      = useState(null); // case occupée en attente de confirmation

  // Modal "Utiliser"
  const [utilisModalOpen,  setUtilisModalOpen]  = useState(false);
  const [utilisSachet,     setUtilisSachet]     = useState(null);
  const [utilisForm,       setUtilisForm]       = useState({ nbMottes: '', idUv: '', nbGraineParMotte: '' });
  const [utilisSaving,     setUtilisSaving]     = useState(false);
  const [utilisError,      setUtilisError]      = useState('');
  // Multi-sachet overflow flow
  const [compatibles,         setCompatibles]         = useState([]);
  const [compatiblesLoading,  setCompatiblesLoading]  = useState(false);
  const [distribution,        setDistribution]        = useState([]);
  // 'simple' | 'loading' | 'distribuer' | 'insufficient' | 'no_compatible'
  const [utilisFlowState,     setUtilisFlowState]     = useState('simple');

  // Modal "Historique"
  const [histoModalOpen, setHistoModalOpen] = useState(false);
  const [histoSachet,    setHistoSachet]    = useState(null);
  const [histoData,      setHistoData]      = useState([]);
  const [histoLoading,   setHistoLoading]   = useState(false);

  // Mini-modals
  const [clientDialogOpen, setClientDialogOpen] = useState(false);
  const [plantDialogOpen,  setPlantDialogOpen]  = useState(false);
  const [uvDialogOpen,     setUvDialogOpen]     = useState(false);

  // Debug — structure complète des réponses API
  useEffect(() => {
    if (sachets !== null) {
      console.log('[ArriveesSachets] /api/gf-clients (brut) →', sachets);
      if (Array.isArray(sachets) && sachets.length > 0) {
        console.log('[ArriveesSachets] exemple sachet[0] →', sachets[0]);
        console.log('[ArriveesSachets] statuts présents →', [...new Set(sachets.map((s) => s.statut))]);
        console.log('[ArriveesSachets] histoDepotId présent sur [0] ?', sachets[0].histoDepotId);
      }
    }
  }, [sachets]);

  useEffect(() => {
    console.log('[ArriveesSachets] /api/clients →', clientsData);
    console.log('[ArriveesSachets] /api/plants →', plantsData);
  }, [clientsData, plantsData]);

  // Persistance du brouillon formulaire
  useEffect(() => {
    localStorage.setItem(DRAFT_KEY, JSON.stringify(form));
  }, [form]);

  // Multi-sachet : détection overflow et calcul de distribution (basé sur totalGraines)
  useEffect(() => {
    const nbMottes    = Number(utilisForm.nbMottes);
    const nbGpm       = Number(utilisForm.nbGraineParMotte);
    const totalGraines = nbMottes * nbGpm;
    const stock = utilisSachet?.quantiteDisponible ?? 0;

    if (!utilisSachet || !totalGraines || totalGraines <= stock) {
      setUtilisFlowState('simple');
      setCompatibles([]);
      setDistribution([]);
      return;
    }

    setUtilisFlowState('loading');
    setCompatiblesLoading(true);

    apiRequest(`/api/gf-clients/${utilisSachet.id}/sachets-compatibles`, 'GET', null, token)
      .then((res) => {
        const list = Array.isArray(res) ? res : [];
        setCompatibles(list);
        const needed = totalGraines - stock;
        const totalCompatible = list.reduce((acc, c) => acc + c.quantiteDisponible, 0);

        if (list.length === 0) {
          setUtilisFlowState('no_compatible');
          setDistribution([]);
        } else if (totalCompatible < needed) {
          setUtilisFlowState('insufficient');
          setDistribution([]);
        } else {
          setUtilisFlowState('distribuer');
          const dist = [{
            idGfClient: utilisSachet.id,
            numeroLot: utilisSachet.numeroLot,
            qteDispo: stock,
            quantite: stock,
            isPrimary: true,
            emplacement: null,
          }];
          let remaining = needed;
          for (const c of list) {
            if (remaining <= 0) break;
            const take = Math.min(c.quantiteDisponible, remaining);
            dist.push({
              idGfClient: c.id,
              numeroLot: c.numeroLot,
              qteDispo: c.quantiteDisponible,
              quantite: take,
              isPrimary: false,
              emplacement: c.emplacement,
            });
            remaining -= take;
          }
          setDistribution(dist);
        }
      })
      .catch(() => {
        setUtilisFlowState('no_compatible');
        setCompatibles([]);
        setDistribution([]);
      })
      .finally(() => setCompatiblesLoading(false));
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [utilisForm.nbMottes, utilisForm.nbGraineParMotte, utilisSachet?.id]);

  const isPositiveInt = (val) => {
    if (val === '' || val === null || val === undefined) return false;
    return /^\d+$/.test(String(val).trim()) && Number(val) > 0;
  };

  const validateForm = (f) => {
    const errs = {};
    if (!f.idClient) errs.idClient = 'Veuillez sélectionner un client.';
    if (!f.idPlant)  errs.idPlant  = 'Veuillez sélectionner une plante.';
    const lot = f.numeroLot.trim();
    if (!lot) errs.numeroLot = 'Le numéro de lot est obligatoire.';
    else if (lot.length > 50) errs.numeroLot = 'Max 50 caractères.';
    if (!isPositiveInt(f.quantiteDisponible))
      errs.quantiteDisponible = 'Veuillez entrer un nombre entier positif.';
    return errs;
  };

  const formIsValid = Object.keys(validateForm(form)).length === 0;

  const clientsList = Array.isArray(clientsData) ? clientsData : [];
  const plantsList  = Array.isArray(plantsData)  ? plantsData  : [];
  const uvsList     = Array.isArray(uvsData)      ? uvsData     : [];

  const allSachets = Array.isArray(sachets) ? sachets : [];
  const filtered = filtre === 'tous'
    ? allSachets
    : allSachets.filter((s) => s.statut === filtre);

  const selectedClient = clientsList.find((c) => c.id === Number(form.idClient));
  const selectedPlant = plantsList.find((p) => p.id === Number(form.idPlant));

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm((f) => ({ ...f, [name]: value }));
    setFormErrors((errs) => { const next = { ...errs }; delete next[name]; return next; });
  };

  const handleOpen = () => {
    setStep(0);
    setSaveError('');
    setFormErrors({});
    setModalOpen(true);
  };

  const handleClearDraft = () => {
    setForm(emptyForm);
    setFormErrors({});
    localStorage.removeItem(DRAFT_KEY);
  };

  const handleConfirm = async () => {
    setSaving(true);
    setSaveError('');
    try {
      await apiRequest('/api/gf-clients', 'POST', {
        idClient: Number(form.idClient),
        idPlant: Number(form.idPlant),
        numeroLot: sanitize(form.numeroLot),
        quantiteDisponible: Number(form.quantiteDisponible) || 0,
        seuilAlerte: 0,
        nomClient: sanitize(selectedClient?.nom ?? ''),
      }, token);
      localStorage.removeItem(DRAFT_KEY);
      setModalOpen(false);
      refetch();
    } catch (err) {
      setSaveError(err.message);
      setStep(0);
    } finally {
      setSaving(false);
    }
  };

  const ETAGERES_MODAL = ['A', 'B', 'C', 'D'];
  const ETAGES_MODAL   = [1, 2, 3, 4];

  const handleOpenRanger = async (sachet) => {
    setRangerSachet(sachet);
    setRangerCell(null);
    setRangerPending(null);
    setRangerError('');
    setRangerModalOpen(true);
    setRangerLoading(true);
    try {
      const res = await apiRequest('/api/emplacements', 'GET', null, token);
      const map = {};
      if (Array.isArray(res)) res.forEach((e) => { map[e.code] = e; });
      setRangerEmplacements(map);
    } catch {
      setRangerEmplacements({});
    } finally {
      setRangerLoading(false);
    }
  };

  const handleAssigner = async () => {
    if (!rangerCell) return;
    setRangerSaving(true);
    setRangerError('');
    try {
      if (rangerCell.emplacementId) {
        // Emplacement existant — ajouter le sachet sans retirer les autres
        await apiRequest(`/api/emplacements/${rangerCell.emplacementId}/assigner`, 'POST', {
          idGfClient: rangerSachet.id,
        }, token);
      } else {
        // Emplacement libre — créer ou réutiliser
        await apiRequest('/api/emplacements/assigner', 'POST', {
          idGfClient: rangerSachet.id,
          lettreEtagere: rangerCell.lettreEtagere,
          numeroEtage: rangerCell.numeroEtage,
        }, token);
      }
      setRangerModalOpen(false);
      refetch();
      setSnackOpen(true);
    } catch (err) {
      setRangerError(err.message || 'Erreur lors du rangement.');
    } finally {
      setRangerSaving(false);
    }
  };

  // ── Handlers "Utiliser" ──────────────────────────────────────────────────
  const handleOpenUtiliser = (sachet) => {
    setUtilisSachet(sachet);
    setUtilisForm({ nbMottes: '', idUv: '', nbGraineParMotte: '' });
    setUtilisError('');
    setUtilisFlowState('simple');
    setCompatibles([]);
    setDistribution([]);
    setUtilisModalOpen(true);
  };

  const handleUtilisFormChange = (e) => {
    const { name, value } = e.target;
    setUtilisForm((f) => {
      const next = { ...f, [name]: value };
      // Auto-remplissage nbGraineParMotte quand on choisit l'UV
      if (name === 'idUv') {
        const uv = uvsList.find((u) => u.id === Number(value));
        if (uv) next.nbGraineParMotte = String(uv.nbGraineParMotte);
      }
      return next;
    });
  };

  const handleDistributionChange = (idx, newVal) => {
    setDistribution((prev) => prev.map((row, i) =>
      i === idx ? { ...row, quantite: newVal === '' ? '' : Number(newVal) } : row
    ));
  };

  const handleUtiliserSubmit = async () => {
    const nb = Number(utilisForm.nbGraineParMotte) || 0;
    const totalGraines = Number(utilisForm.nbMottes) * nb;
    if (!utilisForm.nbMottes || !utilisForm.idUv) {
      setUtilisError('Nombre de mottes et UV sont obligatoires.');
      return;
    }
    if (!totalGraines) {
      setUtilisError('Le total de graines est nul. Vérifiez le nombre de mottes et l\'UV.');
      return;
    }
    setUtilisSaving(true);
    setUtilisError('');
    try {
      if (utilisFlowState === 'distribuer') {
        await apiRequest(`/api/gf-clients/${utilisSachet.id}/utiliser`, 'POST', {
          utilisations: distribution.map((r) => ({ idGfClient: r.idGfClient, quantite: Number(r.quantite) || 0 })),
          idUv: Number(utilisForm.idUv),
          nbGraineParMotte: nb,
        }, token);
      } else {
        await apiRequest(`/api/gf-clients/${utilisSachet.id}/utiliser`, 'POST', {
          quantiteUtilisee: totalGraines,
          idUv: Number(utilisForm.idUv),
          nbGraineParMotte: nb,
        }, token);
      }
      setUtilisModalOpen(false);
      refetch();
    } catch (err) {
      setUtilisError(err.message || 'Erreur lors de l\'utilisation.');
    } finally {
      setUtilisSaving(false);
    }
  };

  // ── Handlers "Historique" ────────────────────────────────────────────────
  const handleOpenHisto = async (sachet) => {
    setHistoSachet(sachet);
    setHistoData([]);
    setHistoModalOpen(true);
    setHistoLoading(true);
    try {
      const res = await apiRequest(`/api/gf-histo-clients?idGfClient=${sachet.id}`, 'GET', null, token);
      setHistoData(Array.isArray(res) ? res : []);
    } catch {
      setHistoData([]);
    } finally {
      setHistoLoading(false);
    }
  };

  // Création rapide client — POST puis auto-sélection
  const handleCreateClient = async (values) => {
    const res = await apiRequest('/api/clients', 'POST', {
      nom: sanitize(values.nom),
      prenom: sanitize(values.prenom),
    }, token);
    console.log('[QuickCreate] client créé →', res);
    await refetchClients();
    setForm((f) => ({ ...f, idClient: res.id }));
  };

  // Création rapide plante — POST puis auto-sélection
  const handleCreatePlant = async (values) => {
    const res = await apiRequest('/api/plants', 'POST', {
      nomPlant: sanitize(values.nomPlant),
      nomEspece: sanitize(values.nomEspece),
    }, token);
    console.log('[QuickCreate] plante créée →', res);
    await refetchPlants();
    setForm((f) => ({ ...f, idPlant: res.id }));
  };

  // Création rapide UV — POST puis auto-sélection + pré-remplissage nbGraineParMotte
  const handleCreateUv = async (values) => {
    const res = await apiRequest('/api/uvs', 'POST', {
      nomUv: sanitize(values.nomUv),
      nbGraineParMotte: Number(values.nbGraineParMotte),
    }, token);
    await refetchUvs();
    setUtilisForm((f) => ({
      ...f,
      idUv: res.id,
      nbGraineParMotte: String(res.nbGraineParMotte),
    }));
  };

  const statutLabel = (s) => {
    if (s.statut === 'en_stock') return 'Rangé';
    if (s.statut === 'epuise') return 'Épuisé';
    return 'À traiter';
  };
  const statutChipColor = (s) => {
    if (s.statut === 'en_stock') return 'success';
    if (s.statut === 'epuise') return 'error';
    return 'warning';
  };

  return (
    <Box>
      {/* Header */}
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2, flexWrap: 'wrap', gap: 1 }}>
        <Typography sx={{ fontWeight: 700, fontSize: { xs: '1.1rem', md: '1.5rem' } }}>
          Arrivées de sachets
        </Typography>
        <Button
          variant="contained"
          startIcon={<AddIcon />}
          onClick={handleOpen}
          fullWidth={isMobile}
          sx={{ py: isMobile ? 1.25 : undefined }}
        >
          Ajouter un sachet
        </Button>
      </Box>

      {/* Filtres */}
      <ToggleButtonGroup
        value={filtre}
        exclusive
        onChange={(_, v) => v && setFiltre(v)}
        size="small"
        sx={{ mb: 2 }}
      >
        <ToggleButton value="tous">Tous</ToggleButton>
        <ToggleButton value="en_attente">À traiter</ToggleButton>
        <ToggleButton value="en_stock">Rangé</ToggleButton>
        <ToggleButton value="epuise">Épuisé</ToggleButton>
      </ToggleButtonGroup>

      {loading ? (
        <Box sx={{ display: 'flex', justifyContent: 'center', pt: 6 }}><CircularProgress /></Box>
      ) : error ? (
        <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider', p: 4, textAlign: 'center' }}>
          <Typography variant="body2" color="text.disabled">Aucune donnée disponible</Typography>
        </Paper>
      ) : filtered.length === 0 ? (
        <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider', p: 4, textAlign: 'center' }}>
          <Typography variant="body2" color="text.disabled">Aucun sachet correspondant</Typography>
        </Paper>
      ) : isMobile ? (
        /* ── VUE CARTES MOBILE ── */
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
          {filtered.map((s) => (
            <Card
              key={s.id}
              elevation={0}
              sx={{
                border: '1px solid',
                borderColor: s.statut === 'en_stock' ? '#A5D6A7' : '#FFB74D',
                borderRadius: 2,
                borderLeft: `4px solid ${s.statut === 'en_stock' ? '#2E7D32' : '#FF8F00'}`,
              }}
            >
              <CardContent sx={{ pb: 1, pt: 1.5, px: 2 }}>
                <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', mb: 0.5 }}>
                  <Typography variant="body1" sx={{ fontWeight: 600, lineHeight: 1.3 }}>
                    {s.plant?.nomPlant ?? '—'}
                  </Typography>
                  <Chip
                    label={statutLabel(s)}
                    size="small"
                    color={statutChipColor(s)}
                    sx={{ fontSize: '0.7rem', ml: 1, flexShrink: 0 }}
                  />
                </Box>
                <Typography variant="caption" sx={{ fontFamily: '"DM Mono", monospace', color: 'text.secondary', display: 'block' }}>
                  {s.numeroLot ?? '—'}
                </Typography>
                <Typography variant="body2" color="text.secondary" sx={{ mt: 0.25 }}>
                  {s.client?.nom ?? '—'}
                </Typography>
              </CardContent>
              {s.statut === 'en_attente' && (
                <CardActions sx={{ pt: 0, px: 2, pb: 1.5 }}>
                  <Button
                    fullWidth
                    variant="contained"
                    color="success"
                    startIcon={<CheckIcon />}
                    onClick={() => handleOpenRanger(s)}
                    sx={{ fontWeight: 600, py: 1 }}
                  >
                    Marquer comme rangé
                  </Button>
                </CardActions>
              )}
              {s.statut === 'en_stock' && (
                <CardActions sx={{ pt: 0, px: 2, pb: 1.5, gap: 1 }}>
                  <Button
                    fullWidth
                    variant="contained"
                    startIcon={<ContentCutIcon />}
                    onClick={() => handleOpenUtiliser(s)}
                    sx={{ fontWeight: 600, py: 1, bgcolor: '#2E7D32', '&:hover': { bgcolor: '#1B5E20' } }}
                  >
                    Utiliser
                  </Button>
                  <Button
                    fullWidth
                    variant="outlined"
                    startIcon={<HistoryIcon />}
                    onClick={() => handleOpenHisto(s)}
                    sx={{ fontWeight: 600, py: 1 }}
                  >
                    Historique
                  </Button>
                </CardActions>
              )}
            </Card>
          ))}
        </Box>
      ) : (
        /* ── TABLEAU DESKTOP ── */
        <Box sx={{ overflowX: 'auto' }}>
          <TableContainer component={Paper} elevation={0} sx={{ border: '1px solid', borderColor: 'divider' }}>
            <Table size="small" sx={{ minWidth: 640 }}>
              <TableHead>
                <TableRow sx={{ backgroundColor: '#F7FAF3' }}>
                  <TableCell sx={{ fontWeight: 600 }}>N° de lot</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>Plante</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>Client</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>Qté disponible</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>Statut</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>Actions</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {filtered.map((s) => (
                  <TableRow key={s.id} hover>
                    <TableCell sx={{ fontFamily: '"DM Mono", monospace', fontSize: '0.8rem' }}>
                      {s.numeroLot ?? '—'}
                    </TableCell>
                    <TableCell>{s.plant?.nomPlant ?? '—'}</TableCell>
                    <TableCell>{s.client?.nom ?? '—'}</TableCell>
                    <TableCell sx={{ fontFamily: '"DM Mono", monospace', fontSize: '0.8rem' }}>
                      {s.quantiteDisponible ?? '—'}
                    </TableCell>
                    <TableCell>
                      <Chip label={statutLabel(s)} size="small" color={statutChipColor(s)} sx={{ fontSize: '0.75rem' }} />
                    </TableCell>
                    <TableCell sx={{ whiteSpace: 'nowrap' }}>
                      {s.statut === 'en_attente' && isAdmin && (
                        <Button
                          size="small"
                          variant="outlined"
                          color="success"
                          startIcon={<CheckIcon />}
                          onClick={() => handleOpenRanger(s)}
                          sx={{ fontSize: '0.75rem' }}
                        >
                          Marquer comme rangé
                        </Button>
                      )}
                      {s.statut === 'en_stock' && (
                        <Box sx={{ display: 'flex', gap: 0.5 }}>
                          <Tooltip title="Utiliser des graines">
                            <IconButton
                              size="small"
                              onClick={() => handleOpenUtiliser(s)}
                              sx={{ color: '#2E7D32' }}
                            >
                              <ContentCutIcon fontSize="small" />
                            </IconButton>
                          </Tooltip>
                          <Tooltip title="Voir l'historique d'utilisation">
                            <IconButton
                              size="small"
                              onClick={() => handleOpenHisto(s)}
                              color="primary"
                            >
                              <HistoryIcon fontSize="small" />
                            </IconButton>
                          </Tooltip>
                        </Box>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>
        </Box>
      )}

      {/* ── Modal ajout sachet ───────────────────────────────────────────── */}
      <Dialog
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        maxWidth="sm"
        fullWidth
        fullScreen={isMobile}
      >
        <DialogTitle sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          {step === 0 ? 'Ajouter un sachet' : 'Confirmez les informations'}
          <IconButton onClick={() => setModalOpen(false)} size="small"><CloseIcon /></IconButton>
        </DialogTitle>
        <Divider />
        <DialogContent sx={{ pt: 2 }}>
          <Stepper activeStep={step} sx={{ mb: 3 }}>
            {STEPS.map((label) => <Step key={label}><StepLabel>{label}</StepLabel></Step>)}
          </Stepper>

          {saveError && <Alert severity="error" sx={{ mb: 2 }}>{saveError}</Alert>}

          {step === 0 ? (
            <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>

              {/* Select Client + bouton "+" */}
              <Box sx={{ display: 'flex', gap: 1, alignItems: 'flex-start' }}>
                <FormControl fullWidth size="small" required error={!!formErrors.idClient}>
                  <InputLabel id="select-client-label">Client</InputLabel>
                  <Select
                    labelId="select-client-label"
                    name="idClient"
                    value={form.idClient}
                    label="Client"
                    onChange={handleChange}
                  >
                    {clientsList.length === 0 && (
                      <MenuItem disabled value="">
                        <em>Aucun client — créez-en un via +</em>
                      </MenuItem>
                    )}
                    {clientsList.map((c) => (
                      <MenuItem key={c.id} value={c.id}>
                        {c.nom} {c.prenom}
                      </MenuItem>
                    ))}
                  </Select>
                  {formErrors.idClient && <FormHelperText>{formErrors.idClient}</FormHelperText>}
                </FormControl>
                <Tooltip title="Créer un client">
                  <IconButton
                    color="primary"
                    onClick={() => setClientDialogOpen(true)}
                    sx={{
                      mt: 0.25,
                      border: '1px solid',
                      borderColor: 'primary.main',
                      borderRadius: 1,
                      width: 40,
                      height: 40,
                      flexShrink: 0,
                    }}
                  >
                    <AddIcon fontSize="small" />
                  </IconButton>
                </Tooltip>
              </Box>

              {/* Select Plante + bouton "+" */}
              <Box sx={{ display: 'flex', gap: 1, alignItems: 'flex-start' }}>
                <FormControl fullWidth size="small" required error={!!formErrors.idPlant}>
                  <InputLabel id="select-plant-label">Plante / espèce</InputLabel>
                  <Select
                    labelId="select-plant-label"
                    name="idPlant"
                    value={form.idPlant}
                    label="Plante / espèce"
                    onChange={handleChange}
                  >
                    {plantsList.length === 0 && (
                      <MenuItem disabled value="">
                        <em>Aucune plante — créez-en une via +</em>
                      </MenuItem>
                    )}
                    {plantsList.map((p) => (
                      <MenuItem key={p.id} value={p.id}>
                        {p.nomPlant}{p.nomEspece ? ` — ${p.nomEspece}` : ''}
                      </MenuItem>
                    ))}
                  </Select>
                  {formErrors.idPlant && <FormHelperText>{formErrors.idPlant}</FormHelperText>}
                </FormControl>
                <Tooltip title="Créer une plante">
                  <IconButton
                    color="primary"
                    onClick={() => setPlantDialogOpen(true)}
                    sx={{
                      mt: 0.25,
                      border: '1px solid',
                      borderColor: 'primary.main',
                      borderRadius: 1,
                      width: 40,
                      height: 40,
                      flexShrink: 0,
                    }}
                  >
                    <AddIcon fontSize="small" />
                  </IconButton>
                </Tooltip>
              </Box>

              <TextField
                label="Numéro de lot"
                name="numeroLot"
                value={form.numeroLot}
                onChange={handleChange}
                fullWidth
                size="small"
                error={!!formErrors.numeroLot}
                helperText={formErrors.numeroLot}
                inputProps={{ maxLength: 50 }}
              />
              <TextField
                label="Quantité disponible"
                name="quantiteDisponible"
                type="number"
                value={form.quantiteDisponible}
                onChange={handleChange}
                fullWidth
                size="small"
                error={!!formErrors.quantiteDisponible}
                helperText={formErrors.quantiteDisponible}
                inputProps={{ min: 1, step: 1 }}
              />
            </Box>
          ) : (
            <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
              {[
                { label: 'Client',        value: selectedClient ? `${selectedClient.nom} ${selectedClient.prenom}` : '—' },
                { label: 'Plante',        value: selectedPlant ? `${selectedPlant.nomPlant}${selectedPlant.nomEspece ? ` (${selectedPlant.nomEspece})` : ''}` : '—' },
                { label: 'Numéro de lot', value: form.numeroLot || '—' },
                { label: 'Quantité',      value: form.quantiteDisponible || '0' },
              ].map(({ label, value }) => (
                <Box key={label} sx={{ display: 'flex', gap: 1 }}>
                  <Typography variant="body2" sx={{ fontWeight: 600, minWidth: 130 }}>{label} :</Typography>
                  <Typography variant="body2" color="text.secondary">{value}</Typography>
                </Box>
              ))}
            </Box>
          )}
        </DialogContent>
        <Divider />
        <DialogActions sx={{ px: 3, py: 1.5, flexDirection: isMobile ? 'column' : 'row', gap: isMobile ? 1 : 0 }}>
          {step === 0 ? (
            <>
              <Button
                onClick={handleClearDraft}
                size="small"
                sx={{ mr: 'auto', color: 'text.disabled', fontSize: '0.75rem', textTransform: 'none' }}
              >
                Effacer le formulaire
              </Button>
              <Button onClick={() => setModalOpen(false)} color="inherit" fullWidth={isMobile}>Annuler</Button>
              <Button
                variant="contained"
                onClick={() => {
                  const errs = validateForm(form);
                  if (Object.keys(errs).length > 0) { setFormErrors(errs); return; }
                  setStep(1);
                }}
                disabled={!formIsValid}
                fullWidth={isMobile}
              >
                Suivant
              </Button>
            </>
          ) : (
            <>
              <Button onClick={() => setStep(0)} color="inherit" fullWidth={isMobile}>Retour</Button>
              <Button variant="contained" onClick={handleConfirm} disabled={saving} fullWidth={isMobile}>
                {saving ? <CircularProgress size={18} color="inherit" /> : 'Confirmer'}
              </Button>
            </>
          )}
        </DialogActions>
      </Dialog>

      {/* ── Mini-modal création client ───────────────────────────────────── */}
      <QuickCreateDialog
        open={clientDialogOpen}
        onClose={() => setClientDialogOpen(false)}
        title="Nouveau client"
        fields={[
          { name: 'nom',    label: 'Nom' },
          { name: 'prenom', label: 'Prénom' },
        ]}
        onSubmit={handleCreateClient}
      />

      {/* ── Mini-modal création plante ───────────────────────────────────── */}
      <QuickCreateDialog
        open={plantDialogOpen}
        onClose={() => setPlantDialogOpen(false)}
        title="Nouvelle plante"
        fields={[
          { name: 'nomPlant',  label: 'Nom de la plante' },
          { name: 'nomEspece', label: 'Nom de l\'espèce' },
        ]}
        onSubmit={handleCreatePlant}
      />

      {/* ── Mini-modal création UV ───────────────────────────────────────── */}
      <QuickCreateDialog
        open={uvDialogOpen}
        onClose={() => setUvDialogOpen(false)}
        title="Nouvelle UV"
        fields={[
          { name: 'nomUv',            label: 'Nom UV' },
          { name: 'nbGraineParMotte', label: 'Graines par motte', type: 'number' },
        ]}
        onSubmit={handleCreateUv}
      />

      {/* ── Modal "Utiliser des graines" ─────────────────────────────────── */}
      <Dialog
        open={utilisModalOpen}
        onClose={() => setUtilisModalOpen(false)}
        maxWidth="sm"
        fullWidth
        fullScreen={isMobile}
      >
        <DialogTitle sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          Utiliser des graines
          <IconButton onClick={() => setUtilisModalOpen(false)} size="small"><CloseIcon /></IconButton>
        </DialogTitle>
        <Divider />
        <DialogContent sx={{ pt: 2 }}>
          {utilisError && <Alert severity="error" sx={{ mb: 2 }}>{utilisError}</Alert>}

          {utilisSachet && (
            <Box sx={{ mb: 2, p: 1.5, bgcolor: '#F1F8E9', borderRadius: 1, border: '1px solid #A5D6A7' }}>
              <Typography variant="body2" sx={{ fontWeight: 600 }}>
                {utilisSachet.plant?.nomPlant ?? utilisSachet.numeroLot}
              </Typography>
              <Typography variant="caption" color="text.secondary">
                {utilisSachet.numeroLot} — Stock actuel : <strong>{utilisSachet.quantiteDisponible}</strong> unité{utilisSachet.quantiteDisponible !== 1 ? 's' : ''}
              </Typography>
            </Box>
          )}

          <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
            <TextField
              label="Nombre de mottes"
              name="nbMottes"
              type="number"
              value={utilisForm.nbMottes}
              onChange={handleUtilisFormChange}
              fullWidth
              size="small"
              required
              inputProps={{ min: 1 }}
            />

            {/* Bloc calcul automatique mottes → graines */}
            {Number(utilisForm.nbMottes) > 0 && (
              <Box sx={{ px: 1.5, py: 1.25, bgcolor: '#F1F8E9', borderRadius: 1, border: '1px solid #A5D6A7' }}>
                {utilisForm.nbGraineParMotte ? (
                  <>
                    <Typography variant="caption" sx={{ display: 'block', fontFamily: '"DM Mono", monospace', color: 'text.secondary' }}>
                      Nombre de mottes : {utilisForm.nbMottes}
                    </Typography>
                    <Typography variant="caption" sx={{ display: 'block', fontFamily: '"DM Mono", monospace', color: 'text.secondary' }}>
                      Graines par motte : {utilisForm.nbGraineParMotte}
                    </Typography>
                    <Divider sx={{ my: 0.75 }} />
                    <Typography variant="body2" sx={{ fontFamily: '"DM Mono", monospace', fontWeight: 700, color: '#1B5E20' }}>
                      Total graines prélevées : {Number(utilisForm.nbMottes) * Number(utilisForm.nbGraineParMotte)}
                    </Typography>
                  </>
                ) : (
                  <Typography variant="caption" sx={{ color: 'text.secondary', fontStyle: 'italic' }}>
                    Sélectionnez une UV pour calculer le total.
                  </Typography>
                )}
              </Box>
            )}

            <Box sx={{ display: 'flex', gap: 1, alignItems: 'flex-start' }}>
              <FormControl fullWidth size="small" required>
                <InputLabel>UV utilisée</InputLabel>
                <Select
                  name="idUv"
                  value={utilisForm.idUv}
                  label="UV utilisée"
                  onChange={handleUtilisFormChange}
                >
                  {uvsList.length === 0 && (
                    <MenuItem disabled value=""><em>Aucune UV — créez-en une via +</em></MenuItem>
                  )}
                  {uvsList.map((u) => (
                    <MenuItem key={u.id} value={u.id}>
                      {u.nomUv} ({u.nbGraineParMotte} gr/motte)
                    </MenuItem>
                  ))}
                </Select>
              </FormControl>
              <Tooltip title="Créer une UV">
                <IconButton
                  color="primary"
                  onClick={() => setUvDialogOpen(true)}
                  sx={{
                    mt: 0.25,
                    border: '1px solid',
                    borderColor: 'primary.main',
                    borderRadius: 1,
                    width: 40,
                    height: 40,
                    flexShrink: 0,
                  }}
                >
                  <AddIcon fontSize="small" />
                </IconButton>
              </Tooltip>
            </Box>

            <TextField
              label="Graines par motte"
              name="nbGraineParMotte"
              type="number"
              value={utilisForm.nbGraineParMotte}
              onChange={handleUtilisFormChange}
              fullWidth
              size="small"
              inputProps={{ min: 1 }}
              helperText="Pré-rempli depuis l'UV sélectionnée"
            />
          </Box>

          {/* ── États overflow ──────────────────────────────────────────── */}

          {utilisFlowState === 'loading' && (
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mt: 2 }}>
              <CircularProgress size={20} />
              <Typography variant="body2" color="text.secondary">
                Recherche de sachets compatibles…
              </Typography>
            </Box>
          )}

          {utilisFlowState === 'simple' && (() => {
            const tg = Number(utilisForm.nbMottes) * Number(utilisForm.nbGraineParMotte);
            return tg > 0 && tg >= (utilisSachet?.quantiteDisponible ?? Infinity);
          })() && (
            <Alert severity="warning" sx={{ mt: 2 }}>
              ⚠️ Cette action épuisera le sachet.
            </Alert>
          )}

          {utilisFlowState === 'no_compatible' && (
            <Alert
              severity="error"
              sx={{ mt: 2 }}
              action={
                <Button
                  size="small"
                  color="inherit"
                  onClick={() => {
                    const nbGpm = Number(utilisForm.nbGraineParMotte);
                    const dispo = utilisSachet?.quantiteDisponible ?? 0;
                    setUtilisForm((f) => ({
                      ...f,
                      nbMottes: nbGpm > 0 ? String(Math.floor(dispo / nbGpm)) : f.nbMottes,
                    }));
                  }}
                >
                  Utiliser le max ({utilisSachet?.quantiteDisponible} gr)
                </Button>
              }
            >
              Aucun autre sachet compatible disponible ({utilisSachet?.quantiteDisponible} gr dispo).
            </Alert>
          )}

          {utilisFlowState === 'insufficient' && (() => {
            const totalDispo = (utilisSachet?.quantiteDisponible ?? 0) + compatibles.reduce((acc, c) => acc + c.quantiteDisponible, 0);
            const totalGraines = Number(utilisForm.nbMottes) * Number(utilisForm.nbGraineParMotte);
            const manque = totalGraines - totalDispo;
            const nbGpm = Number(utilisForm.nbGraineParMotte);
            return (
              <Alert
                severity="error"
                sx={{ mt: 2 }}
                action={
                  <Button
                    size="small"
                    color="inherit"
                    onClick={() => setUtilisForm((f) => ({
                      ...f,
                      nbMottes: nbGpm > 0 ? String(Math.floor(totalDispo / nbGpm)) : f.nbMottes,
                    }))}
                  >
                    Utiliser le max ({totalDispo} gr)
                  </Button>
                }
              >
                Stock insuffisant — Total dispo : {totalDispo} gr, manque {manque} gr.
              </Alert>
            );
          })()}

          {utilisFlowState === 'distribuer' && (() => {
            const total = distribution.reduce((acc, r) => acc + (Number(r.quantite) || 0), 0);
            const target = Number(utilisForm.nbMottes) * Number(utilisForm.nbGraineParMotte);
            const ok = total === target;
            return (
              <Box sx={{ mt: 2 }}>
                <Typography variant="subtitle2" sx={{ fontWeight: 600, mb: 1 }}>
                  Répartition sur {distribution.length} sachet{distribution.length > 1 ? 's' : ''}
                </Typography>
                <Box sx={{ border: '1px solid', borderColor: 'divider', borderRadius: 1, overflow: 'hidden' }}>
                  {distribution.map((row, idx) => (
                    <Box
                      key={row.idGfClient}
                      sx={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 1,
                        p: 1,
                        bgcolor: row.isPrimary ? '#F1F8E9' : 'transparent',
                        borderBottom: idx < distribution.length - 1 ? '1px solid' : 'none',
                        borderColor: 'divider',
                      }}
                    >
                      <Box sx={{ flex: 1, minWidth: 0 }}>
                        <Typography
                          variant="body2"
                          sx={{ fontWeight: row.isPrimary ? 600 : 400, fontFamily: '"DM Mono", monospace', fontSize: '0.8rem' }}
                          noWrap
                        >
                          {row.numeroLot}
                          {row.isPrimary && (
                            <Chip label="principal" size="small" sx={{ ml: 0.75, height: 16, fontSize: '0.65rem' }} />
                          )}
                        </Typography>
                        <Typography variant="caption" color="text.secondary">
                          {row.qteDispo} dispo{row.emplacement ? ` · ${row.emplacement.code}` : ''}
                          {Number(row.quantite) >= row.qteDispo && (
                            <Chip label="épuisé" size="small" color="error" sx={{ ml: 0.5, height: 16, fontSize: '0.65rem' }} />
                          )}
                        </Typography>
                      </Box>
                      <TextField
                        type="number"
                        value={row.quantite}
                        onChange={(e) => handleDistributionChange(idx, e.target.value)}
                        size="small"
                        sx={{ width: 80 }}
                        inputProps={{ min: 0, max: row.qteDispo, style: { fontFamily: '"DM Mono", monospace', textAlign: 'center' } }}
                      />
                    </Box>
                  ))}
                </Box>
                <Box sx={{ display: 'flex', justifyContent: 'flex-end', mt: 1, gap: 1, alignItems: 'center' }}>
                  <Typography variant="caption" color={ok ? 'success.main' : 'error.main'} sx={{ fontFamily: '"DM Mono", monospace' }}>
                    {total} / {target}
                  </Typography>
                  {!ok && (
                    <Typography variant="caption" color="error.main">
                      ({total < target ? `manque ${target - total}` : `excès de ${total - target}`})
                    </Typography>
                  )}
                </Box>
              </Box>
            );
          })()}
        </DialogContent>
        <Divider />
        <DialogActions sx={{ px: 3, py: 1.5, flexDirection: isMobile ? 'column' : 'row', gap: isMobile ? 1 : 0 }}>
          <Button onClick={() => setUtilisModalOpen(false)} color="inherit" fullWidth={isMobile}>Annuler</Button>
          <Button
            variant="contained"
            onClick={handleUtiliserSubmit}
            disabled={
              utilisSaving ||
              !utilisForm.nbMottes ||
              !utilisForm.idUv ||
              !utilisForm.nbGraineParMotte ||
              utilisFlowState === 'loading' ||
              utilisFlowState === 'no_compatible' ||
              utilisFlowState === 'insufficient' ||
              (utilisFlowState === 'distribuer' &&
                distribution.reduce((acc, r) => acc + (Number(r.quantite) || 0), 0) !==
                  Number(utilisForm.nbMottes) * Number(utilisForm.nbGraineParMotte))
            }
            fullWidth={isMobile}
            sx={{ bgcolor: '#2E7D32', '&:hover': { bgcolor: '#1B5E20' } }}
          >
            {utilisSaving ? <CircularProgress size={18} color="inherit" /> : "Confirmer l'utilisation"}
          </Button>
        </DialogActions>
      </Dialog>

      {/* ── Modal "Historique d'utilisation" ─────────────────────────────── */}
      <Dialog
        open={histoModalOpen}
        onClose={() => setHistoModalOpen(false)}
        maxWidth="sm"
        fullWidth
        fullScreen={isMobile}
      >
        <DialogTitle sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Box>
            Historique d'utilisation
            {histoSachet && (
              <Typography variant="caption" color="text.secondary" sx={{ display: 'block' }}>
                {histoSachet.numeroLot} · {histoSachet.plant?.nomPlant}
              </Typography>
            )}
          </Box>
          <IconButton onClick={() => setHistoModalOpen(false)} size="small"><CloseIcon /></IconButton>
        </DialogTitle>
        <Divider />
        <DialogContent sx={{ pt: 2 }}>
          {histoLoading ? (
            <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}><CircularProgress /></Box>
          ) : histoData.length === 0 ? (
            <Typography variant="body2" color="text.disabled" sx={{ textAlign: 'center', py: 4 }}>
              Aucune utilisation enregistrée.
            </Typography>
          ) : (
            <TableContainer>
              <Table size="small">
                <TableHead>
                  <TableRow sx={{ backgroundColor: '#F7FAF3' }}>
                    <TableCell sx={{ fontWeight: 600 }}>Date</TableCell>
                    <TableCell sx={{ fontWeight: 600 }}>Qté semée</TableCell>
                    <TableCell sx={{ fontWeight: 600 }}>UV</TableCell>
                    <TableCell sx={{ fontWeight: 600 }}>Gr/motte</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {histoData.map((h) => (
                    <TableRow key={h.id} hover>
                      <TableCell sx={{ fontFamily: '"DM Mono", monospace', fontSize: '0.8rem' }}>
                        {formatDate(h.dateSemis)}
                      </TableCell>
                      <TableCell sx={{ fontFamily: '"DM Mono", monospace', fontSize: '0.8rem' }}>
                        {h.quantiteSemee}
                      </TableCell>
                      <TableCell>{h.nomUv}</TableCell>
                      <TableCell sx={{ fontFamily: '"DM Mono", monospace', fontSize: '0.8rem' }}>
                        {h.nbGraineParMotte}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TableContainer>
          )}
        </DialogContent>
        <DialogActions sx={{ px: 3, py: 1.5 }}>
          <Button onClick={() => setHistoModalOpen(false)} color="inherit">Fermer</Button>
        </DialogActions>
      </Dialog>

      {/* ── Modal "Choisir un emplacement" ───────────────────────────────── */}
      <Dialog
        open={rangerModalOpen}
        onClose={() => setRangerModalOpen(false)}
        maxWidth="sm"
        fullWidth
        fullScreen={isMobile}
      >
        <DialogTitle sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Box>
            Choisir un emplacement
            {rangerSachet && (
              <Typography variant="caption" color="text.secondary" sx={{ display: 'block' }}>
                {rangerSachet.numeroLot} · {rangerSachet.plant?.nomPlant}
              </Typography>
            )}
          </Box>
          <IconButton onClick={() => setRangerModalOpen(false)} size="small"><CloseIcon /></IconButton>
        </DialogTitle>
        <Divider />
        <DialogContent sx={{ pt: 2 }}>
          {rangerError && <Alert severity="error" sx={{ mb: 2 }}>{rangerError}</Alert>}
          {rangerLoading ? (
            <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}><CircularProgress /></Box>
          ) : (
            <>
              <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 1.5 }}>
                Cliquez sur une case pour la sélectionner. Les cases vertes sont déjà occupées.
              </Typography>
              <Box sx={{ display: 'flex', gap: 1.5, overflowX: 'auto', pb: 1 }}>
                {ETAGERES_MODAL.map((lettre) => (
                  <Box key={lettre} sx={{ minWidth: 80, flex: '0 0 80px' }}>
                    <Typography variant="caption" sx={{ fontWeight: 700, color: 'primary.dark', display: 'block', mb: 0.75, fontSize: '0.75rem' }}>
                      Étagère {lettre}
                    </Typography>
                    <Box sx={{ display: 'flex', flexDirection: 'column', gap: 0.75 }}>
                      {ETAGES_MODAL.map((etage) => {
                        const code      = `${lettre}-${etage}`;
                        const emp       = rangerEmplacements[code] ?? null;
                        const isOccupe  = !!emp;
                        const nbSachets = emp?.sachets?.length ?? 0;
                        const isSelected  = rangerCell?.lettreEtagere === lettre && rangerCell?.numeroEtage === etage;
                        const isPending   = rangerPending?.lettreEtagere === lettre && rangerPending?.numeroEtage === etage;

                        const handleClick = () => {
                          setRangerError('');
                          if (!isOccupe) {
                            setRangerCell({ lettreEtagere: lettre, numeroEtage: etage });
                            setRangerPending(null);
                          } else {
                            setRangerPending({ lettreEtagere: lettre, numeroEtage: etage, emplacementId: emp.id, nbSachets });
                            setRangerCell(null);
                          }
                        };

                        const borderColor = isSelected ? '#1565C0' : isPending ? '#E65100' : isOccupe ? '#A5D6A7' : '#BDBDBD';
                        const bgColor     = isSelected ? '#E3F2FD' : isPending ? '#FFF3E0' : isOccupe ? '#E8F5E9' : '#FAFAFA';
                        const textColor   = isSelected ? '#1565C0' : isPending ? '#E65100' : isOccupe ? '#388E3C' : '#757575';

                        return (
                          <Box
                            key={code}
                            onClick={handleClick}
                            sx={{
                              height: 48,
                              border: `2px solid ${borderColor}`,
                              borderRadius: 1,
                              backgroundColor: bgColor,
                              display: 'flex',
                              flexDirection: 'column',
                              alignItems: 'center',
                              justifyContent: 'center',
                              cursor: 'pointer',
                              transition: 'all 0.12s',
                              '&:hover': { borderColor: isOccupe ? '#E65100' : '#1565C0', filter: 'brightness(0.97)' },
                            }}
                          >
                            <Typography variant="caption" sx={{ fontFamily: '"DM Mono", monospace', fontWeight: 700, fontSize: '0.7rem', color: textColor }}>
                              {code}
                            </Typography>
                            <Typography variant="caption" sx={{ fontSize: '0.6rem', color: textColor }}>
                              {isOccupe ? `${nbSachets} sachet${nbSachets > 1 ? 's' : ''}` : 'Libre'}
                            </Typography>
                          </Box>
                        );
                      })}
                    </Box>
                  </Box>
                ))}
              </Box>

              {/* Zone de confirmation / sélection */}
              {rangerPending && (
                <Box sx={{ mt: 2, p: 1.5, bgcolor: '#FFF3E0', borderRadius: 1, border: '1px solid #FFCC02' }}>
                  <Typography variant="body2" sx={{ fontWeight: 600, mb: 1 }}>
                    Cet emplacement contient déjà {rangerPending.nbSachets} sachet{rangerPending.nbSachets > 1 ? 's' : ''}. Ajouter quand même ?
                  </Typography>
                  <Box sx={{ display: 'flex', gap: 1 }}>
                    <Button
                      size="small"
                      variant="contained"
                      color="warning"
                      onClick={() => {
                        setRangerCell(rangerPending);
                        setRangerPending(null);
                      }}
                    >
                      Ajouter
                    </Button>
                    <Button
                      size="small"
                      color="inherit"
                      onClick={() => setRangerPending(null)}
                    >
                      Choisir une autre case
                    </Button>
                  </Box>
                </Box>
              )}
              {rangerCell && (
                <Box sx={{ mt: 2, p: 1.5, bgcolor: rangerCell.emplacementId ? '#FFF3E0' : '#E3F2FD', borderRadius: 1, border: `1px solid ${rangerCell.emplacementId ? '#FFCC02' : '#90CAF9'}` }}>
                  <Typography variant="body2" sx={{ fontWeight: 600 }}>
                    {rangerCell.emplacementId
                      ? `Ajout à l'emplacement ${rangerCell.lettreEtagere}-${rangerCell.numeroEtage} (${rangerCell.nbSachets} sachet${rangerCell.nbSachets > 1 ? 's' : ''} existant${rangerCell.nbSachets > 1 ? 's' : ''})`
                      : `Emplacement sélectionné : ${rangerCell.lettreEtagere}-${rangerCell.numeroEtage}`}
                  </Typography>
                </Box>
              )}
            </>
          )}
        </DialogContent>
        <Divider />
        <DialogActions sx={{ px: 3, py: 1.5, flexDirection: isMobile ? 'column' : 'row', gap: isMobile ? 1 : 0 }}>
          <Button onClick={() => setRangerModalOpen(false)} color="inherit" fullWidth={isMobile}>Annuler</Button>
          <Button
            variant="contained"
            color="success"
            onClick={handleAssigner}
            disabled={rangerSaving || !rangerCell}
            fullWidth={isMobile}
            startIcon={rangerSaving ? <CircularProgress size={16} color="inherit" /> : <CheckIcon />}
          >
            Confirmer le rangement
          </Button>
        </DialogActions>
      </Dialog>

      {/* ── Snackbar confirmation ─────────────────────────────────────────── */}
      <Snackbar
        open={snackOpen}
        autoHideDuration={3000}
        onClose={() => setSnackOpen(false)}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'center' }}
      >
        <Alert
          onClose={() => setSnackOpen(false)}
          severity="success"
          variant="filled"
          sx={{ width: '100%' }}
        >
          Sachet marqué comme rangé ✓
        </Alert>
      </Snackbar>
    </Box>
  );
}
