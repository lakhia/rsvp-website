import { goto } from '$app/navigation';

const TIMEOUT_MS = 8000;

export function navigate(path) {
  return goto(__BASE_PATH__ + path);
}

function buildUrl(url, params = {}) {
  const u = new URL(__BASE_PATH__ + '/' + url, window.location.origin);
  for (const [k, v] of Object.entries(params)) {
    if (v !== undefined && v !== null && v !== '') {
      u.searchParams.set(k, v);
    }
  }
  return u.toString();
}

async function request(url, options) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), TIMEOUT_MS);
  try {
    const res = await fetch(url, {
      ...options,
      signal: controller.signal,
      credentials: 'include',
    });
    if (!res.ok) throw new Error(`Server error (${res.status})`);
    return await res.json();
  } catch (e) {
    if (e.name === 'AbortError') throw new Error('Request timed out');
    throw e;
  } finally {
    clearTimeout(timer);
  }
}

export async function get(url, params = {}) {
  return request(buildUrl(url, params), {});
}

export async function post(url, params = {}, body) {
  return request(buildUrl(url, params), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
}

export async function postText(url, params = {}, text) {
  return request(buildUrl(url, params), {
    method: 'POST',
    headers: { 'Content-Type': 'text/csv' },
    body: text,
  });
}
