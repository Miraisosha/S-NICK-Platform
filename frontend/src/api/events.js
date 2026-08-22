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

export function getEventCourts(id) {
  return apiGet(`/events/${id}/courts`);
}

export function updateEventCourts(id, courtIds) {
  return apiPut(`/events/${id}/courts`, { court_ids: courtIds });
}

export function getEventUsageTimes(id) {
  return apiGet(`/events/${id}/usage-times`);
}

export function updateEventUsageTimes(id, usageTimes) {
  return apiPut(`/events/${id}/usage-times`, { usage_times: usageTimes });
}
