import { defineStore } from 'pinia'
import type { Task, TaskStatus } from '~/types'

export const useTaskStore = defineStore('tasks', () => {

  const tasks = ref<Task[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // ─────────────────────────────────────────────────────────────────
  // DSA — Array Filtering O(n) + Memoization
  //
  // byStatus performs a linear scan through the tasks array,
  // returning elements where task.status matches the argument.
  // This is identical to the filter step in Two Sum or Group Anagrams
  // where you partition an array into subgroups.
  //
  // Vue's computed() memoizes the result — the filtered array is
  // only recomputed when the tasks ref changes. This is the same
  // concept as caching subproblem results in dynamic programming:
  // store the answer, reuse it until the input changes.
  // ─────────────────────────────────────────────────────────────────
  const byStatus = computed(() => (status: TaskStatus): Task[] =>
    tasks.value.filter(t => t.status === status)
  )

  const todoTasks       = computed((): Task[] => byStatus.value('todo'))
  const inProgressTasks = computed((): Task[] => byStatus.value('in_progress'))
  const doneTasks       = computed((): Task[] => byStatus.value('done'))

  // Total count computed from the array length — O(1) because
  // JavaScript arrays track length as a property
  const totalCount    = computed(() => tasks.value.length)
  const overdueCount  = computed(() => tasks.value.filter(t => t.is_overdue).length)

  async function fetchTasks(projectId: number): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const { get } = useApi()
      const response = await get<{ data: Task[] }>(`/projects/${projectId}/tasks`)
      tasks.value = response.data
    } catch (err: any) {
      error.value = err?.data?.message ?? 'Failed to load tasks.'
    } finally {
      loading.value = false
    }
  }

  async function createTask(
    projectId: number,
    payload: {
      title: string
      description?: string
      priority: string
      due_date?: string
    }
  ): Promise<Task> {
    const { post } = useApi()
    const response = await post<{ data: Task }>(`/projects/${projectId}/tasks`, payload)

    // Prepend new task so it appears at the top of each column
    // DSA: Array prepend — O(n) because all existing elements shift right.
    // Acceptable at this scale. For very large lists you would use a
    // linked list or a sorted map instead.
    tasks.value.unshift(response.data)

    return response.data
  }

  async function updateTask(
    taskId: number,
    payload: Partial<Pick<Task, 'title' | 'description' | 'priority' | 'due_date'>>
  ): Promise<Task> {
    const { patch } = useApi()
    const response = await patch<{ data: Task }>(`/tasks/${taskId}`, payload)

    // Replace the task in the array with the updated version
    // DSA: find index is O(n), then direct index assignment is O(1)
    const index = tasks.value.findIndex(t => t.id === taskId)
    if (index !== -1) {
      tasks.value[index] = response.data
    }

    return response.data
  }

  async function updateStatus(taskId: number, newStatus: TaskStatus): Promise<void> {
    // ─────────────────────────────────────────────────────────────────
    // DSA — Optimistic Update with Rollback
    //
    // We mutate the local array immediately (optimistic) so the UI
    // responds instantly without waiting for the network.
    // If the API rejects the update (e.g. invalid transition),
    // we restore the previous value (rollback).
    //
    // This is the same pattern as tentative writes in two-pointer
    // problems: you make a tentative move, check if it is valid,
    // and revert if not.
    // ─────────────────────────────────────────────────────────────────
    const task = tasks.value.find(t => t.id === taskId)
    if (!task) return

    const previousStatus = task.status
    task.status = newStatus  // optimistic mutation

    try {
      const { patch } = useApi()
      await patch(`/tasks/${taskId}`, { status: newStatus })
    } catch (err: any) {
      task.status = previousStatus  // rollback on failure
      throw err
    }
  }

  async function deleteTask(projectId: number, taskId: number): Promise<void> {
    const { destroy } = useApi()
    await destroy(`/projects/${projectId}/tasks/${taskId}`)

    // DSA: filter creates a new array excluding the deleted task — O(n)
    tasks.value = tasks.value.filter(t => t.id !== taskId)
  }

  function clearTasks(): void {
    tasks.value = []
    error.value = null
  }

  return {
    tasks,
    loading,
    error,
    byStatus,
    todoTasks,
    inProgressTasks,
    doneTasks,
    totalCount,
    overdueCount,
    fetchTasks,
    createTask,
    updateTask,
    updateStatus,
    deleteTask,
    clearTasks,
  }
})