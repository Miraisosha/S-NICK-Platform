// Paths have no `/admin/` prefix - this app is deployed at its own
// subdomain (see docs/specifications/010_SystemArchitecture.md), so the
// prefix that used to disambiguate it from operator routes within one
// shared router is no longer needed.
export const routes = [
  { path: '/', redirect: '/login' },
  {
    path: '/login',
    name: 'admin-login',
    component: () => import('@/apps/admin/views/AdminLogin.vue'),
    meta: { adminGuestOnly: true },
  },
  {
    path: '/facilities',
    name: 'admin-facilities',
    component: () => import('@/apps/admin/views/Facilities.vue'),
    meta: { requiresAdminAuth: true },
  },
];
