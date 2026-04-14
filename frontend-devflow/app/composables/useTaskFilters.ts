import { useDebounceFn } from '@vueuse/core'
import type { TaskFilters, TaskStatus, TaskPriority } from '~/types'

export function useTaskFilters(projectId: number) {

  const route  = useRoute()
  const router = useRouter()
  const taskStore = useTaskStore()

  // ─────────────────────────────────────────────────────────────────
  // Read initial filter state from the URL query string.
  // This makes filter state bookmarkable and shareable —
  // the URL is the source of truth for filter state.
  // ─────────────────────────────────────────────────────────────────
  const filters = reactive<TaskFilters>({
    search:   (route.query.search   as string)   ?? '',
    status:   (route.query.status   as TaskStatus) ?? '',
    priority: (route.query.priority as TaskPriority) ?? '',
    sort:     (route.query.sort     as string)   ?? '-created_at',
  })

  // ─────────────────────────────────────────────────────────────────
  // DSA — Debounce (Sliding Window on time):
  // Without debounce: typing "login bug" fires 9 API requests.
  // With 300ms debounce: only 1 request fires after typing stops.
  // This is a sliding window over time — only the last event in
  // each 300ms window triggers execution. O(k) requests → O(1).
  // ─────────────────────────────────────────────────────────────────
  const debouncedFetch = useDebounceFn(async () => {
    await taskStore.fetchTasks(projectId, filters)
  }, 300)

  // Sync filter state to the URL as query params
  // This way refreshing the page or sharing the URL preserves filters
  function syncToUrl(): void {
    const query: Record<string, string> = {}

    if (filters.search)   query.search   = filters.search
    if (filters.status)   query.status   = filters.status
    if (filters.priority) query.priority = filters.priority
    if (filters.sort && filters.sort !== '-created_at') query.sort = filters.sort

    router.replace({ query })
  }

  // Watch every filter change, sync URL, and trigger debounced fetch
  watch(filters, () => {
    syncToUrl()
    debouncedFetch()
  })

  function resetFilters(): void {
    filters.search   = ''
    filters.status   = ''
    filters.priority = ''
    filters.sort     = '-created_at'
  }

  return {
    filters,
    resetFilters,
  }
}