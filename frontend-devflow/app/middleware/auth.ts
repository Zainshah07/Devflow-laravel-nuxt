export default defineNuxtRouteMiddleware(async (to) => {
  // Skip auth check on public pages
  const publicRoutes = ['/login', '/register']
  if (publicRoutes.includes(to.path)) return

  // CRITICAL: Skip server-side auth check entirely.
  // The backend is not reachable via localhost inside Docker during SSR.
  // Auth is handled client-side only.
  if (import.meta.server) return

  const authStore = useAuthStore()

  if (authStore.isAuthenticated) return

  const refreshed = await authStore.silentRefresh()

  if (!refreshed) {
    return navigateTo('/login')
  }
})