import { apiDelete, apiGet, apiPost, apiPostForm, apiPut, apiPutForm } from './client';

export function listEvents() {
  return apiGet('/events');
}

export function getEvent(id) {
  return apiGet(`/events/${id}`);
}

// `logoFile` is an optional File - when present the request is sent as
// multipart/form-data instead of JSON so EventsController::add()/edit()
// can read it via getUploadedFile('logo').
function toFormData(payload, logoFile) {
  const formData = new FormData();
  for (const [key, value] of Object.entries(payload)) {
    if (value !== null && value !== undefined) {
      formData.append(key, value);
    }
  }
  formData.append('logo', logoFile);

  return formData;
}

export function createEvent(payload, logoFile) {
  if (logoFile) {
    return apiPostForm('/events', toFormData(payload, logoFile));
  }

  return apiPost('/events', payload);
}

export function updateEvent(id, payload, logoFile) {
  if (logoFile) {
    return apiPutForm(`/events/${id}`, toFormData(payload, logoFile));
  }

  return apiPut(`/events/${id}`, payload);
}

export function removeEventLogo(id) {
  return apiPut(`/events/${id}`, { remove_logo: true });
}

export function deleteEvent(id) {
  return apiDelete(`/events/${id}`);
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
