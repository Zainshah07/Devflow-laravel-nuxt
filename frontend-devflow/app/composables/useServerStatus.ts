export interface ServerStatus {
  status:  string
  server:  string
  checks:  {
    database:          string
    database_replica?: string
    redis:             string
  }
}

export function useServerStatus() {
  const status    = ref<ServerStatus | null>(null)
  const loading   = ref(false)

  const isHealthy = computed(() =>
    status.value?.status === 'ok'
  )

  const serverName = computed(() =>
    status.value?.server ?? null
  )

  const replicaConnected = computed(() =>
    status.value?.checks?.database_replica === 'connected'
  )

  async function fetchStatus(): Promise<void> {
    loading.value = true

    try {
      // Call health without the /api prefix
      const config  = useRuntimeConfig()
      const baseUrl = config.public.apiBase.replace('/api', '')

      status.value = await $fetch<ServerStatus>(`${baseUrl}/api/health`)
    } catch {
      status.value = null
    } finally {
      loading.value = false
    }
  }

  return {
    status,
    loading,
    isHealthy,
    serverName,
    replicaConnected,
    fetchStatus,
  }
}