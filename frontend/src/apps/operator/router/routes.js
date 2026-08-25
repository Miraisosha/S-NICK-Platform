export const routes = [
  { path: '/', redirect: '/login' },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/apps/operator/views/auth/Login.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/apps/operator/views/auth/Register.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/register/pending',
    name: 'register-pending',
    component: () => import('@/apps/operator/views/auth/RegisterPending.vue'),
  },
  {
    path: '/verify-email',
    name: 'verify-email',
    component: () => import('@/apps/operator/views/auth/VerifyEmail.vue'),
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/apps/operator/views/auth/ForgotPassword.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/forgot-password/sent',
    name: 'forgot-password-sent',
    component: () => import('@/apps/operator/views/auth/ForgotPasswordSent.vue'),
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('@/apps/operator/views/auth/ResetPassword.vue'),
  },
  {
    path: '/reset-password/complete',
    name: 'reset-password-complete',
    component: () => import('@/apps/operator/views/auth/ResetPasswordComplete.vue'),
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/apps/operator/views/Dashboard.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/events',
    name: 'events',
    component: () => import('@/apps/operator/views/events/EventList.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/events/new',
    name: 'events-new',
    component: () => import('@/apps/operator/views/events/EventWizard.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/events/:id/edit',
    name: 'events-edit',
    component: () => import('@/apps/operator/views/events/EventForm.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/events/:id/categories',
    name: 'events-categories',
    component: () => import('@/apps/operator/views/events/CategoryList.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/events/:id/courts',
    name: 'events-courts',
    component: () => import('@/apps/operator/views/events/EventCourts.vue'),
    meta: { requiresAuth: true },
  },
];
