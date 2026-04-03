<template>
  <div class="layout">
    <aside class="sidebar">
      <div class="sidebar-logo">
        <span class="logo-text">DevFlow</span>
      </div>

      <nav class="sidebar-nav">
        <p class="nav-section">Navigation</p>
        <NuxtLink to="/" class="nav-item" active-class="nav-item--active">
          Dashboard
        </NuxtLink>
        <NuxtLink to="/projects" class="nav-item" active-class="nav-item--active">
          Projects
        </NuxtLink>
      </nav>
    </aside>

    <main class="main-content">
      <header class="topbar">
        <h1 class="page-title">{{ pageTitle }}</h1>
      </header>

      <div class="page-body">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()

const pageTitle = computed(() => {
  const map: Record<string, string> = {
    '/':         'Dashboard',
    '/projects': 'Projects',
  }
  return map[route.path] ?? 'DevFlow'
})
</script>

<style scoped>
.layout {
  display: flex;
  min-height: 100vh;
}

.sidebar {
  width: 220px;
  flex-shrink: 0;
  background: #ffffff;
  border-right: 1px solid #e5e7eb;
  display: flex;
  flex-direction: column;
}

.sidebar-logo {
  padding: 20px 16px 16px;
  border-bottom: 1px solid #e5e7eb;
}

.logo-text {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
}

.sidebar-nav {
  padding: 12px 8px;
}

.nav-section {
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: #9ca3af;
  padding: 8px 8px 4px;
}

.nav-item {
  display: block;
  padding: 8px 10px;
  border-radius: 6px;
  font-size: 13px;
  color: #6b7280;
  margin: 1px 0;
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

.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.topbar {
  height: 56px;
  padding: 0 24px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  background: #ffffff;
}

.page-title {
  font-size: 16px;
  font-weight: 500;
  color: #111827;
}

.page-body {
  padding: 24px;
  flex: 1;
}
</style>