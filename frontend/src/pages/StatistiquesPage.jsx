import { useState } from 'react';
import { Box, Typography, Paper, Grid, CircularProgress, useMediaQuery, useTheme } from '@mui/material';
import {
  LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip,
  ResponsiveContainer, PieChart, Pie, Cell, Legend,
} from 'recharts';
import { useApi } from '../hooks/useApi';

const COLORS = ['#2E7D32', '#D4E157', '#FF8F00', '#388E3C'];

const formatMois = (mois) => {
  if (!mois) return '';
  const [year, month] = mois.split('-');
  return new Date(year, month - 1).toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
};

function EmptyState({ message = 'Pas encore de données' }) {
  return (
    <Typography variant="body2" color="text.disabled" sx={{ py: 4, textAlign: 'center' }}>
      {message}
    </Typography>
  );
}

export default function StatistiquesPage() {
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));

  const { data: stats, loading } = useApi('/api/statistiques');

  const evolutionData = stats?.evolutionMensuelle ?? [];
  const categoriesData = stats?.categories ?? [];

  const chartHeight = isMobile ? 200 : 240;
  const pieHeight = isMobile ? 200 : 260;
  const pieRadius = isMobile ? 70 : 90;

  return (
    <Box>
      <Typography sx={{ fontWeight: 700, mb: { xs: 2, md: 3 }, fontSize: { xs: '1.1rem', md: '1.5rem' } }}>
        Statistiques
      </Typography>

      {loading ? (
        <Box sx={{ display: 'flex', justifyContent: 'center', pt: 8 }}><CircularProgress /></Box>
      ) : (
        <Grid container spacing={{ xs: 1.5, md: 2 }}>
          {/* Courbe évolution */}
          <Grid size={{ xs: 12 }}>
            <Paper elevation={0} sx={{ p: { xs: 1.5, md: 2.5 }, border: '1px solid', borderColor: 'divider' }}>
              <Typography variant="subtitle1" sx={{ fontWeight: 600, mb: 1.5, fontSize: { xs: '0.875rem', md: '1rem' } }}>
                Évolution des sachets traités
              </Typography>
              {evolutionData.length === 0 ? (
                <EmptyState />
              ) : (
                <ResponsiveContainer width="100%" height={chartHeight}>
                  <LineChart data={evolutionData}>
                    <CartesianGrid strokeDasharray="3 3" stroke="#E8F5E9" />
                    <XAxis dataKey="mois" tickFormatter={formatMois} tick={{ fontSize: 11 }} />
                    <YAxis tick={{ fontSize: 11, fontFamily: '"DM Mono", monospace' }} width={30} allowDecimals={false} />
                    <Tooltip
                      labelFormatter={formatMois}
                      formatter={(value) => [value, 'Sachets traités']}
                    />
                    <Line
                      type="monotone"
                      dataKey="total"
                      name="Sachets traités"
                      stroke="#2E7D32"
                      strokeWidth={2}
                      dot={{ r: 3 }}
                      activeDot={{ r: 5 }}
                    />
                  </LineChart>
                </ResponsiveContainer>
              )}
            </Paper>
          </Grid>

          {/* Camembert */}
          <Grid size={{ xs: 12, md: 5 }}>
            <Paper elevation={0} sx={{ p: { xs: 1.5, md: 2.5 }, border: '1px solid', borderColor: 'divider' }}>
              <Typography variant="subtitle1" sx={{ fontWeight: 600, mb: 1, fontSize: { xs: '0.875rem', md: '1rem' } }}>
                Répartition par catégorie
              </Typography>
              {categoriesData.length === 0 ? (
                <EmptyState message="Données non disponibles" />
              ) : (
                <ResponsiveContainer width="100%" height={pieHeight}>
                  <PieChart>
                    <Pie
                      data={categoriesData}
                      cx="50%"
                      cy="45%"
                      outerRadius={pieRadius}
                      dataKey="value"
                      label={!isMobile ? ({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%` : undefined}
                      labelLine={!isMobile}
                    >
                      {categoriesData.map((_, i) => (
                        <Cell key={i} fill={COLORS[i % COLORS.length]} />
                      ))}
                    </Pie>
                    <Legend iconSize={10} wrapperStyle={{ fontSize: 11 }} />
                    <Tooltip />
                  </PieChart>
                </ResponsiveContainer>
              )}
            </Paper>
          </Grid>

          {/* Résumé chiffré */}
          <Grid size={{ xs: 12, md: 7 }}>
            <Paper elevation={0} sx={{ p: { xs: 1.5, md: 2.5 }, border: '1px solid', borderColor: 'divider' }}>
              <Typography variant="subtitle1" sx={{ fontWeight: 600, mb: 2, fontSize: { xs: '0.875rem', md: '1rem' } }}>
                Résumé de la période
              </Typography>
              {!stats ? (
                <EmptyState message="Données non disponibles" />
              ) : (
                <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
                  {[
                    { label: 'Total entrées', value: stats.totalEntrees ?? '—' },
                    { label: 'Total sorties', value: stats.totalSorties ?? '—' },
                    { label: 'Solde net', value: stats.solde ?? '—' },
                    { label: 'Catégorie dominante', value: stats.categorieTop ?? '—' },
                    { label: "Pic d'activité", value: stats.picActivite ?? '—' },
                  ].map(({ label, value }) => (
                    <Box key={label} sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                      <Typography variant="body2" color="text.secondary" sx={{ fontSize: { xs: '0.8rem', md: '0.875rem' } }}>
                        {label}
                      </Typography>
                      <Typography variant="body2" sx={{ fontFamily: '"DM Mono", monospace', fontWeight: 600, color: 'primary.dark', fontSize: { xs: '0.8rem', md: '0.875rem' } }}>
                        {value}
                      </Typography>
                    </Box>
                  ))}
                </Box>
              )}
            </Paper>
          </Grid>
        </Grid>
      )}
    </Box>
  );
}
