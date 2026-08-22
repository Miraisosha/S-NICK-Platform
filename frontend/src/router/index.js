import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useAdminAuthStore } from '../stores/adminAuth';

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
  {
    path: '/events',
    name: 'events',
    component: () => import('../views/operator/events/EventList.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/events/new',
    name: 'events-new',
    component: () => import('../views/operator/events/EventForm.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/events/:id/edit',
    name: 'events-edit',
    component: () => import('../views/operator/events/EventForm.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/events/:id/categories',
    name: 'events-categories',
    component: () => import('../views/operator/events/CategoryList.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/events/:id/courts',
    name: 'events-courts',
    component: () => import('../views/operator/events/EventCourts.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/login',
    name: 'admin-login',
    component: () => import('../views/admin/AdminLogin.vue'),
    meta: { adminGuestOnly: true },
  },
  { path: '/admin', redirect: '/admin/facilities' },
  {
    path: '/admin/facilities',
    name: 'admin-facilities',
    component: () => import('../views/admin/Facilities.vue'),
    meta: { requiresAdminAuth: true },
  },
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to) => {
  // Admin routes are checked against a completely separate store/session
  // from operator routes (see docs/specifications/500_Admin.md §501 and
  // Application::getAdminAuthenticationService()) - being logged in as one
  // says nothing about the other, so each guard only ever touches its own
  // store.
  if (to.meta.requiresAdminAuth || to.meta.adminGuestOnly) {
    const adminAuth = useAdminAuthStore();

    if (!adminAuth.initialized) {
      await adminAuth.fetchCurrentAdmin();
    }

    if (to.meta.requiresAdminAuth && !adminAuth.isAuthenticated) {
      return { name: 'admin-login', query: { redirect: to.fullPath } };
    }

    if (to.meta.adminGuestOnly && adminAuth.isAuthenticated) {
      return { name: 'admin-home' };
    }

    return true;
  }

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
