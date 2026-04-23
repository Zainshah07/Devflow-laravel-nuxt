import type { Task, TaskStatus } from '~/types'

export function useRealtimeTasks(projectId: number) {
  const { $echo }   = useNuxtApp()
  const taskStore   = useTaskStore()
  const authStore   = useAuthStore()

  const onlineUsers  = ref<Array<{ id: number; name: string }>>([])
  const isConnected  = ref(false)
  const channelRef   = ref<any>(null)
  const presenceRef  = ref<any>(null)

  function subscribe(): void {
    // 1. Double check the token is actually a valid string
    if (!authStore.accessToken || authStore.accessToken === 'null') {
      return
    }

    const echo = ($echo as any)

    // 2. FORCE RECONNECT: This wipes the old "Bearer null" state from Pusher memory
    if (echo.connector) {
      echo.connector.disconnect()
    }

    // 3. Re-inject the fresh token
    if (echo.connector?.pusher?.config?.auth?.headers) {
      echo.connector.pusher.config.auth.headers.Authorization = `Bearer ${authStore.accessToken}`
    }

    // 4. Reconnect and join
    echo.connector.connect()

    // Private Channel Listeners
    const channel = echo.private(`project.${projectId}`)

    channel.listen('.task.created', (data: { data: Task }) => {
      if (!taskStore.tasks.find(t => t.id === data.data.id)) {
        taskStore.tasks.unshift(data.data)
      }
    })

    channel.listen('.task.updated', (data: { data: Task }) => {
      const index = taskStore.tasks.findIndex(t => t.id === data.data.id)
      if (index !== -1) taskStore.tasks[index] = data.data
    })

    channel.listen('.task.status_changed', (data: any) => {
      const task = taskStore.tasks.find(t => t.id === data.task_id)
      if (task) task.status = data.new_status
    })

    channel.listen('.task.deleted', (data: any) => {
      taskStore.tasks = taskStore.tasks.filter(t => t.id !== data.task_id)
    })

    channel.subscribed(() => isConnected.value = true)
    channel.error(() => isConnected.value = false)
    channelRef.value = channel

    // Presence Channel
    const presence = echo.join(`presence.project.${projectId}`)
    presence
      .here((users: any) => onlineUsers.value = users)
      .joining((user: any) => {
        if (!onlineUsers.value.find(u => u.id === user.id)) onlineUsers.value.push(user)
      })
      .leaving((user: any) => {
        onlineUsers.value = onlineUsers.value.filter(u => u.id !== user.id)
      })

    presenceRef.value = presence
  }

  function unsubscribe(): void {
    if (channelRef.value || presenceRef.value) {
      ($echo as any).leave(`project.${projectId}`)
      ($echo as any).leave(`presence.project.${projectId}`)
      channelRef.value = null
      presenceRef.value = null
    }
    isConnected.value = false
  }

  // Watch for token changes (Silent Refresh finishing)
  watch(() => authStore.accessToken, (newToken) => {
    if (newToken && newToken !== 'null') {
      console.log('Realtime: Valid token received, resetting connection...')
      unsubscribe()
      subscribe()
    }
  }, { immediate: true })

  return { onlineUsers, isConnected, subscribe, unsubscribe }
}