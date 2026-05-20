import { useState, useEffect, useCallback } from 'react';
import { Box, Typography, Skeleton, Button, InputBase, IconButton } from '@mui/material';
import SearchIcon from '@mui/icons-material/Search';
import { useAuth } from '../context/AuthContext';

const API_BASE = import.meta.env.VITE_API_URL;
const DEFAULT  = { lat: 43.83, lon: 4.84, ville: 'Eyragues' };

const WMO = {
  0:  { emoji: '☀️',  label: 'Ciel dégagé' },
  1:  { emoji: '🌤️', label: 'Peu nuageux' },
  2:  { emoji: '⛅',  label: 'Partiellement nuageux' },
  3:  { emoji: '☁️',  label: 'Nuageux' },
  45: { emoji: '🌫️', label: 'Brouillard' },
  48: { emoji: '🌫️', label: 'Brouillard givrant' },
  51: { emoji: '🌦️', label: 'Bruine légère' },
  53: { emoji: '🌦️', label: 'Bruine modérée' },
  55: { emoji: '🌦️', label: 'Bruine dense' },
  61: { emoji: '🌧️', label: 'Pluie légère' },
  63: { emoji: '🌧️', label: 'Pluie modérée' },
  65: { emoji: '🌧️', label: 'Pluie forte' },
  71: { emoji: '🌨️', label: 'Neige légère' },
  73: { emoji: '🌨️', label: 'Neige modérée' },
  75: { emoji: '❄️',  label: 'Neige forte' },
  77: { emoji: '🌨️', label: 'Grésil' },
  80: { emoji: '🌦️', label: 'Averses légères' },
  81: { emoji: '🌧️', label: 'Averses modérées' },
  82: { emoji: '⛈️',  label: 'Averses violentes' },
  85: { emoji: '🌨️', label: 'Averses neigeuses' },
  86: { emoji: '❄️',  label: 'Averses de neige' },
  95: { emoji: '⛈️',  label: 'Orage' },
  96: { emoji: '⛈️',  label: 'Orage avec grêle' },
  99: { emoji: '⛈️',  label: 'Orage violent' },
};

function getWmo(code) {
  return WMO[code] ?? { emoji: '🌡️', label: 'Météo' };
}

async function geocodeCity(name) {
  try {
    const res  = await fetch(
      `https://geocoding-api.open-meteo.com/v1/search?name=${encodeURIComponent(name)}&count=1&language=fr&format=json`
    );
    const json = await res.json();
    const r    = json.results?.[0];
    if (!r) return null;
    return { lat: r.latitude, lon: r.longitude, ville: r.name };
  } catch {
    return null;
  }
}

