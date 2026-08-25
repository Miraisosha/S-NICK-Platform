import { apiDelete, apiGet, apiPost, apiPut } from './client';

export function listCategories(eventId) {
  return apiGet(`/events/${eventId}/categories`);
}

export function createCategory(eventId, payload) {
  return apiPost(`/events/${eventId}/categories`, payload);
}

export function updateCategory(eventId, id, payload) {
  return apiPut(`/events/${eventId}/categories/${id}`, payload);
}

export function deleteCategory(eventId, id) {
  return apiDelete(`/events/${eventId}/categories/${id}`);
}
