import { apiGet, apiPost } from './client';

export function register(email, password, passwordConfirm, termsAgreed) {
  return apiPost('/users/register', {
    email,
    password,
    password_confirm: passwordConfirm,
    terms_agreed: termsAgreed,
  });
}

export function resendVerification(email) {
  return apiPost('/users/resend-verification', { email });
}

export function verifyEmail(token) {
  return apiPost('/users/verify-email', { token });
}

export function login(email, password) {
  return apiPost('/users/login', { email, password });
}

export function logout() {
  return apiPost('/users/logout');
}

export function me() {
  return apiGet('/users/me');
}

export function forgotPassword(email) {
  return apiPost('/users/forgot-password', { email });
}

export function resetPassword(token, password, passwordConfirm) {
  return apiPost('/users/reset-password', {
    token,
    password,
    password_confirm: passwordConfirm,
  });
}
