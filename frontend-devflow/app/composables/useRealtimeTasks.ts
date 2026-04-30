import type { Task, TaskStatus } from '~/types'

export function useRealtimeTasks(projectId: number) {
  const { $echo }       = useNuxtApp()
  const taskStore       = useTaskStore()
  const presenceStore   = usePresenceStore()

  let privateChannel: any  = null
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
          const task = data.data ?? data
          if (!taskStore.tasks.find(t => t.id === task.id)) {
            taskStore.tasks.unshift(task)
          }
        })
        .listen('.task.updated', (data: any) => {
          const task  = data.data ?? data
          const index = taskStore.tasks.findIndex(t => t.id === task.id)
          if (index !== -1) {
            taskStore.tasks[index] = task
          }
        })
        .listen('.task.status_changed', (data: any) => {
          const task = taskStore.tasks.find(t => t.id === data.task_id)
          if (task) task.status = data.new_status as TaskStatus
        })
        .listen('.task.deleted', (data: any) => {
          taskStore.tasks = taskStore.tasks.filter(t => t.id !== data.task_id)
        })

      console.log(`Subscribed to private channel: project.${projectId}`)
    } catch (err) {
      console.error('Failed to subscribe to private channel:', err)
    }

    // ── Presence channel — who is viewing ──────────────────────────
    // Echo.join() subscribes to the presence channel.
    // The channel name passed here is WITHOUT the "presence-" prefix —
    // Echo adds it internally. So join('project.1') → presence-project.1
    // The authorization callback in channels.php handles both.
    try {
      presenceChannel = ($echo as any).join(`project.${projectId}`)

      presenceChannel
        .here((users: Array<{ id: number; name: string }>) => {
          console.log('Presence here — users online:', users)
          presenceStore.setUsers(users)
          presenceStore.setConnected(true)
        })
        .joining((user: { id: number; name: string }) => {
          console.log('User joining presence:', user)
          presenceStore.addUser(user)
        })
        .leaving((user: { id: number; name: string }) => {
          console.log('User leaving presence:', user)
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
    if (privateChannel) {
      try {
        ($echo as any).leave(`project.${projectId}`)
      } catch {}
      privateChannel = null
    }
    if (presenceChannel) {
      // Presence channel leave is handled by the same leave call above
      presenceChannel = null
    }
  }

  function unsubscribe(): void {
    cleanup()
    presenceStore.reset()
  }

  return { subscribe, unsubscribe }
}