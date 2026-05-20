import { useState, useEffect, useCallback, useRef } from 'react';
import { useAuth } from '../context/AuthContext';

const API_BASE = import.meta.env.VITE_API_URL;
const CACHE_TTL = 30_000; // 30 secondes

// Cache module-level : survit aux navigations entre pages
const cache = new Map(); // path → { data, ts }

function handleUnauthorized() {
  localStorage.removeItem('jwt_token');
  localStorage.removeItem('user');
  window.location.href = '/login';
}

export function invalidateCache(path) {
  if (path) {
    cache.delete(path);
  } else {
    cache.clear();
  }
}

export function useApi(path, { skip = false } = {}) {
  const { token } = useAuth();

  const hit = cache.get(path);
  const isFresh = hit && (Date.now() - hit.ts < CACHE_TTL);

  const [data, setData] = useState(isFresh ? hit.data : null);
  const [loading, setLoading] = useState(!skip && !isFresh);
  const [error, setError] = useState(null);
  const mountedRef = useRef(true);

  useEffect(() => {
    mountedRef.current = true;
    return () => { mountedRef.current = false; };
  }, []);

  const fetchData = useCallback(async (force = false) => {
    if (!force) {
      const hit = cache.get(path);
      if (hit && Date.now() - hit.ts < CACHE_TTL) {
        if (mountedRef.current) {
          setData(hit.data);
          setLoading(false);
        }
        return;
      }
    }

    if (mountedRef.current) {
      setLoading(true);
      setError(null);
    }

    try {
      const res = await fetch(`${API_BASE}${path}`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      if (res.status === 401) { handleUnauthorized(); return; }
      if (!res.ok) throw new Error(`Erreur ${res.status}`);
      const json = await res.json();

      cache.set(path, { data: json, ts: Date.now() });

      if (mountedRef.current) setData(json);
    } catch (err) {
      if (mountedRef.current) setError(err.message);
    } finally {
      if (mountedRef.current) setLoading(false);
    }
  }, [path, token]);

  useEffect(() => {
    if (!skip && token) fetchData();
  }, [fetchData, skip, token]);

  // refetch force-bypass le cache (après une mutation)
  const refetch = useCallback(() => {
    cache.delete(path);
    fetchData(true);
  }, [fetchData, path]);

  return { data, loading, error, refetch };
}

export async function apiRequest(path, method, body, token) {
  const res = await fetch(`${API_BASE}${path}`, {
    method,
    headers: {
      ...(method !== 'GET' && { 'Content-Type': 'application/json' }),
      Authorization: `Bearer ${token}`,
    },
    body: body ? JSON.stringify(body) : undefined,
  });
  if (res.status === 401) { handleUnauthorized(); return; }
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error(data.message || `Erreur ${res.status}`);
  }
  return res.status === 204 ? null : res.json();
}
