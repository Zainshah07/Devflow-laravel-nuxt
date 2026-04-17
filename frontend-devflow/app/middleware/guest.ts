export default defineNuxtRouteMiddleware(async () => {
  // Skip server-side check
  if (import.meta.server) return

  const authStore = useAuthStore()

  if (authStore.isAuthenticated) {
    return navigateTo('/')
  }

  const refreshed = await authStore.silentRefresh()
  if (refreshed) {
    return navigateTo('/')
  }
})