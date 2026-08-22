import { apiGet, apiPost, apiPut } from './client';

export function listEvents() {
  return apiGet('/events');
}

export function getEvent(id) {
  return apiGet(`/events/${id}`);
}

export function createEvent(payload) {
  return apiPost('/events', payload);
}

export function updateEvent(id, payload) {
  return apiPut(`/events/${id}`, payload);
}
