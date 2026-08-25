import { createApp } from 'vue';
import { createPinia } from 'pinia';
import AppRoot from '@/components/common/AppRoot.vue';
import { createAppRouter } from '@/router/createAppRouter';
import { createRequireAuth } from '@/router/guards/requireAuth';
import { useAuthStore } from '@/stores/auth';
import { routes } from './router/routes';

const router = createAppRouter({
  routes,
  guards: [
    createRequireAuth({
      useStore: useAuthStore,
      fetchCurrentIdentity: (auth) => auth.fetchCurrentUser(),
      loginRouteName: 'login',
      homeRouteName: 'dashboard',
    }),
  ],
});

const app = createApp(AppRoot);
app.use(createPinia());
app.use(router);
app.mount('#app');
