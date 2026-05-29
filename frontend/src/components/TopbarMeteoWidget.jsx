import { useState, useEffect } from 'react';
import { Box, Typography, Skeleton } from '@mui/material';
import { useMediaQuery, useTheme } from '@mui/material';

const DEFAULT = { lat: 43.83, lon: 4.84 };

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

export default function TopbarMeteoWidget() {
  const theme    = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));

  const [weather, setWeather] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchWeather = async ({ lat, lon }) => {
      try {
        const res  = await fetch(
          `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current_weather=true`
        );
        const data = await res.json();
        if (data.current_weather) setWeather(data.current_weather);
      } catch { /* réseau indisponible : silence */ }
      finally   { setLoading(false); }
    };

    if (!('geolocation' in navigator)) {
      fetchWeather(DEFAULT);
      return;
    }

    navigator.geolocation.getCurrentPosition(
      ({ coords: pos }) => fetchWeather({ lat: pos.latitude, lon: pos.longitude }),
      ()                => fetchWeather(DEFAULT),
      { timeout: 6000 }
    );
  }, []);

  if (loading) {
    return (
      <Skeleton
        variant="text"
        width={isMobile ? 52 : 110}
        sx={{ bgcolor: 'rgba(0,0,0,0.08)' }}
      />
    );
  }

  if (!weather) return null;

  const { emoji, label } = getWmo(weather.weathercode);

  return (
    <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5, mr: 0.5 }}>
      <Typography component="span" sx={{ fontSize: '1.1rem', lineHeight: 1 }}>
        {emoji}
      </Typography>
      <Typography sx={{
        fontSize: '0.82rem', fontWeight: 700,
        color: 'text.primary',
        fontFamily: '"DM Mono", monospace',
        lineHeight: 1,
      }}>
        {Math.round(weather.temperature)}°C
      </Typography>
      {!isMobile && (
        <Typography sx={{ fontSize: '0.78rem', color: 'text.secondary', ml: 0.25 }}>
          {label}
        </Typography>
      )}
    </Box>
  );
}
