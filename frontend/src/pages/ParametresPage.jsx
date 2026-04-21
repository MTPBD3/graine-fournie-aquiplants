import { useState } from 'react';
import {
  Box, Typography, Paper, TextField, Button, Alert, CircularProgress, Divider,
} from '@mui/material';
import { useAuth } from '../context/AuthContext';
import { apiRequest } from '../hooks/useApi';
import { useApi } from '../hooks/useApi';

export default function ParametresPage() {
  const { user, token } = useAuth();
  const { data: utilisateurs } = useApi('/api/utilisateurs', { skip: user?.role !== 'admin' });

  const [form,    setForm]    = useState({ ancienMdp: '', nouveauMdp: '', confirmation: '' });
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState('');
  const [error,   setError]   = useState('');

  const handleChange = (e) => setForm(f => ({ ...f, [e.target.name]: e.target.value }));

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(''); setSuccess('');
    if (form.nouveauMdp !== form.confirmation) {
      setError('Les mots de passe ne correspondent pas.');
      return;
    }
    if (form.nouveauMdp.length < 6) {
      setError('Le mot de passe doit faire au moins 6 caractères.');
      return;
    }

    const me = Array.isArray(utilisateurs)
      ? utilisateurs.find(u => u.email === user.email)
      : null;
    if (!me) { setError('Utilisateur introuvable.'); return; }

    setLoading(true);
    try {
      await apiRequest(`/api/utilisateurs/${me.id}`, 'PUT', { password: form.nouveauMdp }, token);
      setSuccess('Mot de passe modifié avec succès.');
      setForm({ ancienMdp: '', nouveauMdp: '', confirmation: '' });
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Box>
      <Typography sx={{ fontWeight: 700, fontSize: { xs: '1.1rem', md: '1.5rem' }, mb: 2 }}>
        Paramètres
      </Typography>

      <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider', p: 3, maxWidth: 480 }}>
        <Typography variant="subtitle1" sx={{ fontWeight: 600, mb: 0.5 }}>Informations</Typography>
        <Typography variant="body2" color="text.secondary">{user?.prenom} {user?.nom}</Typography>
        <Typography variant="body2" color="text.secondary">{user?.email}</Typography>
        <Typography variant="body2" color="text.secondary" sx={{ textTransform: 'capitalize' }}>
          Rôle : {user?.role}
        </Typography>

        <Divider sx={{ my: 2.5 }} />

        <Typography variant="subtitle1" sx={{ fontWeight: 600, mb: 2 }}>Changer le mot de passe</Typography>

        {error   && <Alert severity="error"   sx={{ mb: 2 }}>{error}</Alert>}
        {success && <Alert severity="success" sx={{ mb: 2 }}>{success}</Alert>}

        <Box component="form" onSubmit={handleSubmit} sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
          <TextField label="Nouveau mot de passe" name="nouveauMdp" type="password"
            value={form.nouveauMdp} onChange={handleChange} required fullWidth />
          <TextField label="Confirmation" name="confirmation" type="password"
            value={form.confirmation} onChange={handleChange} required fullWidth />
          <Button type="submit" variant="contained" disabled={loading} sx={{ alignSelf: 'flex-start' }}>
            {loading ? <CircularProgress size={20} color="inherit" /> : 'Enregistrer'}
          </Button>
        </Box>
      </Paper>
    </Box>
  );
}
