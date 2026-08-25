import { createApp } from 'vue';
import { createPinia } from 'pinia';
import AppRoot from '@/components/common/AppRoot.vue';
import { createAppRouter } from '@/router/createAppRouter';
import { createRequireAuth } from '@/router/guards/requireAuth';
import { useAdminAuthStore } from '@/stores/adminAuth';
import { routes } from './router/routes';

const router = createAppRouter({
  routes,
  guards: [
    createRequireAuth({
      useStore: useAdminAuthStore,
      fetchCurrentIdentity: (auth) => auth.fetchCurrentAdmin(),
      loginRouteName: 'admin-login',
      homeRouteName: 'admin-facilities',
    }),
  ],
});

const app = createApp(AppRoot);
app.use(createPinia());
app.use(router);
app.mount('#app');
