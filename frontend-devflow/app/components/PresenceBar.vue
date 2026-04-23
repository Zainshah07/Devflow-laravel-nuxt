<template>
  <div class="presence-bar">

    <!-- Connection status indicator -->
    <div
      class="connection-dot"
      :class="connected ? 'connection-dot--live' : 'connection-dot--offline'"
      :title="connected ? 'Real-time connected' : 'Connecting...'"
    />

    <!-- Online user avatars -->
    <div v-if="users.length > 0" class="avatars">
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

    <!-- Label -->
    <span class="presence-label">
      <template v-if="users.length === 0">
        No one else online
      </template>
      <template v-else-if="users.length === 1">
        {{ users[0].name }} is viewing
      </template>
      <template v-else>
        {{ users.length }} people viewing
      </template>
    </span>

  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  users:     Array<{ id: number; name: string }>
  connected: boolean
}>()

// Show max 3 avatars, then "+N" for the rest
// DSA: slice is O(k) where k = maxVisible — constant time
const maxVisible  = 3
const visibleUsers = computed(() => props.users.slice(0, maxVisible))
const extraCount   = computed(() => Math.max(0, props.users.length - maxVisible))

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

.connection-dot--live {
  background: #1d9e75;
  box-shadow: 0 0 0 2px #dcfce7;
}

.connection-dot--offline {
  background: #d1d5db;
}

.avatars {
  display: flex;
  align-items: center;
}

.avatar-bubble {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #e6f1fb;
  color: #185fa5;
  font-size: 9px;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #ffffff;
  margin-left: -5px;
  flex-shrink: 0;
}

.avatar-bubble:first-child {
  margin-left: 0;
}

.avatar-extra {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #f3f4f6;
  color: #6b7280;
  font-size: 9px;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #ffffff;
  margin-left: -5px;
}

.presence-label {
  font-size: 12px;
  color: #9ca3af;
  white-space: nowrap;
}
</style>