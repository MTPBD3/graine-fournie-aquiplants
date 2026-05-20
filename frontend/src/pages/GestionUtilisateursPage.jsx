import { useState } from 'react';
import {
  Box, Typography, Paper, Table, TableBody, TableCell, TableContainer,
  TableHead, TableRow, Chip, Button, IconButton, Dialog, DialogTitle,
  DialogContent, DialogActions, TextField, MenuItem, Divider, Avatar,
  Tooltip, CircularProgress, Alert, Card, CardContent, CardActions,
  useMediaQuery, useTheme, Tabs, Tab, Select, InputLabel, FormControl,
} from '@mui/material';
import AddIcon from '@mui/icons-material/Add';
import EditIcon from '@mui/icons-material/Edit';
import DeleteIcon from '@mui/icons-material/Delete';
import CloseIcon from '@mui/icons-material/Close';
import { useApi, apiRequest } from '../hooks/useApi';
import { useAuth } from '../context/AuthContext';
import { sanitize } from '../utils/sanitize';
import { formatDate } from '../utils/formatDate';

// ── Couleurs et labels des actions de log ─────────────────────────────────────
const ACTION_META = {
  connexion:              { label: 'Connexion',           color: '#1565C0', bg: '#E3F2FD' },
  ajout_sachet:           { label: 'Ajout sachet',        color: '#2E7D32', bg: '#E8F5E9' },
  modification_sachet:    { label: 'Modif. sachet',       color: '#FF8F00', bg: '#FFF8E1' },
  suppression_sachet:     { label: 'Suppression sachet',  color: '#E53935', bg: '#FFEBEE' },
  changement_statut:      { label: 'Rangement',           color: '#388E3C', bg: '#F1F8E9' },
  creation_utilisateur:   { label: 'Créa. utilisateur',   color: '#6A1B9A', bg: '#F3E5F5' },
  suppression_utilisateur:{ label: 'Suppr. utilisateur',  color: '#BF360C', bg: '#FBE9E7' },
  rangement_sachet:       { label: 'Rangement sachet',    color: '#2E7D32', bg: '#E8F5E9' },
  utilisation_sachet:     { label: 'Utilisation graines', color: '#FF8F00', bg: '#FFF8E1' },
};

function ActionBadge({ action }) {
  const meta = ACTION_META[action] ?? { label: action, color: '#546E7A', bg: '#ECEFF1' };
  return (
    <Chip
      label={meta.label}
      size="small"
      sx={{
        backgroundColor: meta.bg,
        color: meta.color,
        fontWeight: 600,
        fontSize: '0.72rem',
        border: `1px solid ${meta.color}30`,
      }}
    />
  );
}

const emptyForm = { email: '', prenom: '', nom: '', role: 'ROLE_EMPLOYE', motdepasse: '' };

