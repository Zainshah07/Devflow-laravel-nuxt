<template>
  <div>
    <div class="page-header">
      <h2 class="section-title">All projects</h2>
    </div>

    <div v-if="store.loading">Loading...</div>

    <div v-else class="projects-list">
      <NuxtLink
        v-for="project in store.sortedProjects"
        :key="project.id"
        :to="`/projects/${project.id}`"
        class="list-row"
      >
        <div class="list-row-left">
          <div class="project-dot" />
          <div>
            <div class="list-name">{{ project.name }}</div>
            <div class="list-desc">{{ project.description ?? '—' }}</div>
          </div>
        </div>
        <div class="list-row-right">
          <span class="task-badge">{{ project.tasks_count ?? 0 }} tasks</span>
          <span class="list-date">{{ formatDate(project.created_at) }}</span>
        </div>
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout:     'default',
  middleware: 'auth',
})
import { useProjectStore } from '~/stores/projects'



const store = useProjectStore()

onMounted(async () => {
  if (store.projects.length === 0) {
    await store.fetchProjects()
  }
})

function formatDate(dateString: string): string {
  return new Intl.DateTimeFormat('en-US', {
    month: 'short', day: 'numeric', year: 'numeric',
  }).format(new Date(dateString))
}
</script>

<style scoped>
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.section-title {
  font-size: 15px;
  font-weight: 500;
}

.projects-list {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
}

.list-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  border-bottom: 1px solid #f3f4f6;
  transition: background 0.1s;
}

.list-row:last-child {
  border-bottom: none;
}

.list-row:hover {
  background: #f9fafb;
}

.list-row-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.project-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #378add;
  flex-shrink: 0;
}

.list-name {
  font-size: 13px;
  font-weight: 500;
  color: #111827;
}

.list-desc {
  font-size: 12px;
  color: #9ca3af;
  margin-top: 2px;
}

.list-row-right {
  display: flex;
  align-items: center;
  gap: 14px;
}

.task-badge {
  font-size: 11px;
  background: #f3f4f6;
  color: #6b7280;
  padding: 2px 8px;
  border-radius: 99px;
  font-weight: 500;
}

.list-date {
  font-size: 11px;
  color: #9ca3af;
}
</style>