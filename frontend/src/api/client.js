const BASE_URL = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8080/api/v1';
const ORIGIN = BASE_URL.replace(/\/api\/v1\/?$/, '');

// Resolves a server-relative path (e.g. an Event's `logo`, like
// "uploads/events/12/logo.png") into an absolute URL pointing at the API
// host's webroot.
export function resolveUploadUrl(path) {
  if (!path) {
    return null;
  }

  return `${ORIGIN}/${path}`;
}

export class ApiError extends Error {
  constructor(code, message, status, extra = {}) {
    super(message);
    this.name = 'ApiError';
    this.code = code;
    this.status = status;
    this.extra = extra;
  }
}

async function request(path, { method = 'GET', body } = {}) {
  const response = await fetch(`${BASE_URL}${path}`, {
    method,
    credentials: 'include',
    headers: body !== undefined ? { 'Content-Type': 'application/json' } : {},
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    const error = payload?.error ?? {};
    const { code, message, ...extra } = error;
    throw new ApiError(
      code ?? 'unknown_error',
      message ?? 'エラーが発生しました。',
      response.status,
      extra,
    );
  }

  return payload?.data;
}

export function apiGet(path) {
  return request(path);
}

export function apiPost(path, body = {}) {
  return request(path, { method: 'POST', body });
}

export function apiPut(path, body = {}) {
  return request(path, { method: 'PUT', body });
}

export function apiDelete(path) {
  return request(path, { method: 'DELETE' });
}

async function requestForm(path, formData) {
  const response = await fetch(`${BASE_URL}${path}`, {
    method: 'POST',
    credentials: 'include',
    body: formData,
  });

  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    const error = payload?.error ?? {};
    const { code, message, ...extra } = error;
    throw new ApiError(
      code ?? 'unknown_error',
      message ?? 'エラーが発生しました。',
      response.status,
      extra,
    );
  }

  return payload?.data;
}

// multipart/form-data requests (file uploads). PUT/PATCH bodies aren't
// parsed by PHP for file uploads, so edits use CakePHP's `_method`
// override field over a real POST instead.
export function apiPostForm(path, formData) {
  return requestForm(path, formData);
}

export function apiPutForm(path, formData) {
  formData.append('_method', 'PUT');
  return requestForm(path, formData);
}
