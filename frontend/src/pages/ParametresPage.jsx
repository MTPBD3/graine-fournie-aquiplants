import { useState } from 'react';
import {
  Box,
  Typography,
  Paper,
  TextField,
  Button,
  Divider,
  Switch,
  FormControlLabel,
  Alert,
  CircularProgress,
} from '@mui/material';
import { useAuth } from '../context/AuthContext';
import { apiRequest } from '../hooks/useApi';
import { sanitize } from '../utils/sanitize';

export default function ParametresPage() {
  const { user, token } = useAuth();
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [saving, setSaving] = useState(false);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState('');
  const [notifEmail, setNotifEmail] = useState(true);
  const [seuilAlerte, setSeuilAlerte] = useState('5');
  const [meteoVille, setMeteoVille] = useState(() => localStorage.getItem('meteo_ville') ?? '');
  const [meteoSaved, setMeteoSaved] = useState(false);

  const handleSave = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess(false);

    if (!password) {
      setError('Veuillez saisir un nouveau mot de passe.');
      return;
    }
    if (password !== confirmPassword) {
      setError('Les mots de passe ne correspondent pas.');
      return;
    }

    setSaving(true);
    try {
      await apiRequest('/api/utilisateurs/mon-profil', 'PATCH', { motdepasse: password.trim() }, token);
      setSuccess(true);
      setPassword('');
      setConfirmPassword('');
    } catch (err) {
      setError(err.message || 'Erreur lors de la mise à jour.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Box sx={{ maxWidth: 640 }}>
      <Typography variant="h5" sx={{ fontWeight: 700, mb: 3 }}>
        Paramètres
      </Typography>

      {/* Profil */}
      <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider', p: 2.5, mb: 2 }}>
        <Typography variant="subtitle1" sx={{ fontWeight: 600, mb: 2 }}>
          Mon profil
        </Typography>

        {success && <Alert severity="success" sx={{ mb: 2 }}>Mot de passe mis à jour.</Alert>}
        {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}

        <Box component="form" onSubmit={handleSave} sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
          <TextField
            label="Adresse e-mail"
            value={user?.email ?? ''}
            fullWidth
            size="small"
            disabled
          />
          <TextField
            label="Nouveau mot de passe"
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            fullWidth
            size="small"
            autoComplete="new-password"
          />
          <TextField
            label="Confirmer le mot de passe"
            type="password"
            value={confirmPassword}
            onChange={(e) => setConfirmPassword(e.target.value)}
            fullWidth
            size="small"
            autoComplete="new-password"
          />
          <Button
            type="submit"
            variant="contained"
            disabled={saving}
            sx={{ alignSelf: 'flex-start' }}
            startIcon={saving ? <CircularProgress size={16} color="inherit" /> : undefined}
          >
            Mettre à jour le mot de passe
          </Button>
        </Box>
      </Paper>

      {/* Notifications */}
      <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider', p: 2.5, mb: 2 }}>
        <Typography variant="subtitle1" sx={{ fontWeight: 600, mb: 2 }}>
          Notifications
        </Typography>
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
          <FormControlLabel
            control={
              <Switch
                checked={notifEmail}
                onChange={(e) => setNotifEmail(e.target.checked)}
                color="primary"
              />
            }
            label="Recevoir les alertes stock par e-mail"
          />
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
            <Typography variant="body2" sx={{ minWidth: 200 }}>
              Seuil d'alerte par défaut (unités)
            </Typography>
            <TextField
              type="number"
              value={seuilAlerte}
              onChange={(e) => setSeuilAlerte(e.target.value)}
              size="small"
              sx={{ width: 100 }}
              inputProps={{ min: 1, style: { fontFamily: '"DM Mono", monospace' } }}
            />
          </Box>
        </Box>
      </Paper>

      {/* Météo */}
      <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider', p: 2.5, mb: 2 }}>
        <Typography variant="subtitle1" sx={{ fontWeight: 600, mb: 0.5 }}>
          Météo
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
          Ville affichée dans le widget météo de la barre latérale. Laissez vide pour utiliser la géolocalisation de l'appareil.
        </Typography>
        <Box sx={{ display: 'flex', gap: 1.5, alignItems: 'flex-start' }}>
          <TextField
            label="Ville par défaut"
            placeholder="ex : Eyragues, Paris, Lyon…"
            value={meteoVille}
            onChange={e => { setMeteoVille(e.target.value); setMeteoSaved(false); }}
            size="small"
            sx={{ flex: 1 }}
          />
          <Button
            variant="outlined"
            onClick={() => {
              const v = meteoVille.trim();
              if (v) localStorage.setItem('meteo_ville', v);
              else localStorage.removeItem('meteo_ville');
              setMeteoSaved(true);
            }}
            sx={{ borderColor: '#2E7D32', color: '#2E7D32', whiteSpace: 'nowrap',
              '&:hover': { bgcolor: '#2E7D321A', borderColor: '#1B5E20' } }}
          >
            Enregistrer
          </Button>
        </Box>
        {meteoSaved && (
          <Typography variant="caption" sx={{ color: '#2E7D32', mt: 1, display: 'block' }}>
            Ville enregistrée. Le widget se mettra à jour au prochain chargement.
          </Typography>
        )}
      </Paper>

      {/* À propos */}
      <Paper elevation={0} sx={{ border: '1px solid', borderColor: 'divider', p: 2.5 }}>
        <Typography variant="subtitle1" sx={{ fontWeight: 600, mb: 2 }}>
          À propos
        </Typography>
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1 }}>
          {[
            { label: 'Application', value: 'Graine Fournie AQUIPLANTS' },
            { label: 'Version', value: '1.0.0' },
            { label: 'Pépinière', value: 'AQUIPLANTS · Eyragues' },
          ].map(({ label, value }) => (
            <Box key={label} sx={{ display: 'flex', gap: 1 }}>
              <Typography variant="body2" sx={{ fontWeight: 500, minWidth: 130 }}>{label} :</Typography>
              <Typography variant="body2" color="text.secondary">{value}</Typography>
            </Box>
          ))}
        </Box>
      </Paper>
    </Box>
  );
}
