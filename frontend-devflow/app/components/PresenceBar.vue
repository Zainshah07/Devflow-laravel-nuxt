<template>
  <div class="presence-bar">

    <div
      class="connection-dot"
      :class="presenceStore.isConnected ? 'dot--live' : 'dot--offline'"
      :title="presenceStore.isConnected ? 'Real-time connected' : 'Connecting...'"
    />

    <div v-if="presenceStore.onlineUsers.length > 0" class="avatars">
      <div
        v-for="user in visibleUsers"
        :key="user.id"
        class="avatar-bubble"
        :title="user.name"
      >
        {{ initials(user.name) }}
      </div>
      <div v-if="extraCount > 0" class="avatar-extra">
        +{{ extraCount }}
      </div>
    </div>

    <span class="presence-label">
      <template v-if="presenceStore.onlineUsers.length === 0">
        No one else online
      </template>
      <template v-else-if="presenceStore.onlineUsers.length === 1">
        {{ presenceStore.onlineUsers[0].name }} is viewing
      </template>
      <template v-else>
        {{ presenceStore.onlineUsers.length }} people viewing
      </template>
    </span>

  </div>
</template>

<script setup lang="ts">
import { usePresenceStore } from '~/stores/presence'

const presenceStore = usePresenceStore()

const maxVisible  = 3
const visibleUsers = computed(() => presenceStore.onlineUsers.slice(0, maxVisible))
const extraCount   = computed(() => Math.max(0, presenceStore.onlineUsers.length - maxVisible))

function initials(name: string): string {
  return name
    .split(' ')
    .slice(0, 2)
    .map(n => n[0])
    .join('')
    .toUpperCase()
}
</script>

<style scoped>
.presence-bar {
  display: flex;
  align-items: center;
  gap: 8px;
}

.connection-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
  transition: background 0.3s;
}

.dot--live {
  background: #1d9e75;
  box-shadow: 0 0 0 2px #dcfce7;
}

.dot--offline {
  background: #d1d5db;
}

.avatars {
  display: flex;
  align-items: center;
}

.avatar-bubble {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: #e6f1fb;
  color: #185fa5;
  font-size: 10px;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #ffffff;
  margin-left: -6px;
  flex-shrink: 0;
}

.avatar-bubble:first-child {
  margin-left: 0;
}

.avatar-extra {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: #f3f4f6;
  color: #6b7280;
  font-size: 10px;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #ffffff;
  margin-left: -6px;
}

.presence-label {
  font-size: 12px;
  color: #9ca3af;
  white-space: nowrap;
}
</style>