// ── Onglet Utilisateurs ───────────────────────────────────────────────────────
function OngletUtilisateurs({ token, isMobile }) {
  const { data: users, loading, error, refetch } = useApi('/api/utilisateurs');

  const [modalOpen, setModalOpen]   = useState(false);
  const [editTarget, setEditTarget] = useState(null);
  const [form, setForm]             = useState(emptyForm);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [saving, setSaving]         = useState(false);
  const [saveError, setSaveError]   = useState('');

  const handleChange = (e) => setForm((f) => ({ ...f, [e.target.name]: e.target.value }));

  const handleCreate = () => {
    setEditTarget(null);
    setForm(emptyForm);
    setSaveError('');
    setModalOpen(true);
  };

  const handleEdit = (user) => {
    setEditTarget(user);
    setForm({ email: user.email, prenom: user.prenom ?? '', nom: user.nom ?? '', role: user.roles?.[0] ?? 'ROLE_EMPLOYE', motdepasse: '' });
    setSaveError('');
    setModalOpen(true);
  };

  const handleSave = async () => {
    setSaving(true);
    setSaveError('');
    try {
      const body = { email: sanitize(form.email), prenom: sanitize(form.prenom), nom: sanitize(form.nom), role: form.role };
      if (form.motdepasse) body.motdepasse = form.motdepasse.trim();
      if (editTarget) {
        await apiRequest(`/api/utilisateurs/${editTarget.id}`, 'PUT', body, token);
      } else {
        await apiRequest('/api/utilisateurs', 'POST', body, token);
      }
      setModalOpen(false);
      refetch();
    } catch (err) {
      setSaveError(err.message);
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async () => {
    try {
      await apiRequest(`/api/utilisateurs/${deleteTarget.id}`, 'DELETE', null, token);
      setDeleteTarget(null);
      refetch();
    } catch {
      setDeleteTarget(null);
    }
  };

  const roleLabel = (u) => u.roles?.includes('ROLE_ADMIN') ? 'Admin' : 'Employé';
  const roleColor = (u) => u.roles?.includes('ROLE_ADMIN') ? 'primary' : 'default';
  const initiales = (u) => (u.prenom?.[0] ?? u.email?.[0] ?? 'U').toUpperCase();

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'flex-end', mb: 2 }}>
        <Button
          variant="contained"
          startIcon={<AddIcon />}
          onClick={handleCreate}
          fullWidth={isMobile}
          sx={{ py: isMobile ? 1.25 : undefined }}
        >
          Nouvel utilisateur
        </Button>
      </Box>

      {loading ? (
        <Box sx={{ display: 'flex', justifyContent: 'center', pt: 6 }}><CircularProgress /></Box>
      ) : error || !users || users.length === 0 ? (
        <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider', p: 4, textAlign: 'center' }}>
          <Typography variant="body2" color="text.disabled">Aucun utilisateur disponible</Typography>
        </Paper>
      ) : isMobile ? (
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
          {users.map((u) => (
            <Card key={u.id} elevation={0} sx={{ border: '1px solid', borderColor: 'divider', borderRadius: 2 }}>
              <CardContent sx={{ pb: 1, pt: 1.5, px: 2 }}>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 0.75 }}>
                  <Avatar sx={{ width: 36, height: 36, bgcolor: 'primary.main', fontSize: '0.85rem' }}>
                    {initiales(u)}
                  </Avatar>
                  <Box sx={{ flexGrow: 1, minWidth: 0 }}>
                    <Typography variant="body1" sx={{ fontWeight: 600 }} noWrap>{u.prenom ?? ''} {u.nom ?? ''}</Typography>
                    <Typography variant="caption" sx={{ fontFamily: '"DM Mono", monospace', color: 'text.secondary' }} noWrap>{u.email}</Typography>
                  </Box>
                  <Chip label={roleLabel(u)} size="small" color={roleColor(u)} sx={{ fontSize: '0.7rem', flexShrink: 0 }} />
                </Box>
              </CardContent>
              <CardActions sx={{ px: 2, pb: 1.5, pt: 0, gap: 1 }}>
                <Button fullWidth variant="outlined" startIcon={<EditIcon />} size="small" onClick={() => handleEdit(u)}>Modifier</Button>
                <Button fullWidth variant="outlined" color="error" startIcon={<DeleteIcon />} size="small" onClick={() => setDeleteTarget(u)}>Supprimer</Button>
              </CardActions>
            </Card>
          ))}
        </Box>
      ) : (
        <Box sx={{ overflowX: 'auto' }}>
          <TableContainer component={Paper} elevation={0} sx={{ border: '1px solid', borderColor: 'divider' }}>
            <Table size="small">
              <TableHead>
                <TableRow sx={{ backgroundColor: '#F7FAF3' }}>
                  <TableCell sx={{ fontWeight: 600 }}>Utilisateur</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>Email</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>Rôle</TableCell>
                  <TableCell align="right" sx={{ fontWeight: 600 }}>Actions</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {users.map((u) => (
                  <TableRow key={u.id} hover>
                    <TableCell>
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                        <Avatar sx={{ width: 30, height: 30, bgcolor: 'primary.main', fontSize: '0.8rem' }}>{initiales(u)}</Avatar>
                        <Typography variant="body2">{u.prenom ?? ''} {u.nom ?? ''}</Typography>
                      </Box>
                    </TableCell>
                    <TableCell sx={{ fontFamily: '"DM Mono", monospace', fontSize: '0.8rem' }}>{u.email}</TableCell>
                    <TableCell><Chip label={roleLabel(u)} size="small" color={roleColor(u)} sx={{ fontSize: '0.75rem' }} /></TableCell>
                    <TableCell align="right">
                      <Tooltip title="Modifier"><IconButton size="small" onClick={() => handleEdit(u)}><EditIcon fontSize="small" /></IconButton></Tooltip>
                      <Tooltip title="Supprimer"><IconButton size="small" color="error" onClick={() => setDeleteTarget(u)}><DeleteIcon fontSize="small" /></IconButton></Tooltip>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>
        </Box>
      )}

      {/* Modal créer / modifier */}
      <Dialog open={modalOpen} onClose={() => setModalOpen(false)} maxWidth="sm" fullWidth fullScreen={isMobile}>
        <DialogTitle sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          {editTarget ? "Modifier l'utilisateur" : 'Nouvel utilisateur'}
          <IconButton onClick={() => setModalOpen(false)} size="small"><CloseIcon /></IconButton>
        </DialogTitle>
        <Divider />
        <DialogContent sx={{ pt: 2 }}>
          {saveError && <Alert severity="error" sx={{ mb: 2 }}>{saveError}</Alert>}
          <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
            <Box sx={{ display: 'flex', gap: 2 }}>
              <TextField label="Prénom" name="prenom" value={form.prenom} onChange={handleChange} fullWidth size="small" required />
              <TextField label="Nom" name="nom" value={form.nom} onChange={handleChange} fullWidth size="small" required />
            </Box>
            <TextField label="Email" name="email" type="email" value={form.email} onChange={handleChange} fullWidth size="small" required />
            <TextField select label="Rôle" name="role" value={form.role} onChange={handleChange} fullWidth size="small">
              <MenuItem value="ROLE_EMPLOYE">Employé</MenuItem>
              <MenuItem value="ROLE_ADMIN">Administrateur</MenuItem>
            </TextField>
            <TextField
              label={editTarget ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe'}
              name="motdepasse" type="password" value={form.motdepasse}
              onChange={handleChange} fullWidth size="small" required={!editTarget}
            />
          </Box>
        </DialogContent>
        <Divider />
        <DialogActions sx={{ px: 3, py: 1.5, flexDirection: isMobile ? 'column' : 'row', gap: isMobile ? 1 : 0 }}>
          <Button onClick={() => setModalOpen(false)} color="inherit" fullWidth={isMobile}>Annuler</Button>
          <Button variant="contained" onClick={handleSave} disabled={saving || !form.email || !form.prenom || !form.nom} fullWidth={isMobile}>
            {saving ? <CircularProgress size={18} color="inherit" /> : editTarget ? 'Enregistrer' : 'Créer'}
          </Button>
        </DialogActions>
      </Dialog>

      {/* Confirmation suppression */}
      <Dialog open={Boolean(deleteTarget)} onClose={() => setDeleteTarget(null)} maxWidth="xs" fullWidth>
        <DialogTitle>Supprimer l'utilisateur ?</DialogTitle>
        <DialogContent>
          <Typography variant="body2">
            Êtes-vous sûr de vouloir supprimer <strong>{deleteTarget?.prenom} {deleteTarget?.nom}</strong> ?
            Cette action est irréversible.
          </Typography>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setDeleteTarget(null)} color="inherit">Annuler</Button>
          <Button variant="contained" color="error" onClick={handleDelete}>Supprimer</Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}

// ── Onglet Logs ───────────────────────────────────────────────────────────────
const FILTRE_DATE_OPTIONS = [
  { value: 'all',  label: 'Tout' },
  { value: 'today', label: "Aujourd'hui" },
  { value: 'week',  label: 'Cette semaine' },
];

function OngletLogs() {
  const { data: logs, loading, error } = useApi('/api/logs');
  const [filtreAction, setFiltreAction] = useState('');
  const [filtreDate,   setFiltreDate]   = useState('all');

  const allLogs = Array.isArray(logs) ? logs : [];

  const filtered = allLogs.filter((l) => {
    if (filtreAction && l.action !== filtreAction) return false;
    if (filtreDate === 'today') {
      const today = new Date().toISOString().slice(0, 10);
      if (!l.dateAction.startsWith(today)) return false;
    } else if (filtreDate === 'week') {
      const since = new Date();
      since.setDate(since.getDate() - 7);
      if (new Date(l.dateAction) < since) return false;
    }
    return true;
  });

  return (
    <Box>
      {/* Filtres */}
      <Box sx={{ display: 'flex', gap: 2, mb: 2, flexWrap: 'wrap' }}>
        <FormControl size="small" sx={{ minWidth: 200 }}>
          <InputLabel>Type d'action</InputLabel>
          <Select
            value={filtreAction}
            label="Type d'action"
            onChange={(e) => setFiltreAction(e.target.value)}
          >
            <MenuItem value="">Toutes les actions</MenuItem>
            {Object.entries(ACTION_META).map(([key, meta]) => (
              <MenuItem key={key} value={key}>{meta.label}</MenuItem>
            ))}
          </Select>
        </FormControl>

        <FormControl size="small" sx={{ minWidth: 160 }}>
          <InputLabel>Période</InputLabel>
          <Select
            value={filtreDate}
            label="Période"
            onChange={(e) => setFiltreDate(e.target.value)}
          >
            {FILTRE_DATE_OPTIONS.map((o) => (
              <MenuItem key={o.value} value={o.value}>{o.label}</MenuItem>
            ))}
          </Select>
        </FormControl>

        {(filtreAction || filtreDate !== 'all') && (
          <Button size="small" color="inherit" onClick={() => { setFiltreAction(''); setFiltreDate('all'); }}>
            Réinitialiser
          </Button>
        )}

        <Typography variant="body2" color="text.secondary" sx={{ alignSelf: 'center', ml: 'auto' }}>
          {filtered.length} entrée{filtered.length !== 1 ? 's' : ''}
        </Typography>
      </Box>

      {loading ? (
        <Box sx={{ display: 'flex', justifyContent: 'center', pt: 6 }}><CircularProgress /></Box>
      ) : error ? (
        <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider', p: 4, textAlign: 'center' }}>
          <Typography variant="body2" color="text.disabled">Impossible de charger les logs.</Typography>
        </Paper>
      ) : filtered.length === 0 ? (
        <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider', p: 4, textAlign: 'center' }}>
          <Typography variant="body2" color="text.disabled">Aucun log correspondant.</Typography>
        </Paper>
      ) : (
        <Box sx={{ overflowX: 'auto' }}>
          <TableContainer component={Paper} elevation={0} sx={{ border: '1px solid', borderColor: 'divider' }}>
            <Table size="small" sx={{ minWidth: 680 }}>
              <TableHead>
                <TableRow sx={{ backgroundColor: '#F7FAF3' }}>
                  <TableCell sx={{ fontWeight: 600, whiteSpace: 'nowrap' }}>Date / Heure</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>Action</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>Détail</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>Utilisateur</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {filtered.map((l) => (
                  <TableRow key={l.id} hover>
                    <TableCell sx={{ fontFamily: '"DM Mono", monospace', fontSize: '0.78rem', whiteSpace: 'nowrap' }}>
                      {formatDate(l.dateAction)} {l.dateAction?.slice(11, 16)}
                    </TableCell>
                    <TableCell><ActionBadge action={l.action} /></TableCell>
                    <TableCell sx={{ fontSize: '0.85rem', color: 'text.secondary' }}>{l.detail ?? '—'}</TableCell>
                    <TableCell sx={{ fontSize: '0.85rem', whiteSpace: 'nowrap' }}>
                      {l.utilisateur?.prenom} {l.utilisateur?.nom}
                      <Typography component="span" variant="caption" sx={{ fontFamily: '"DM Mono", monospace', color: 'text.secondary', display: 'block' }}>
                        {l.utilisateur?.email}
                      </Typography>
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

// ── Page principale ───────────────────────────────────────────────────────────
export default function GestionUtilisateursPage() {
  const { token } = useAuth();
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));
  const [onglet, setOnglet] = useState(0);

  return (
    <Box>
      <Typography sx={{ fontWeight: 700, fontSize: { xs: '1.1rem', md: '1.5rem' }, mb: 2 }}>
        Gestion des utilisateurs
      </Typography>

      <Tabs
        value={onglet}
        onChange={(_, v) => setOnglet(v)}
        sx={{ mb: 3, borderBottom: '1px solid', borderColor: 'divider' }}
      >
        <Tab label="Utilisateurs" />
        <Tab label="Logs d'activité" />
      </Tabs>

      {onglet === 0 && <OngletUtilisateurs token={token} isMobile={isMobile} />}
      {onglet === 1 && <OngletLogs />}
    </Box>
  );
}
