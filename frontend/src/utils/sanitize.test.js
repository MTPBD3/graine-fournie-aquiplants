import { describe, it, expect } from 'vitest';
import { sanitize } from './sanitize';

describe('sanitize', () => {
  it('échappe les balises HTML', () => {
    expect(sanitize('<script>alert("xss")</script>')).toBe('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');
  });

  it('échappe les guillemets simples', () => {
    expect(sanitize("l'injection")).toBe('l&#x27;injection');
  });

  it('échappe les backticks', () => {
    expect(sanitize('`cmd`')).toBe('&#x60;cmd&#x60;');
  });

  it('échappe les esperluettes', () => {
    expect(sanitize('a & b')).toBe('a &amp; b');
  });

  it('retourne une chaîne vide inchangée', () => {
    expect(sanitize('')).toBe('');
  });

  it('ne modifie pas un texte sans caractères spéciaux', () => {
    expect(sanitize('Texte normal')).toBe('Texte normal');
  });

  it('retourne la valeur telle quelle si ce n\'est pas une string', () => {
    expect(sanitize(42)).toBe(42);
    expect(sanitize(null)).toBe(null);
  });

  it('supprime les espaces en début et fin', () => {
    expect(sanitize('  bonjour  ')).toBe('bonjour');
  });
});
