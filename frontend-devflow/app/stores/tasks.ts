import { defineStore } from 'pinia'
import type { Task, TaskStatus, TaskFilters, CursorPaginatedResponse } from '~/types'

export const useTaskStore = defineStore('tasks', () => {

  const tasks       = ref<Task[]>([])
  const loading     = ref(false)
  const loadingMore = ref(false)
  const error       = ref<string | null>(null)
  const nextCursor  = ref<string | null>(null)
  const hasMore     = computed(() => nextCursor.value !== null)

  const byStatus = computed(() => (status: TaskStatus): Task[] =>
    tasks.value.filter(t => t.status === status)
  )
  const todoTasks       = computed((): Task[] => byStatus.value('todo'))
  const inProgressTasks = computed((): Task[] => byStatus.value('in_progress'))
  const doneTasks       = computed((): Task[] => byStatus.value('done'))
  const totalCount      = computed(() => tasks.value.length)
  const overdueCount    = computed(() => tasks.value.filter(t => t.is_overdue).length)

  async function fetchTasks(
    projectId: number,
    filters: Partial<TaskFilters> = {}
  ): Promise<void> {
    loading.value    = true
    error.value      = null
    nextCursor.value = null

    try {
      const { get } = useApi()

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
      nextCursor.value = response.meta?.next_cursor ?? null
    } catch (err: any) {
      error.value = err?.data?.message ?? 'Failed to load tasks.'
    } finally {
      loading.value = false
    }
  }

  async function fetchMore(
    projectId: number,
    filters: Partial<TaskFilters> = {}
  ): Promise<void> {
    if (!nextCursor.value || loadingMore.value) return
    loadingMore.value = true

    try {
      const { get } = useApi()
      const params: Record<string, string> = { cursor: nextCursor.value }

      if (filters.search)   params['filter[search]']   = filters.search
      if (filters.status)   params['filter[status]']   = filters.status
      if (filters.priority) params['filter[priority]'] = filters.priority
      if (filters.sort)     params['sort']              = filters.sort

      const response = await get<CursorPaginatedResponse<Task>>(
        `/projects/${projectId}/tasks`,
        params
      )

      tasks.value.push(...response.data)
      nextCursor.value = response.meta?.next_cursor ?? null
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
    const { post }  = useApi()
    const response  = await post<{ data: Task }>(`/projects/${projectId}/tasks`, payload)
    tasks.value.unshift(response.data)
    return response.data
  }

  async function updateTask(
    taskId: number,
    payload: Partial<Pick<Task, 'title' | 'description' | 'priority' | 'due_date'>>
  ): Promise<Task> {
    const { patch } = useApi()
    const response  = await patch<{ data: Task }>(`/tasks/${taskId}`, payload)

    const index = tasks.value.findIndex(t => t.id === taskId)
    if (index !== -1) {
      // Use splice for full Vue 3 reactivity
      tasks.value.splice(index, 1, response.data)
    }

    return response.data
  }

 async function updateStatus(taskId: number, newStatus: TaskStatus): Promise<void> {
  const index = tasks.value.findIndex(t => t.id === taskId)
  if (index === -1) return

  const previousStatus = tasks.value[index].status

  // Optimistic update in the store
  tasks.value.splice(index, 1, {
    ...tasks.value[index],
    status: newStatus,
  })

  try {
    const { patch } = useApi()
    await patch(`/tasks/${taskId}`, { status: newStatus })
  } catch (err: any) {
    // Rollback in the store — KanbanBoard's watch will pick this up
    // and revert the column lists automatically
    tasks.value.splice(index, 1, {
      ...tasks.value[index],
      status: previousStatus,
    })
    throw err
  }
}

  async function deleteTask(projectId: number, taskId: number): Promise<void> {
    const { destroy } = useApi()
    // Shallow route — no project prefix
    await destroy(`/tasks/${taskId}`)
    tasks.value = tasks.value.filter(t => t.id !== taskId)
  }

  // Used by WebSocket handlers to apply remote updates reactively
  function applyRemoteTaskUpdate(updatedTask: Task): void {
    const index = tasks.value.findIndex(t => t.id === updatedTask.id)
    if (index !== -1) {
      tasks.value.splice(index, 1, updatedTask)
    }
  }

  function applyRemoteStatusChange(taskId: number, newStatus: TaskStatus): void {
    const index = tasks.value.findIndex(t => t.id === taskId)
    if (index !== -1) {
      tasks.value.splice(index, 1, {
        ...tasks.value[index],
        status: newStatus,
      })
    }
  }

  function applyRemoteTaskCreate(newTask: Task): void {
    const exists = tasks.value.find(t => t.id === newTask.id)
    if (!exists) {
      tasks.value.unshift(newTask)
    }
  }

  function applyRemoteTaskDelete(taskId: number): void {
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
    applyRemoteTaskUpdate,
    applyRemoteStatusChange,
    applyRemoteTaskCreate,
    applyRemoteTaskDelete,
    clearTasks,
  }
})