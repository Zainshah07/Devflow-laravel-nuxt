import { defineStore } from 'pinia'
import type { Task, TaskStatus } from '~/types'

export const useTaskStore = defineStore('tasks', () => {
  const tasks = ref<Task[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // DSA: byStatus is a computed property that filters the tasks array
  // This is O(n) array traversal with a predicate — same as filter() in any DSA problem.
  // Vue memoizes this — it only recalculates when the tasks array changes.
  // This is the same concept as memoization in dynamic programming:
  // cache the result, only recompute when inputs change.
  const byStatus = computed(() => (status: TaskStatus) =>
    tasks.value.filter(t => t.status === status)
  )

  const todoTasks = computed(() => byStatus.value('todo'))
  const inProgressTasks = computed(() => byStatus.value('in_progress'))
  const doneTasks = computed(() => byStatus.value('done'))

  async function fetchTasks(projectId: number) {
    loading.value = true
    error.value = null

    try {
      const { get } = useApi()
      const response = await get<{ data: Task[] }>(`/projects/${projectId}/tasks`)
      tasks.value = response.data
    } catch (err: any) {
      error.value = err?.data?.message ?? 'Failed to fetch tasks'
    } finally {
      loading.value = false
    }
  }

  async function createTask(projectId: number, payload: {
    title: string
    description?: string
    priority: string
    due_date?: string
  }) {
    const { post } = useApi()
    const response = await post<{ data: Task }>(`/projects/${projectId}/tasks`, payload)

    // Prepend to array so the new task appears at the top
    // DSA: array prepend with unshift is O(n) — acceptable at this scale
    tasks.value.unshift(response.data)
    return response.data
  }

  async function updateStatus(taskId: number, newStatus: TaskStatus) {
    const { patch } = useApi()

    // Optimistic update: update local state immediately before API confirms
    // DSA: find() is O(n) linear search — finding the node to update
    const task = tasks.value.find(t => t.id === taskId)
    if (!task) return

    const previousStatus = task.status
    task.status = newStatus

    try {
      await patch(`/tasks/${taskId}`, { status: newStatus })
    } catch (err) {
      // Rollback optimistic update on failure
      task.status = previousStatus
      throw err
    }
  }

  function clearTasks() {
    tasks.value = []
  }

  return {
    tasks,
    loading,
    error,
    byStatus,
    todoTasks,
    inProgressTasks,
    doneTasks,
    fetchTasks,
    createTask,
    updateStatus,
    clearTasks,
  }
})