// Generic "must be logged in" guard shared by every app's router. Each app
// supplies its own Pinia store and route names since sessions are
// completely independent per app (operator vs admin use separate tables,
// separate Authentication guards - see docs/specifications/500_Admin.md
// §501 and Application::getAdminAuthenticationService()).
export function createRequireAuth({ useStore, fetchCurrentIdentity, loginRouteName, homeRouteName }) {
  return async (to) => {
    const auth = useStore();

    if (!auth.initialized) {
      await fetchCurrentIdentity(auth);
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
      return { name: loginRouteName, query: { redirect: to.fullPath } };
    }

    if (to.meta.guestOnly && auth.isAuthenticated) {
      return { name: homeRouteName };
    }

    return true;
  };
}
