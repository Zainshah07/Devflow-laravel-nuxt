<template>
  <div class="layout">
    <aside class="sidebar">
      <div class="sidebar-logo">
        <span class="logo-text">DevFlow</span>
        <small class="logo-sub">Workspace</small>
      </div>

      <nav class="sidebar-nav">
        <p class="nav-section-label">Navigation</p>
        <NuxtLink to="/" class="nav-item" active-class="nav-item--active" exact>
          <svg width="15" height="15" viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="1" y="1" width="5" height="5" rx="1"/>
            <rect x="9" y="1" width="5" height="5" rx="1"/>
            <rect x="1" y="9" width="5" height="5" rx="1"/>
            <rect x="9" y="9" width="5" height="5" rx="1"/>
          </svg>
          Dashboard
        </NuxtLink>
        <NuxtLink to="/projects" class="nav-item" active-class="nav-item--active">
          <svg width="15" height="15" viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="1" y="3" width="4" height="9" rx="1"/>
            <rect x="6" y="3" width="4" height="6" rx="1"/>
            <rect x="11" y="3" width="3" height="11" rx="1"/>
          </svg>
          Projects
        </NuxtLink>
      </nav>

      <div class="sidebar-bottom">
        <div class="user-row">
          <div class="user-avatar">{{ userInitials }}</div>
          <div class="user-info">
            <span class="user-name">{{ authStore.user?.name ?? 'Loading...' }}</span>
            <span class="user-email">{{ authStore.user?.email }}</span>
          </div>
        </div>
        <button class="logout-btn" @click="handleLogout">Sign out</button>
      </div>
    </aside>

    <div class="main-wrapper">
      <header class="topbar">
        <h1 class="page-title">{{ pageTitle }}</h1>

        <div class="topbar-right">
          <!-- Server status badges — Day 8 + 9 addition -->
          <div class="status-cluster">
            <!-- Backend health -->
            <div
              class="status-pill"
              :class="serverStatus.isHealthy.value ? 'status-pill--ok' : 'status-pill--error'"
              :title="'Server: ' + (serverStatus.serverName.value ?? 'unknown')"
            >
              <span class="status-dot" />
              <span>{{ serverStatus.isHealthy.value ? 'API online' : 'API offline' }}</span>
            </div>

            <!-- Replica status — Day 9 -->
            <div
              v-if="serverStatus.replicaConnected.value"
              class="status-pill status-pill--replica"
              title="MySQL read replica connected"
            >
              <span class="status-dot" />
              <span>replica</span>
            </div>

            <!-- Redis status -->
            <div
              v-if="serverStatus.redisConnected.value"
              class="status-pill status-pill--redis"
              title="Redis connected"
            >
              <span class="status-dot" />
              <span>redis</span>
            </div>
          </div>
        </div>
      </header>

      <main class="page-body">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore }     from '~/stores/auth'
import { useServerStatus }  from '~/composables/useServerStatus'

const route        = useRoute()
const router       = useRouter()
const authStore    = useAuthStore()
const serverStatus = useServerStatus()

const pageTitles: Record<string, string> = {
  '/':         'Dashboard',
  '/projects': 'All Projects',
}

const pageTitle = computed(() => {
  if (route.path.match(/^\/projects\/\d+\/graph$/)) return 'Dependency Graph'
  if (route.path.match(/^\/projects\/\d+/))         return 'Project Board'
  return pageTitles[route.path] ?? 'DevFlow'
})

const userInitials = computed(() => {
  const name = authStore.user?.name ?? ''
  return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase() || 'U'
})

onMounted(() => {
  serverStatus.fetchStatus()
})

async function handleLogout(): Promise<void> {
  await authStore.logout()
  await router.push('/login')
}
</script>

<style scoped>
.layout {
  display: flex;
  min-height: 100vh;
  background: #f9fafb;
}

.sidebar {
  width: 220px;
  flex-shrink: 0;
  background: #ffffff;
  border-right: 1px solid #e5e7eb;
  display: flex;
  flex-direction: column;
  position: sticky;
  top: 0;
  height: 100vh;
}

.sidebar-logo {
  padding: 18px 16px 14px;
  border-bottom: 1px solid #e5e7eb;
}

.logo-text {
  display: block;
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  letter-spacing: -0.3px;
}

.logo-sub {
  font-size: 11px;
  color: #9ca3af;
  font-style: normal;
}

.sidebar-nav {
  padding: 10px 8px;
  flex: 1;
}

.nav-section-label {
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: #d1d5db;
  padding: 8px 8px 4px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border-radius: 6px;
  font-size: 13px;
  color: #6b7280;
  margin: 2px 0;
  transition: background 0.1s, color 0.1s;
}

.nav-item:hover {
  background: #f3f4f6;
  color: #111827;
}

.nav-item--active {
  background: #f3f4f6;
  color: #111827;
  font-weight: 500;
}

.nav-item svg {
  flex-shrink: 0;
  opacity: 0.6;
}

.nav-item--active svg {
  opacity: 1;
}

.sidebar-bottom {
  padding: 12px 10px;
  border-top: 1px solid #e5e7eb;
}

.user-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 6px 8px;
}

.user-avatar {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: #e6f1fb;
  color: #185fa5;
  font-size: 11px;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.user-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.user-name {
  font-size: 12px;
  font-weight: 500;
  color: #111827;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-email {
  font-size: 11px;
  color: #9ca3af;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.logout-btn {
  width: 100%;
  padding: 6px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background: transparent;
  font-size: 12px;
  color: #6b7280;
  cursor: pointer;
  text-align: left;
  transition: background 0.1s, color 0.1s;
}

.logout-btn:hover {
  background: #fee2e2;
  color: #dc2626;
  border-color: #fca5a5;
}

.main-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.topbar {
  height: 54px;
  padding: 0 24px;
  border-bottom: 1px solid #e5e7eb;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 10;
}

.page-title {
  font-size: 15px;
  font-weight: 500;
  color: #111827;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.status-cluster {
  display: flex;
  align-items: center;
  gap: 5px;
}

.status-pill {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 10px;
  font-family: monospace;
  padding: 3px 8px;
  border-radius: 99px;
  border: 1px solid #e5e7eb;
  background: #f9fafb;
  color: #9ca3af;
}

.status-pill--ok {
  border-color: #bbf7d0;
  background: #f0fdf4;
  color: #166534;
}

.status-pill--error {
  border-color: #fca5a5;
  background: #fef2f2;
  color: #991b1b;
}

.status-pill--replica {
  border-color: #bfdbfe;
  background: #eff6ff;
  color: #1e40af;
}

.status-pill--redis {
  border-color: #fed7aa;
  background: #fff7ed;
  color: #9a3412;
}

.status-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: currentColor;
  flex-shrink: 0;
}

.page-body {
  padding: 24px;
  flex: 1;
}
</style>