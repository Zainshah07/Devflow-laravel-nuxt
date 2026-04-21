export interface HealthCheck {
  status:   string
  server:   string
  checks: {
    database:           string
    database_replica?:  string
    redis:              string
  }
}

export function useServerStatus() {
  const health  = ref<HealthCheck | null>(null)
  const loading = ref(false)
  const error   = ref(false)

  const isHealthy = computed(() => health.value?.status === 'ok')

  const serverName = computed(() => health.value?.server ?? null)

  const dbConnected = computed(() =>
    health.value?.checks?.database === 'connected'
  )

  const replicaConnected = computed(() =>
    health.value?.checks?.database_replica === 'connected'
  )

  const redisConnected = computed(() =>
    health.value?.checks?.redis === 'connected'
  )

  async function fetchStatus(): Promise<void> {
    loading.value = true
    error.value   = false

    try {
      const config = useRuntimeConfig()
      // Strip /api suffix to hit the root health endpoint
      const base   = config.public.apiBase.replace(/\/api$/, '')

      health.value = await $fetch<HealthCheck>(`${base}/api/health`, {
        credentials: 'include',
      })
    } catch {
      error.value  = true
      health.value = null
    } finally {
      loading.value = false
    }
  }

  return {
    health,
    loading,
    error,
    isHealthy,
    serverName,
    dbConnected,
    replicaConnected,
    redisConnected,
    fetchStatus,
  }
}