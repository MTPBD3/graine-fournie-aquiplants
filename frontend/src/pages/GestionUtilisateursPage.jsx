import { useState } from 'react';
import {
  Box, Typography, Paper, Table, TableHead, TableRow, TableCell, TableBody,
  Button, Dialog, DialogTitle, DialogContent, DialogActions, TextField,
  Select, MenuItem, FormControl, InputLabel, Alert, CircularProgress, IconButton, Chip,
} from '@mui/material';
import EditIcon from '@mui/icons-material/Edit';
import DeleteIcon from '@mui/icons-material/Delete';
import AddIcon from '@mui/icons-material/Add';
import { useApi, apiRequest } from '../hooks/useApi';
import { useAuth } from '../context/AuthContext';

const EMPTY_FORM = { prenom: '', nom: '', email: '', password: '', role: 'employe' };

export default function GestionUtilisateursPage() {
  const { token } = useAuth();
  const { data: utilisateurs, loading, error, refetch } = useApi('/api/utilisateurs');
  const [open,    setOpen]    = useState(false);
  const [editing, setEditing] = useState(null);
  const [form,    setForm]    = useState(EMPTY_FORM);
  const [saving,  setSaving]  = useState(false);
  const [formErr, setFormErr] = useState('');
  const [deleting, setDeleting] = useState(null);

  const openCreate = () => { setEditing(null); setForm(EMPTY_FORM); setFormErr(''); setOpen(true); };
  const openEdit   = (u) => {
    setEditing(u);
    setForm({ prenom: u.prenom ?? '', nom: u.nom ?? '', email: u.email ?? '', password: '', role: u.role ?? 'employe' });
    setFormErr('');
    setOpen(true);
  };
  const closeDialog = () => { setOpen(false); setEditing(null); };

  const handleChange = (e) => setForm(f => ({ ...f, [e.target.name]: e.target.value }));

  const handleSave = async () => {
    setFormErr('');
    if (!form.prenom || !form.nom || !form.email) { setFormErr('Prénom, nom et email sont requis.'); return; }
    if (!editing && !form.password) { setFormErr('Le mot de passe est requis pour un nouvel utilisateur.'); return; }

    const payload = { prenom: form.prenom, nom: form.nom, email: form.email, role: form.role };
    if (form.password) payload.password = form.password;

    setSaving(true);
    try {
      if (editing) {
        await apiRequest(`/api/utilisateurs/${editing.id}`, 'PUT', payload, token);
      } else {
        await apiRequest('/api/utilisateurs', 'POST', payload, token);
      }
      refetch();
      closeDialog();
    } catch (err) {
      setFormErr(err.message);
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Supprimer cet utilisateur ?')) return;
    setDeleting(id);
    try {
      await apiRequest(`/api/utilisateurs/${id}`, 'DELETE', null, token);
      refetch();
    } catch (err) {
      alert(err.message);
    } finally {
      setDeleting(null);
    }
  };

  return (
    <Box>
      <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 2 }}>
        <Typography sx={{ fontWeight: 700, fontSize: { xs: '1.1rem', md: '1.5rem' } }}>
          Utilisateurs
        </Typography>
        <Button variant="contained" startIcon={<AddIcon />} onClick={openCreate} sx={{ bgcolor: '#1B5E20' }}>
          Ajouter
        </Button>
      </Box>

      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}

      <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider' }}>
        {loading && <Box sx={{ p: 3 }}><CircularProgress size={24} /></Box>}
        {!loading && Array.isArray(utilisateurs) && (
          <Table size="small">
            <TableHead>
              <TableRow sx={{ bgcolor: '#F5F5F5' }}>
                <TableCell sx={{ fontWeight: 600 }}>Nom</TableCell>
                <TableCell sx={{ fontWeight: 600 }}>Email</TableCell>
                <TableCell sx={{ fontWeight: 600 }}>Rôle</TableCell>
                <TableCell />
              </TableRow>
            </TableHead>
            <TableBody>
              {utilisateurs.map(u => (
                <TableRow key={u.id} hover>
                  <TableCell>{u.prenom} {u.nom}</TableCell>
                  <TableCell>{u.email}</TableCell>
                  <TableCell>
                    <Chip
                      label={u.role} size="small"
                      sx={{ bgcolor: u.role === 'admin' ? '#1B5E20' : '#E0E0E0', color: u.role === 'admin' ? '#fff' : 'inherit' }}
                    />
                  </TableCell>
                  <TableCell align="right">
                    <IconButton size="small" onClick={() => openEdit(u)}><EditIcon fontSize="small" /></IconButton>
                    <IconButton size="small" color="error" disabled={deleting === u.id} onClick={() => handleDelete(u.id)}>
                      <DeleteIcon fontSize="small" />
                    </IconButton>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </Paper>

      <Dialog open={open} onClose={closeDialog} maxWidth="xs" fullWidth>
        <DialogTitle>{editing ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur'}</DialogTitle>
        <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2, pt: '16px !important' }}>
          {formErr && <Alert severity="error">{formErr}</Alert>}
          <TextField label="Prénom" name="prenom" value={form.prenom} onChange={handleChange} required fullWidth />
          <TextField label="Nom"    name="nom"    value={form.nom}    onChange={handleChange} required fullWidth />
          <TextField label="Email"  name="email"  type="email" value={form.email} onChange={handleChange} required fullWidth />
          <TextField
            label={editing ? 'Nouveau mot de passe (laisser vide = inchangé)' : 'Mot de passe'}
            name="password" type="password" value={form.password} onChange={handleChange}
            required={!editing} fullWidth
          />
          <FormControl fullWidth>
            <InputLabel>Rôle</InputLabel>
            <Select name="role" value={form.role} label="Rôle" onChange={handleChange}>
              <MenuItem value="employe">Employé</MenuItem>
              <MenuItem value="admin">Admin</MenuItem>
            </Select>
          </FormControl>
        </DialogContent>
        <DialogActions sx={{ px: 3, pb: 2 }}>
          <Button onClick={closeDialog}>Annuler</Button>
          <Button variant="contained" onClick={handleSave} disabled={saving} sx={{ bgcolor: '#1B5E20' }}>
            {saving ? <CircularProgress size={18} color="inherit" /> : 'Enregistrer'}
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}
