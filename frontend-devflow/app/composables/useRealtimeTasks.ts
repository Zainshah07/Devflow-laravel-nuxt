import type { Task, TaskStatus } from '~/types'

export function useRealtimeTasks(projectId: number) {
  const { $echo }     = useNuxtApp()
  const taskStore     = useTaskStore()
  const presenceStore = usePresenceStore()

  let privateChannel:  any = null
  let presenceChannel: any = null

  function subscribe(): void {
    if (!$echo) {
      console.warn('Echo not available')
      return
    }

    cleanup()

    // ── Private channel — task events ──────────────────────────────
    try {
      privateChannel = ($echo as any).private(`project.${projectId}`)

      privateChannel
        .listen('.task.created', (data: any) => {
          const task: Task = data.data ?? data
          // Check duplicate before adding
          const exists = taskStore.tasks.find((t: Task) => t.id === task.id)
          if (!exists) {
            taskStore.tasks.unshift(task)
          }
        })
        .listen('.task.updated', (data: any) => {
          const task: Task = data.data ?? data
          const index = taskStore.tasks.findIndex((t: Task) => t.id === task.id)
          if (index !== -1) {
            // splice triggers Vue 3 reactivity — index assignment does not
            taskStore.tasks.splice(index, 1, task)
          }
        })
        .listen('.task.status_changed', (data: any) => {
          const index = taskStore.tasks.findIndex((t: Task) => t.id === data.task_id)
          if (index !== -1) {
            // Create a new object with the updated status using splice
            taskStore.tasks.splice(index, 1, {
              ...taskStore.tasks[index],
              status: data.new_status as TaskStatus,
            })
          }
        })
        .listen('.task.deleted', (data: any) => {
          taskStore.tasks = taskStore.tasks.filter((t: Task) => t.id !== data.task_id)
        })

      console.log(`Subscribed to private channel: project.${projectId}`)
    } catch (err) {
      console.error('Failed to subscribe to private channel:', err)
    }

    // ── Presence channel ───────────────────────────────────────────
    try {
      presenceChannel = ($echo as any).join(`project.${projectId}`)

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
          console.error('Presence channel error:', err)
          presenceStore.setConnected(false)
        })

      console.log(`Joined presence channel: project.${projectId}`)
    } catch (err) {
      console.error('Failed to join presence channel:', err)
    }
  }

  function cleanup(): void {
    if (privateChannel || presenceChannel) {
      try {
        ($echo as any).leave(`project.${projectId}`)
      } catch {}
      privateChannel  = null
      presenceChannel = null
    }
  }

  function unsubscribe(): void {
    cleanup()
    presenceStore.reset()
  }

  return { subscribe, unsubscribe }
}