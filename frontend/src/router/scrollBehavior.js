// Shared by every app's router (see router/createAppRouter.js): restore the
// saved position on back/forward navigation, otherwise scroll to top.
export function scrollBehavior(to, from, savedPosition) {
  return savedPosition ?? { top: 0 };
}
