import { defineStore } from 'pinia'
import type { Task, TaskStatus, TaskFilters, CursorPaginatedResponse } from '~/types'

export const useTaskStore = defineStore('tasks', () => {

  const tasks      = ref<Task[]>([])
  const loading    = ref(false)
  const loadingMore = ref(false)
  const error      = ref<string | null>(null)
  const nextCursor = ref<string | null>(null)
  const hasMore    = computed(() => nextCursor.value !== null)

  // ─────────────────────────────────────────────────────────────────
  // DSA — Array Filtering O(n) + Memoization:
  // Each byStatus computed does a single linear scan — O(n).
  // Vue memoizes the result and only recalculates when tasks changes.
  // Three computed properties = at most 3 × O(n) = O(n) total.
  // Same concept as memoized subproblem results in DP.
  // ─────────────────────────────────────────────────────────────────
const byStatus = computed(() => (status: TaskStatus): Task[] =>
  tasks.value.filter(t => t.status === status)
)

// These must call byStatus.value(status) — not byStatus(status)
const todoTasks       = computed((): Task[] => byStatus.value('todo'))
const inProgressTasks = computed((): Task[] => byStatus.value('in_progress'))
const doneTasks       = computed((): Task[] => byStatus.value('done'))
  const totalCount      = computed(() => tasks.value.length)
  const overdueCount    = computed(() => tasks.value.filter(t => t.is_overdue).length)

  // ─────────────────────────────────────────────────────────────────
  // DSA — Cursor Pagination O(log n):
  // fetchTasks builds query params from the filters object.
  // The API uses cursor pagination — MySQL jumps to the cursor row
  // via primary key index lookup (O(log n)) rather than scanning
  // and discarding offset rows (O(n)).
  // ─────────────────────────────────────────────────────────────────
  async function fetchTasks(
    projectId: number,
    filters: Partial<TaskFilters> = {}
  ): Promise<void> {
    loading.value  = true
    error.value    = null
    nextCursor.value = null

    try {
      const { get } = useApi()

      // Build query params — only include non-empty values
      // DSA: building this params object is hash map construction O(k)
      // where k is the number of active filters
      const params: Record<string, string> = {}

      if (filters.search)   params['filter[search]']   = filters.search
      if (filters.status)   params['filter[status]']   = filters.status
      if (filters.priority) params['filter[priority]'] = filters.priority
      if (filters.sort)     params['sort']              = filters.sort

      const response = await get<CursorPaginatedResponse<Task>>(
        `/projects/${projectId}/tasks`,
        params
      )

      tasks.value      = response.data
      nextCursor.value = response.meta.next_cursor

    } catch (err: any) {
      error.value = err?.data?.message ?? 'Failed to load tasks.'
    } finally {
      loading.value = false
    }
  }

  // Load the next page of tasks and append to the existing array
  async function fetchMore(projectId: number, filters: Partial<TaskFilters> = {}): Promise<void> {
    if (!nextCursor.value || loadingMore.value) return

    loadingMore.value = true

    try {
      const { get } = useApi()

      const params: Record<string, string> = {
        cursor: nextCursor.value,
      }

      if (filters.search)   params['filter[search]']   = filters.search
      if (filters.status)   params['filter[status]']   = filters.status
      if (filters.priority) params['filter[priority]'] = filters.priority
      if (filters.sort)     params['sort']              = filters.sort

      const response = await get<CursorPaginatedResponse<Task>>(
        `/projects/${projectId}/tasks`,
        params
      )

      // DSA — Array concatenation:
      // push(...array) is O(m) where m is the new items count.
      // More efficient than creating a new array with spread.
      tasks.value.push(...response.data)
      nextCursor.value = response.meta.next_cursor

    } finally {
      loadingMore.value = false
    }
  }

  async function createTask(
    projectId: number,
    payload: {
      title:        string
      description?: string
      priority:     string
      due_date?:    string
    }
  ): Promise<Task> {
    const { post } = useApi()
    const response = await post<{ data: Task }>(`/projects/${projectId}/tasks`, payload)

    tasks.value.unshift(response.data)

    return response.data
  }

  async function updateTask(
    taskId: number,
    payload: Partial<Pick<Task, 'title' | 'description' | 'priority' | 'due_date'>>
  ): Promise<Task> {
    const { patch } = useApi()
    const response  = await patch<{ data: Task }>(`/tasks/${taskId}`, payload)

    // DSA: findIndex is O(n), direct index write is O(1)
    const index = tasks.value.findIndex(t => t.id === taskId)
    if (index !== -1) tasks.value[index] = response.data

    return response.data
  }

  async function updateStatus(taskId: number, newStatus: TaskStatus): Promise<void> {
    // DSA — Optimistic update with rollback:
    // Mutate locally first (O(n) find + O(1) assign),
    // send API request, revert on failure.
    const task = tasks.value.find(t => t.id === taskId)
    if (!task) return

    const previousStatus = task.status
    task.status = newStatus

    try {
      const { patch } = useApi()
      await patch(`/tasks/${taskId}`, { status: newStatus })
    } catch (err: any) {
      task.status = previousStatus
      throw err
    }
  }

async function deleteTask(projectId: number, taskId: number): Promise<void> {
  const { destroy } = useApi()

  // Route is /api/tasks/:id (shallow resource — no project prefix)
  await destroy(`/tasks/${taskId}`)

  tasks.value = tasks.value.filter(t => t.id !== taskId)
}

  function clearTasks(): void {
    tasks.value      = []
    nextCursor.value = null
    error.value      = null
  }

  return {
    tasks,
    loading,
    loadingMore,
    error,
    nextCursor,
    hasMore,
    byStatus,
    todoTasks,
    inProgressTasks,
    doneTasks,
    totalCount,
    overdueCount,
    fetchTasks,
    fetchMore,
    createTask,
    updateTask,
    updateStatus,
    deleteTask,
    clearTasks,
  }
})