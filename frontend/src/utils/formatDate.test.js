import { describe, it, expect } from 'vitest';
import { formatDate } from './formatDate';

describe('formatDate', () => {
  it('formate une date ISO en JJ/MM/AAAA', () => {
    expect(formatDate('2026-01-15')).toBe('15/01/2026');
  });

  it('formate une date avec heure', () => {
    expect(formatDate('2026-05-20T10:30:00')).toMatch(/20\/05\/2026/);
  });

  it('retourne "-" pour une valeur nulle', () => {
    expect(formatDate(null)).toBe('-');
  });

  it('retourne "-" pour undefined', () => {
    expect(formatDate(undefined)).toBe('-');
  });

  it('retourne "-" pour une chaîne vide', () => {
    expect(formatDate('')).toBe('-');
  });
});
