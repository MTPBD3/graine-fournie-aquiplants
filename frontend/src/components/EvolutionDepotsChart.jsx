import { useState, useMemo } from 'react';
import { Box, Paper, Typography, ToggleButtonGroup, ToggleButton, CircularProgress } from '@mui/material';
import {
  LineChart, Line, XAxis, YAxis, CartesianGrid,
  Tooltip as RTooltip, ResponsiveContainer,
} from 'recharts';
import { useApi } from '../hooks/useApi';

const DOT_COLORS = ['#388E3C', '#FF8F00', '#E53935', '#D4E157', '#1B5E20', '#2E7D32'];

function CustomDot({ cx, cy, index }) {
  if (cx == null || cy == null) return null;
  return (
    <circle
      cx={cx} cy={cy} r={5}
      fill={DOT_COLORS[index % DOT_COLORS.length]}
      stroke="#fff" strokeWidth={2}
    />
  );
}

function CustomTooltip({ active, payload, label }) {
  if (!active || !payload?.length) return null;
  const v = payload[0].value;
  return (
    <Box sx={{
      bgcolor: '#fff', border: '1px solid #E8F5E9', borderRadius: '8px',
      px: 1.5, py: 1, boxShadow: '0 4px 12px rgba(0,0,0,0.1)',
    }}>
      <Typography sx={{ fontSize: '0.75rem', color: 'text.secondary', mb: 0.25 }}>
        {label}
      </Typography>
      <Typography sx={{
        fontSize: '0.875rem', fontWeight: 700,
        color: '#1B5E20', fontFamily: '"DM Mono", monospace',
      }}>
        {v} dépôt{v > 1 ? 's' : ''}
      </Typography>
    </Box>
  );
}

export default function EvolutionDepotsChart() {
  const [period, setPeriod] = useState('1M');

  // Appel réel vers GET /api/stats/depots?periode=1M|3M|6M
  const { data: rawData, loading } = useApi(`/api/stats/depots?periode=${period}`);

  // Transforme [{ date: "2026-04-01", total: 3 }] → [{ date: "01/04", dépôts: 3 }]
  const chartData = useMemo(() => {
    if (!Array.isArray(rawData)) return [];
    return rawData.map(({ date, total }) => ({
      date: `${date.slice(8, 10)}/${date.slice(5, 7)}`,
      dépôts: total,
    }));
  }, [rawData]);

  const tickInterval = chartData.length <= 31 ? 0 : chartData.length <= 92 ? 6 : 13;

  return (
    <Paper elevation={0} sx={{
      borderRadius: '12px',
      boxShadow: '0 2px 12px rgba(0,0,0,0.06)',
      border: '1px solid rgba(27,94,32,0.08)',
      overflow: 'hidden',
      mb: 2.5,
      bgcolor: '#fff',
    }}>
      {/* En-tête */}
      <Box sx={{
        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
        px: 2.5, py: 1.75,
        borderBottom: '1px solid rgba(0,0,0,0.05)',
      }}>
        <Typography sx={{ fontWeight: 700, fontSize: '0.875rem' }}>
          Évolution des dépôts
        </Typography>
        <ToggleButtonGroup
          value={period}
          exclusive
          onChange={(_, v) => v && setPeriod(v)}
          size="small"
          sx={{ '& .MuiToggleButton-root': { py: 0.25, px: 1, fontSize: '0.7rem', textTransform: 'none' } }}
        >
          {['1M', '3M', '6M'].map(p => (
            <ToggleButton key={p} value={p}>{p}</ToggleButton>
          ))}
        </ToggleButtonGroup>
      </Box>

      {/* Zone graphique */}
      <Box sx={{
        px: 2, pt: 2, pb: 1.5, bgcolor: '#F7FAF3',
        minHeight: 256, display: 'flex', alignItems: 'center', justifyContent: 'center',
      }}>
        {loading ? (
          <CircularProgress size={22} sx={{ color: '#1B5E20' }} />
        ) : chartData.length === 0 ? (
          <Typography variant="body2" sx={{ color: 'text.disabled' }}>
            Aucune donnée sur cette période
          </Typography>
        ) : (
          <ResponsiveContainer width="100%" height={220}>
            <LineChart data={chartData} margin={{ top: 8, right: 12, bottom: 0, left: -20 }}>
              <CartesianGrid strokeDasharray="3 3" stroke="#D8ECC8" />
              <XAxis
                dataKey="date"
                tick={{ fontSize: 10, fill: '#9E9E9E' }}
                interval={tickInterval}
                tickLine={false}
                axisLine={{ stroke: '#E0E0E0' }}
              />
              <YAxis
                tick={{ fontSize: 10, fill: '#9E9E9E', fontFamily: '"DM Mono", monospace' }}
                tickLine={false}
                axisLine={false}
                allowDecimals={false}
                width={30}
              />
              <RTooltip
                content={<CustomTooltip />}
                cursor={{ stroke: '#D4E157', strokeWidth: 1.5, strokeDasharray: '4 4' }}
              />
              <Line
                type="monotone"
                dataKey="dépôts"
                stroke="#2E7D32"
                strokeWidth={2.5}
                dot={(props) => <CustomDot key={props.index} {...props} />}
                activeDot={{ r: 7, fill: '#FF8F00', stroke: '#fff', strokeWidth: 2 }}
              />
            </LineChart>
          </ResponsiveContainer>
        )}
      </Box>
    </Paper>
  );
}
