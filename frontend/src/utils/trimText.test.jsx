import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { trimText } from './trimText';

describe('trimText', () => {
  it('conserve une apostrophe telle quelle', () => {
    expect(trimText("O'Brien")).toBe("O'Brien");
  });

  it('ne réencode aucun caractère HTML', () => {
    expect(trimText('Dupont & Fils <SARL> "Jo" `x`')).toBe('Dupont & Fils <SARL> "Jo" `x`');
  });

  it('supprime les espaces en début et fin', () => {
    expect(trimText('  bonjour  ')).toBe('bonjour');
  });

  it('retourne une chaîne vide inchangée', () => {
    expect(trimText('')).toBe('');
  });

  it("retourne la valeur telle quelle si ce n'est pas une string", () => {
    expect(trimText(42)).toBe(42);
    expect(trimText(null)).toBe(null);
  });
});

describe("protection XSS à l'affichage (React)", () => {
  it("affiche une apostrophe correctement, sans entité HTML", () => {
    render(<p>{"O'Brien"}</p>);
    expect(screen.getByText("O'Brien")).toBeInTheDocument();
  });

  it("affiche une balise script comme du texte, sans l'exécuter", () => {
    const { container } = render(<p>{'<script>alert(1)</script>'}</p>);
    expect(container.querySelector('script')).toBeNull();
    expect(container.textContent).toBe('<script>alert(1)</script>');
  });
});