export default function MeteoWidget() {
  const { token } = useAuth();

  const [coords,      setCoords]      = useState(null);
  const [geoLoading,  setGeoLoading]  = useState(true);
  const [meteoData,   setMeteoData]   = useState(null);
  const [meteoLoading,setMeteoLoading]= useState(false);
  const [meteoError,  setMeteoError]  = useState(null);
  const [searchInput, setSearchInput] = useState('');
  const [searchError, setSearchError] = useState('');

  // ── Fetch météo pour des coords données ──────────────────────────────────
  const fetchMeteo = useCallback(async ({ lat, lon }) => {
    setMeteoLoading(true);
    setMeteoError(null);
    try {
      const res = await fetch(`${API_BASE}/api/meteo?lat=${lat}&lon=${lon}`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      if (!res.ok) throw new Error(`Erreur ${res.status}`);
      setMeteoData(await res.json());
    } catch (err) {
      setMeteoError(err.message);
    } finally {
      setMeteoLoading(false);
    }
  }, [token]);

  // ── Résolution initiale des coordonnées (une seule fois au mount) ─────────
  useEffect(() => {
    const savedVille = localStorage.getItem('meteo_ville');

    if (savedVille) {
      geocodeCity(savedVille).then(result => {
        setCoords(result ?? DEFAULT);
        setGeoLoading(false);
      });
      return;
    }

    if (!('geolocation' in navigator)) {
      setCoords(DEFAULT);
      setGeoLoading(false);
      return;
    }

    navigator.geolocation.getCurrentPosition(
      ({ coords: pos }) => {
        setCoords({ lat: pos.latitude, lon: pos.longitude, ville: 'Ma position' });
        setGeoLoading(false);
      },
      () => { setCoords(DEFAULT); setGeoLoading(false); },
      { timeout: 6000 }
    );
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  // ── Fetch météo à chaque changement de coords ─────────────────────────────
  useEffect(() => {
    if (coords) fetchMeteo(coords);
  }, [coords, fetchMeteo]);

  // ── Recherche manuelle d'une ville ────────────────────────────────────────
  const handleSearch = useCallback(async () => {
    const q = searchInput.trim();
    if (!q) return;
    setSearchError('');
    const result = await geocodeCity(q);
    if (!result) { setSearchError('Ville introuvable'); return; }
    setSearchInput('');
    setCoords(result);
  }, [searchInput]);

  const handleKeyDown = (e) => { if (e.key === 'Enter') handleSearch(); };

  // ── Rendu ─────────────────────────────────────────────────────────────────
  const CARD = { bgcolor: 'rgba(255,255,255,0.08)', borderRadius: 2, px: 1.5, py: 1 };

  if (geoLoading || meteoLoading) {
    return (
      <Box sx={CARD}>
        <Skeleton variant="text" width="40%" sx={{ bgcolor: 'rgba(255,255,255,0.15)', mb: 0.25 }} />
        <Skeleton variant="text" width="55%" sx={{ bgcolor: 'rgba(255,255,255,0.15)', mb: 0.25 }} />
        <Skeleton variant="text" width="75%" sx={{ bgcolor: 'rgba(255,255,255,0.12)' }} />
      </Box>
    );
  }

  if (meteoError) {
    return (
      <Box sx={CARD}>
        <Typography sx={{ color: '#FF8F00', fontSize: '0.72rem', fontWeight: 600, mb: 0.75 }}>
          Pas de connexion internet
        </Typography>
        <Button
          size="small"
          variant="outlined"
          onClick={() => coords && fetchMeteo(coords)}
          sx={{
            borderColor: '#2E7D32', color: '#fff', fontSize: '0.68rem',
            py: 0.25, px: 1, minHeight: 0, textTransform: 'none',
            '&:hover': { borderColor: '#D4E157', color: '#D4E157', bgcolor: 'transparent' },
          }}
        >
          Actualiser
        </Button>
      </Box>
    );
  }

  if (!meteoData || meteoData.error) return null;

  const { emoji, label } = getWmo(meteoData.weathercode);

  return (
    <Box sx={CARD}>

      {/* Champ de recherche */}
      <Box sx={{
        display: 'flex', alignItems: 'center',
        bgcolor: 'rgba(255,255,255,0.08)', borderRadius: 1, px: 0.75, mb: 0.75,
      }}>
        <InputBase
          value={searchInput}
          onChange={e => { setSearchInput(e.target.value); setSearchError(''); }}
          onKeyDown={handleKeyDown}
          placeholder="Rechercher une ville…"
          sx={{
            flex: 1, fontSize: '0.7rem', color: 'rgba(255,255,255,0.8)',
            '& input::placeholder': { color: 'rgba(255,255,255,0.35)', opacity: 1 },
          }}
        />
        <IconButton
          size="small"
          onClick={handleSearch}
          sx={{ p: 0.25, color: 'rgba(255,255,255,0.45)', '&:hover': { color: '#D4E157' } }}
        >
          <SearchIcon sx={{ fontSize: 14 }} />
        </IconButton>
      </Box>

      {searchError && (
        <Typography sx={{ color: '#E53935', fontSize: '0.67rem', mb: 0.4 }}>
          {searchError}
        </Typography>
      )}

      {/* Ville */}
      <Typography sx={{ color: 'rgba(255,255,255,0.45)', fontSize: '0.68rem', mb: 0.2 }}>
        {coords?.ville ?? ''}
      </Typography>

      {/* Température + emoji */}
      <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.75, mb: 0.2 }}>
        <Typography sx={{ fontSize: '1rem', lineHeight: 1 }}>{emoji}</Typography>
        <Typography sx={{
          color: '#fff', fontSize: '0.85rem', fontWeight: 700,
          fontFamily: '"DM Mono", monospace', lineHeight: 1,
        }}>
          {meteoData.temperature}°C
        </Typography>
      </Box>

      {/* Description */}
      <Typography sx={{ color: 'rgba(255,255,255,0.7)', fontSize: '0.72rem', mb: 0.3 }}>
        {label}
      </Typography>

      {/* Humidité + vent */}
      <Typography sx={{
        color: 'rgba(255,255,255,0.4)', fontSize: '0.66rem',
        fontFamily: '"DM Mono", monospace',
      }}>
        {meteoData.humidity}% · {meteoData.windspeed} km/h
      </Typography>
    </Box>
  );
}
