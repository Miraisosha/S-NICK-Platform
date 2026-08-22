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

// Read-only, operator-authenticated (not /admin/*): SCR-OPR-261 lets
// operators browse the facility/court master to select courts for an
// event, without the create/edit/delete access above (admin-only).
export function listFacilitiesForOperator() {
  return apiGet('/facilities');
}
