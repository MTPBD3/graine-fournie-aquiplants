import { describe, it, vi } from 'vitest';
import { render } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import Layout from './Layout';

vi.mock('../context/AuthContext', () => ({
  useAuth: () => ({
    token: 'fake-token',
    user: { email: 'admin@test.fr', roles: ['ROLE_ADMIN'] },
    logout: vi.fn(),
  }),
}));

vi.mock('../hooks/useApi', () => ({
  useApi: () => ({ data: null, loading: false, error: null }),
  apiRequest: vi.fn(),
}));

vi.mock('./MeteoWidget', () => ({ default: () => null }));

describe('Layout', () => {
  it('se render sans crasher', () => {
    render(
      <MemoryRouter>
        <Layout />
      </MemoryRouter>
    );
  });
});
