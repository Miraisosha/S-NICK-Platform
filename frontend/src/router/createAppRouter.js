import { createRouter, createWebHistory } from 'vue-router';
import { scrollBehavior } from './scrollBehavior';

// Shared factory used by each app's main.js (see apps/*/router/routes.js) -
// keeps history mode and scroll behavior consistent across apps, and
// composes each app's own guards (see router/guards/) into one beforeEach.
export function createAppRouter({ routes, guards = [] }) {
  const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior,
  });

  router.beforeEach(async (to) => {
    for (const guard of guards) {
      const result = await guard(to);
      if (result !== true) {
        return result;
      }
    }
    return true;
  });

  return router;
}
