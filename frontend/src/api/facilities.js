import { apiDelete, apiGet, apiPost, apiPut } from './client';

export function listFacilities() {
  return apiGet('/admin/facilities');
}

export function createFacility(payload) {
  return apiPost('/admin/facilities', payload);
}

export function updateFacility(id, payload) {
  return apiPut(`/admin/facilities/${id}`, payload);
}

export function deleteFacility(id) {
  return apiDelete(`/admin/facilities/${id}`);
}
