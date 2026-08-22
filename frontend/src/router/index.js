import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
  { path: '/', redirect: '/login' },
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/auth/Login.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../views/auth/Register.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/register/pending',
    name: 'register-pending',
    component: () => import('../views/auth/RegisterPending.vue'),
  },
  {
    path: '/verify-email',
    name: 'verify-email',
    component: () => import('../views/auth/VerifyEmail.vue'),
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('../views/auth/ForgotPassword.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/forgot-password/sent',
    name: 'forgot-password-sent',
    component: () => import('../views/auth/ForgotPasswordSent.vue'),
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('../views/auth/ResetPassword.vue'),
  },
  {
    path: '/reset-password/complete',
    name: 'reset-password-complete',
    component: () => import('../views/auth/ResetPasswordComplete.vue'),
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('../views/operator/Dashboard.vue'),
    meta: { requiresAuth: true },
  },
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();

  if (!auth.initialized) {
    await auth.fetchCurrentUser();
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } };
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: 'dashboard' };
  }

  return true;
});
