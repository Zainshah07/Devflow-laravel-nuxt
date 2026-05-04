import { useDebounceFn } from '@vueuse/core'
import type { TaskFilters, TaskStatus, TaskPriority } from '~/types'

export function useTaskFilters(projectId: number) {
  const route  = useRoute()
  const router = useRouter()
  const taskStore = useTaskStore()

  const filters = reactive<TaskFilters>({
    search:   (route.query.search   as string)       ?? '',
    status:   (route.query.status   as TaskStatus)   ?? '',
    priority: (route.query.priority as TaskPriority) ?? '',
    sort:     (route.query.sort     as string)       ?? '-created_at',
  })

  // Track whether the initial load has happened
  // The watcher should only fire on USER changes, not on mount
  let initialized = false

  const debouncedFetch = useDebounceFn(async () => {
    await taskStore.fetchTasks(projectId, filters)
  }, 300)

  function syncToUrl(): void {
    const query: Record<string, string> = {}
    if (filters.search)                               query.search   = filters.search
    if (filters.status)                               query.status   = filters.status
    if (filters.priority)                             query.priority = filters.priority
    if (filters.sort && filters.sort !== '-created_at') query.sort   = filters.sort
    router.replace({ query })
  }

  // Only watch AFTER initialization so the first mount fetch
  // does not trigger a second fetch from the watcher
  watch(filters, () => {
    if (!initialized) return
    syncToUrl()
    debouncedFetch()
  })

  function markInitialized(): void {
    initialized = true
  }

  function resetFilters(): void {
    filters.search   = ''
    filters.status   = ''
    filters.priority = ''
    filters.sort     = '-created_at'
  }

  return {
    filters,
    resetFilters,
    markInitialized,
  }
}