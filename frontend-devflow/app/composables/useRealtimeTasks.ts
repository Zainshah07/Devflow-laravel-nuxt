import type { Task, TaskStatus } from '~/types'

export function useRealtimeTasks(projectId: number) {
  const nuxtApp       = useNuxtApp()
  const taskStore     = useTaskStore()
  const presenceStore = usePresenceStore()
  const authStore     = useAuthStore()

  let subscribed = false

  function getEchoInstance(): any | null {
    // $echo is now a getter function — call it to get the instance
    const echo = (nuxtApp as any).$echo?.()
    if (!echo) {
      console.warn('[Realtime] Echo instance not available yet')
      return null
    }
    return echo
  }

  function subscribe(): void {
    if (subscribed) {
      unsubscribe()
    }

    // Wait for Echo to be ready with a retry mechanism
    // This handles the case where subscribe() is called before
    // Echo has connected (e.g. after a page load with silent refresh)
    attemptSubscribe(0)
  }

  function attemptSubscribe(attempt: number): void {
    const echo = getEchoInstance()

    if (!echo) {
      if (attempt < 10) {
        // Retry up to 10 times with 500ms delay
        // Total max wait: 5 seconds
        setTimeout(() => attemptSubscribe(attempt + 1), 500)
        return
      }
      console.error('[Realtime] Could not get Echo instance after retries')
      return
    }

    if (!authStore.accessToken) {
      if (attempt < 10) {
        setTimeout(() => attemptSubscribe(attempt + 1), 500)
        return
      }
      console.error('[Realtime] No auth token available after retries')
      return
    }

    doSubscribe(echo)
  }

  function doSubscribe(echo: any): void {
    try {
      // ── Private channel — task CRUD events ───────────────────────
      const privateChannel = echo.private(`project.${projectId}`)

      privateChannel
        .listen('.task.created', (data: any) => {
          const task: Task = data.data ?? data
          const exists = taskStore.tasks.find((t: Task) => t.id === task.id)
          if (!exists) {
            taskStore.tasks.unshift(task)
          }
        })
        .listen('.task.updated', (data: any) => {
          const task: Task  = data.data ?? data
          const index = taskStore.tasks.findIndex((t: Task) => t.id === task.id)
          if (index !== -1) {
            taskStore.tasks.splice(index, 1, task)
          }
        })
        .listen('.task.status_changed', (data: any) => {
          const index = taskStore.tasks.findIndex((t: Task) => t.id === data.task_id)
          if (index !== -1) {
            taskStore.tasks.splice(index, 1, {
              ...taskStore.tasks[index],
              status: data.new_status as TaskStatus,
            })
          }
        })
        .listen('.task.deleted', (data: any) => {
          taskStore.tasks = taskStore.tasks.filter((t: Task) => t.id !== data.task_id)
        })

      console.log(`[Realtime] Subscribed to private channel: project.${projectId}`)

      // ── Presence channel — who is viewing ────────────────────────
      const presenceChannel = echo.join(`project.${projectId}`)

      presenceChannel
        .here((users: Array<{ id: number; name: string }>) => {
          presenceStore.setUsers(users)
          presenceStore.setConnected(true)
        })
        .joining((user: { id: number; name: string }) => {
          presenceStore.addUser(user)
        })
        .leaving((user: { id: number; name: string }) => {
          presenceStore.removeUser(user)
        })
        .error((err: any) => {
          console.error('[Realtime] Presence channel error:', err)
          presenceStore.setConnected(false)
        })

      console.log(`[Realtime] Joined presence channel: project.${projectId}`)
      subscribed = true

    } catch (err) {
      console.error('[Realtime] Subscription failed:', err)
      subscribed = false
    }
  }

  function unsubscribe(): void {
    const echo = getEchoInstance()
    if (echo) {
      try {
        echo.leave(`project.${projectId}`)
      } catch {}
    }
    subscribed = false
    presenceStore.reset()
  }

  return { subscribe, unsubscribe }
}