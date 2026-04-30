import { defineStore } from 'pinia'

interface PresenceUser {
  id:   number
  name: string
}

export const usePresenceStore = defineStore('presence', () => {
  const onlineUsers  = ref<PresenceUser[]>([])
  const isConnected  = ref(false)

  function setUsers(users: PresenceUser[]): void {
    onlineUsers.value = users
  }

  function addUser(user: PresenceUser): void {
    const exists = onlineUsers.value.find(u => u.id === user.id)
    if (!exists) {
      onlineUsers.value = [...onlineUsers.value, user]
    }
  }

  function removeUser(user: PresenceUser): void {
    onlineUsers.value = onlineUsers.value.filter(u => u.id !== user.id)
  }

  function setConnected(val: boolean): void {
    isConnected.value = val
  }

  function reset(): void {
    onlineUsers.value = []
    isConnected.value = false
  }

  return {
    onlineUsers,
    isConnected,
    setUsers,
    addUser,
    removeUser,
    setConnected,
    reset,
  }
})