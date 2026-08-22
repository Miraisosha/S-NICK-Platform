import { apiGet, apiPost } from './client';

export function login(email, password) {
  return apiPost('/admin/login', { email, password });
}

export function logout() {
  return apiPost('/admin/logout');
}

export function me() {
  return apiGet('/admin/me');
}
